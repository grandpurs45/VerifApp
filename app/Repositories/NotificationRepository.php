<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Env;
use App\Core\Database;
use PDO;

final class NotificationRepository
{
    private ?bool $notificationsTableExists = null;
    private ?bool $recipientsTableExists = null;
    private ?bool $subscriptionsTableExists = null;
    private string $lastEmailError = '';

    public function getLastEmailError(): string
    {
        return $this->lastEmailError;
    }

    public function sendTestEmail(int $caserneId, string $recipientEmail): bool
    {
        $this->lastEmailError = '';
        $recipientEmail = trim($recipientEmail);
        if ($caserneId <= 0 || $recipientEmail === '' || filter_var($recipientEmail, FILTER_VALIDATE_EMAIL) === false) {
            $this->lastEmailError = 'Destinataire test invalide.';
            return false;
        }

        $title = 'Test notifications email';
        $message = 'Si tu reçois ce message, le canal email VerifApp fonctionne sur ce serveur.';

        return $this->sendEmailNotifications(
            $caserneId,
            'anomaly.updated',
            $title,
            $message,
            '/index.php?controller=manager_admin&action=settings',
            [
                [
                    'id' => 0,
                    'email' => $recipientEmail,
                    'nom' => 'Test',
                ],
            ]
        );
    }

    /**
     * @return array<string, array{label: string, description: string, default_roles: array<int, string>}>
     */
    public static function eventCatalog(): array
    {
        return [
            'anomaly.created' => [
                'label' => 'Anomalies: nouvelle anomalie',
                'description' => 'Une verification non conforme vient de creer une anomalie.',
                'default_roles' => ['admin', 'responsable_materiel'],
            ],
            'anomaly.updated' => [
                'label' => 'Anomalies: mise a jour',
                'description' => 'Statut, priorite ou assignation d une anomalie.',
                'default_roles' => ['admin', 'responsable_materiel'],
            ],
            'pharmacy.output.created' => [
                'label' => 'Pharmacie: nouvelle sortie',
                'description' => 'Une sortie de stock pharmacie vient d etre enregistree.',
                'default_roles' => ['admin', 'resp_pharma', 'responsable_pharmacie', 'responsable_materiel'],
            ],
            'pharmacy.inventory.created' => [
                'label' => 'Pharmacie: inventaire saisi',
                'description' => 'Un inventaire pharmacie vient d etre saisi sur le terrain.',
                'default_roles' => ['admin', 'resp_pharma', 'responsable_pharmacie', 'responsable_materiel'],
            ],
        ];
    }

    public function isAvailable(): bool
    {
        return $this->hasNotificationsTable() && $this->hasRecipientsTable() && $this->hasSubscriptionsTable();
    }

    public function createForCaserneEvent(
        int $caserneId,
        string $eventCode,
        string $title,
        string $message,
        ?string $link = null,
        ?int $actorUserId = null,
        ?string $actorName = null,
        array $context = [],
        array $additionalRecipientUserIds = []
    ): bool {
        if (!$this->isAvailable() || $caserneId <= 0) {
            return false;
        }

        $eventCode = trim($eventCode);
        $catalog = self::eventCatalog();
        if (!isset($catalog[$eventCode])) {
            return false;
        }

        $settings = $this->readEventSettings($caserneId, $eventCode);
        if (!$settings['enabled']) {
            return true;
        }

        if ($eventCode === 'anomaly.created') {
            $vehicleId = (int) ($context['vehicle_id'] ?? 0);
            if ($vehicleId > 0) {
                $vehicleRouting = $this->readVehicleAnomalyRouting($caserneId, $vehicleId);
                if ($vehicleRouting['mode'] === 'custom') {
                    $settings['roles'] = $vehicleRouting['roles'];
                    $settings['users'] = $vehicleRouting['users'];
                    $settings['groups'] = $vehicleRouting['groups'];
                }
            }
        }

        $groupUserIds = $this->findUserIdsForGroups($caserneId, $settings['groups'] ?? []);

        $inAppRecipients = [];
        if ($settings['in_app_enabled']) {
            $inAppRecipients = $this->findRecipientUserIds(
                $caserneId,
                $eventCode,
                $settings['roles'],
                $settings['users'],
                array_merge($additionalRecipientUserIds, $groupUserIds),
                'in_app',
                null
            );
        }

        $emailRecipients = [];
        if ($settings['email_enabled']) {
            $emailRecipients = $this->findRecipientUsersWithEmail(
                $caserneId,
                $eventCode,
                $settings['roles'],
                $settings['users'],
                array_merge($additionalRecipientUserIds, $groupUserIds),
                null
            );
        }

        if ($inAppRecipients === [] && $emailRecipients === []) {
            error_log(sprintf(
                '[VerifApp notifications] aucun destinataire event=%s caserne=%d vehicle=%d roles=%d users=%d groups=%d',
                $eventCode,
                $caserneId,
                (int) ($context['vehicle_id'] ?? 0),
                count($settings['roles']),
                count($settings['users']),
                count($settings['groups'] ?? [])
            ));
            return true;
        }

        if ($inAppRecipients !== []) {
            $connection = Database::getConnection();
            try {
                $connection->beginTransaction();

                $insertNotif = $connection->prepare('
                    INSERT INTO notifications (caserne_id, event_code, severity, titre, message, lien, acteur_utilisateur_id, acteur_nom)
                    VALUES (:caserne_id, :event_code, :severity, :titre, :message, :lien, :acteur_utilisateur_id, :acteur_nom)
                ');
                $insertNotif->execute([
                    'caserne_id' => $caserneId,
                    'event_code' => $eventCode,
                    'severity' => 'info',
                    'titre' => mb_substr(trim($title), 0, 190),
                    'message' => mb_substr(trim($message), 0, 500),
                    'lien' => $link !== null && trim($link) !== '' ? mb_substr(trim($link), 0, 255) : null,
                    'acteur_utilisateur_id' => $actorUserId !== null && $actorUserId > 0 ? $actorUserId : null,
                    'acteur_nom' => $actorName !== null && trim($actorName) !== '' ? mb_substr(trim($actorName), 0, 150) : null,
                ]);

                $notificationId = (int) $connection->lastInsertId();
                if ($notificationId <= 0) {
                    $connection->rollBack();
                    return false;
                }

                $insertRecipient = $connection->prepare('
                    INSERT INTO notification_recipients (notification_id, utilisateur_id, lu)
                    VALUES (:notification_id, :utilisateur_id, 0)
                ');
                foreach ($inAppRecipients as $userId) {
                    $insertRecipient->execute([
                        'notification_id' => $notificationId,
                        'utilisateur_id' => $userId,
                    ]);
                }

                $connection->commit();
            } catch (\Throwable $throwable) {
                if (isset($connection) && $connection->inTransaction()) {
                    $connection->rollBack();
                }
                return false;
            }
        }

        if ($emailRecipients !== []) {
            $emailSent = $this->sendEmailNotifications(
                $caserneId,
                $eventCode,
                $title,
                $message,
                $link,
                $emailRecipients,
                $context
            );
            if (!$emailSent) {
                error_log(sprintf(
                    '[VerifApp notifications] echec email event=%s caserne=%d destinataires=%d erreur=%s',
                    $eventCode,
                    $caserneId,
                    count($emailRecipients),
                    $this->lastEmailError !== '' ? $this->lastEmailError : 'inconnue'
                ));
                return false;
            }
        }

        return true;
    }

    public function getUnreadCount(int $userId, ?int $caserneId = null): int
    {
        if (!$this->isAvailable() || $userId <= 0) {
            return 0;
        }

        $connection = Database::getConnection();
        $params = ['utilisateur_id' => $userId];
        $sql = '
            SELECT COUNT(*)
            FROM notification_recipients nr
            INNER JOIN notifications n ON n.id = nr.notification_id
            WHERE nr.utilisateur_id = :utilisateur_id
              AND nr.lu = 0
        ';
        if ($caserneId !== null && $caserneId > 0) {
            $sql .= ' AND n.caserne_id = :caserne_id';
            $params['caserne_id'] = $caserneId;
        }

        $statement = $connection->prepare($sql);
        $statement->execute($params);
        $value = $statement->fetchColumn();

        return $value === false ? 0 : (int) $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function findRecentForUser(int $userId, ?int $caserneId = null, int $limit = 6): array
    {
        if (!$this->isAvailable() || $userId <= 0) {
            return [];
        }

        $limit = max(1, min(20, $limit));
        $connection = Database::getConnection();
        $params = [
            'utilisateur_id' => $userId,
            'limit_rows' => $limit,
        ];
        $sql = '
            SELECT
                n.id,
                n.event_code,
                n.titre,
                n.message,
                n.lien,
                n.created_at,
                nr.lu,
                nr.lu_le
            FROM notification_recipients nr
            INNER JOIN notifications n ON n.id = nr.notification_id
            WHERE nr.utilisateur_id = :utilisateur_id
        ';
        if ($caserneId !== null && $caserneId > 0) {
            $sql .= ' AND n.caserne_id = :caserne_id';
            $params['caserne_id'] = $caserneId;
        }
        $sql .= '
            ORDER BY nr.lu ASC, n.created_at DESC
            LIMIT :limit_rows
        ';

        $statement = $connection->prepare($sql);
        foreach ($params as $name => $value) {
            $statement->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, string> $filters
     * @return array<int, array<string, mixed>>
     */
    public function findHistoryForUser(int $userId, ?int $caserneId, array $filters, int $limit = 100): array
    {
        if (!$this->isAvailable() || $userId <= 0) {
            return [];
        }

        $limit = max(10, min(400, $limit));
        $connection = Database::getConnection();
        $params = [
            'utilisateur_id' => $userId,
            'limit_rows' => $limit,
        ];
        $sql = '
            SELECT
                n.id,
                n.event_code,
                n.titre,
                n.message,
                n.lien,
                n.created_at,
                n.acteur_nom,
                nr.lu,
                nr.lu_le
            FROM notification_recipients nr
            INNER JOIN notifications n ON n.id = nr.notification_id
            WHERE nr.utilisateur_id = :utilisateur_id
        ';
        if ($caserneId !== null && $caserneId > 0) {
            $sql .= ' AND n.caserne_id = :caserne_id';
            $params['caserne_id'] = $caserneId;
        }

        $readFilter = strtolower(trim((string) ($filters['lu'] ?? 'all')));
        if ($readFilter === 'unread') {
            $sql .= ' AND nr.lu = 0';
        } elseif ($readFilter === 'read') {
            $sql .= ' AND nr.lu = 1';
        }

        $eventCode = trim((string) ($filters['event_code'] ?? ''));
        if ($eventCode !== '' && isset(self::eventCatalog()[$eventCode])) {
            $sql .= ' AND n.event_code = :event_code';
            $params['event_code'] = $eventCode;
        }

        $sql .= ' ORDER BY n.created_at DESC LIMIT :limit_rows';

        $statement = $connection->prepare($sql);
        foreach ($params as $name => $value) {
            $statement->bindValue(':' . $name, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markAsRead(int $userId, int $notificationId): bool
    {
        if (!$this->isAvailable() || $userId <= 0 || $notificationId <= 0) {
            return false;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            UPDATE notification_recipients
            SET lu = 1, lu_le = NOW()
            WHERE utilisateur_id = :utilisateur_id
              AND notification_id = :notification_id
        ');

        return $statement->execute([
            'utilisateur_id' => $userId,
            'notification_id' => $notificationId,
        ]);
    }

    public function markAllAsRead(int $userId, ?int $caserneId = null): bool
    {
        if (!$this->isAvailable() || $userId <= 0) {
            return false;
        }

        $connection = Database::getConnection();
        $params = ['utilisateur_id' => $userId];
        $sql = '
            SELECT nr.notification_id
            FROM notification_recipients nr
            INNER JOIN notifications n ON n.id = nr.notification_id
            WHERE nr.utilisateur_id = :utilisateur_id
              AND nr.lu = 0
        ';
        if ($caserneId !== null && $caserneId > 0) {
            $sql .= ' AND n.caserne_id = :caserne_id';
            $params['caserne_id'] = $caserneId;
        }

        $statement = $connection->prepare($sql);
        $statement->execute($params);
        $notificationIds = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN) ?: []);
        $notificationIds = array_values(array_filter($notificationIds, static fn (int $id): bool => $id > 0));
        if ($notificationIds === []) {
            return true;
        }

        $placeholders = [];
        $updateParams = ['utilisateur_id' => $userId];
        foreach ($notificationIds as $index => $notificationId) {
            $paramName = 'notification_id_' . $index;
            $placeholders[] = ':' . $paramName;
            $updateParams[$paramName] = $notificationId;
        }

        $update = $connection->prepare('
            UPDATE notification_recipients
            SET lu = 1, lu_le = NOW()
            WHERE utilisateur_id = :utilisateur_id
              AND lu = 0
              AND notification_id IN (' . implode(',', $placeholders) . ')
        ');

        return $update->execute($updateParams);
    }

    /**
     * @return array<string, array{in_app_enabled: bool, email_enabled: bool}>
     */
    public function findSubscriptionsByUser(int $userId): array
    {
        $defaults = [];
        foreach (self::eventCatalog() as $eventCode => $_eventMeta) {
            $defaults[$eventCode] = [
                'in_app_enabled' => true,
                'email_enabled' => true,
            ];
        }

        if (!$this->isAvailable() || $userId <= 0) {
            return $defaults;
        }

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT event_code, in_app_enabled, email_enabled
            FROM notification_subscriptions
            WHERE utilisateur_id = :utilisateur_id
        ');
        $statement->execute(['utilisateur_id' => $userId]);
        $rows = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as $row) {
            $eventCode = (string) ($row['event_code'] ?? '');
            if (!isset($defaults[$eventCode])) {
                continue;
            }
            $defaults[$eventCode] = [
                'in_app_enabled' => (int) ($row['in_app_enabled'] ?? 0) === 1,
                'email_enabled' => (int) ($row['email_enabled'] ?? 0) === 1,
            ];
        }

        return $defaults;
    }

    /**
     * @param array<string, bool> $inAppByEvent
     */
    public function saveSubscriptionsByUser(int $userId, array $inAppByEvent, array $emailByEvent = []): bool
    {
        if (!$this->isAvailable() || $userId <= 0) {
            return false;
        }

        $catalog = self::eventCatalog();
        $connection = Database::getConnection();

        try {
            $connection->beginTransaction();
            $statement = $connection->prepare('
                INSERT INTO notification_subscriptions (utilisateur_id, event_code, in_app_enabled, email_enabled)
                VALUES (:utilisateur_id, :event_code, :in_app_enabled, :email_enabled)
                ON DUPLICATE KEY UPDATE
                    in_app_enabled = VALUES(in_app_enabled),
                    email_enabled = VALUES(email_enabled)
            ');

            foreach ($catalog as $eventCode => $_meta) {
                $statement->execute([
                    'utilisateur_id' => $userId,
                    'event_code' => $eventCode,
                    'in_app_enabled' => isset($inAppByEvent[$eventCode]) && $inAppByEvent[$eventCode] ? 1 : 0,
                    'email_enabled' => isset($emailByEvent[$eventCode]) && $emailByEvent[$eventCode] ? 1 : 0,
                ]);
            }

            $connection->commit();
            return true;
        } catch (\Throwable $throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            return false;
        }
    }

    /**
     * @return array<string, array{enabled: bool, roles: array<int, string>, users: array<int, int>, in_app_enabled: bool, email_enabled: bool}>
     */
    public function readAdminSettings(?int $caserneId): array
    {
        $settings = [];
        $catalog = self::eventCatalog();
        foreach ($catalog as $eventCode => $meta) {
            $settings[$eventCode] = $this->readEventSettings($caserneId, $eventCode);
        }

        return $settings;
    }

    /**
     * @return array{mode: 'default'|'custom', roles: array<int, string>, users: array<int, int>, groups: array<int, int>}
     */
    public function readVehicleAnomalyRouting(int $caserneId, int $vehicleId): array
    {
        if ($caserneId <= 0 || $vehicleId <= 0) {
            return [
                'mode' => 'default',
                'roles' => [],
                'users' => [],
                'groups' => [],
            ];
        }

        $settingRepository = new AppSettingRepository();
        if (!$settingRepository->isAvailable()) {
            return [
                'mode' => 'default',
                'roles' => [],
                'users' => [],
                'groups' => [],
            ];
        }

        $prefix = $this->vehicleAnomalySettingPrefix($vehicleId);
        $suffix = '_caserne_' . $caserneId;
        $mode = strtolower(trim((string) ($settingRepository->get($prefix . '_mode' . $suffix) ?? 'default')));
        if ($mode !== 'custom') {
            return [
                'mode' => 'default',
                'roles' => [],
                'users' => [],
                'groups' => [],
            ];
        }

        $rolesRaw = (string) ($settingRepository->get($prefix . '_roles' . $suffix) ?? '__none__');
        $usersRaw = (string) ($settingRepository->get($prefix . '_users' . $suffix) ?? '__none__');
        $groupsRaw = (string) ($settingRepository->get($prefix . '_groups' . $suffix) ?? '__none__');

        return [
            'mode' => 'custom',
            'roles' => $this->normalizeRoles(explode(',', $rolesRaw)),
            'users' => $this->normalizeUserIds(explode(',', $usersRaw)),
            'groups' => $this->normalizeUserIds(explode(',', $groupsRaw)),
        ];
    }

    /**
     * @param array<int, string> $roles
     * @param array<int, int> $userIds
     */
    public function saveVehicleAnomalyRouting(
        int $caserneId,
        int $vehicleId,
        string $mode,
        array $roles,
        array $userIds,
        array $groupIds = []
    ): bool {
        if ($caserneId <= 0 || $vehicleId <= 0) {
            return false;
        }

        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['default', 'custom'], true)) {
            return false;
        }

        $settingRepository = new AppSettingRepository();
        if (!$settingRepository->isAvailable()) {
            return false;
        }

        $prefix = $this->vehicleAnomalySettingPrefix($vehicleId);
        $suffix = '_caserne_' . $caserneId;
        if ($mode === 'default') {
            return $settingRepository->set($prefix . '_mode' . $suffix, 'default');
        }

        $selectedRoles = $this->normalizeRoles($roles);
        $selectedUsers = $this->normalizeUserIds($userIds);
        $selectedGroups = $this->normalizeUserIds($groupIds);
        if ($selectedRoles === [] && $selectedUsers === [] && $selectedGroups === []) {
            return false;
        }

        if (!$settingRepository->set(
            $prefix . '_roles' . $suffix,
            $selectedRoles === [] ? '__none__' : implode(',', $selectedRoles)
        )) {
            return false;
        }
        if (!$settingRepository->set(
            $prefix . '_groups' . $suffix,
            $selectedGroups === [] ? '__none__' : implode(',', $selectedGroups)
        )) {
            return false;
        }
        if (!$settingRepository->set(
            $prefix . '_users' . $suffix,
            $selectedUsers === [] ? '__none__' : implode(',', $selectedUsers)
        )) {
            return false;
        }

        return $settingRepository->set($prefix . '_mode' . $suffix, 'custom');
    }

    /**
     * @param array<string, bool> $enabledByEvent
     * @param array<string, array<int, string>> $rolesByEvent
     * @param array<string, array<int, int>> $usersByEvent
     */
    public function saveAdminSettings(
        ?int $caserneId,
        bool $inAppEnabled,
        bool $emailEnabled,
        array $enabledByEvent,
        array $rolesByEvent,
        array $usersByEvent,
        array $groupsByEvent = []
    ): bool {
        $settingRepository = new AppSettingRepository();
        if (!$settingRepository->isAvailable()) {
            return false;
        }

        $suffix = ($caserneId !== null && $caserneId > 0) ? '_caserne_' . $caserneId : '';
        $catalog = self::eventCatalog();

        $saveMap = [
            'notifications_channel_in_app_enabled' . $suffix => $inAppEnabled ? '1' : '0',
            'notifications_channel_email_enabled' . $suffix => $emailEnabled ? '1' : '0',
        ];

        foreach ($catalog as $eventCode => $meta) {
            $eventKey = str_replace('.', '_', $eventCode);
            $defaultRoles = $this->normalizeRoles((array) ($meta['default_roles'] ?? []));
            $selectedRoles = $this->normalizeRoles($rolesByEvent[$eventCode] ?? $defaultRoles);
            $selectedUsers = $this->normalizeUserIds($usersByEvent[$eventCode] ?? []);
            $selectedGroups = $this->normalizeUserIds($groupsByEvent[$eventCode] ?? []);

            $saveMap['notifications_event_' . $eventKey . '_enabled' . $suffix] =
                (isset($enabledByEvent[$eventCode]) && $enabledByEvent[$eventCode]) ? '1' : '0';
            $saveMap['notifications_event_' . $eventKey . '_roles' . $suffix] =
                $selectedRoles === [] ? '__none__' : implode(',', $selectedRoles);
            $saveMap['notifications_event_' . $eventKey . '_users' . $suffix] =
                $selectedUsers === [] ? '__none__' : implode(',', $selectedUsers);
            $saveMap['notifications_event_' . $eventKey . '_groups' . $suffix] =
                $selectedGroups === [] ? '__none__' : implode(',', $selectedGroups);
        }

        foreach ($saveMap as $key => $value) {
            if (!$settingRepository->set($key, $value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param array<int, string> $targetRoles
     * @param array<int, int> $targetUserIds
     * @param array<int, int> $additionalRecipientUserIds
     * @return array<int, int>
     */
    private function findRecipientUserIds(
        int $caserneId,
        string $eventCode,
        array $targetRoles,
        array $targetUserIds,
        array $additionalRecipientUserIds,
        string $channel = 'in_app',
        ?int $excludeUserId = null
    ): array
    {
        $users = $this->findRecipientUsers(
            $caserneId,
            $eventCode,
            $targetRoles,
            array_merge($targetUserIds, $additionalRecipientUserIds),
            $channel,
            $excludeUserId
        );
        $ids = [];
        foreach ($users as $user) {
            $id = (int) ($user['id'] ?? 0);
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    /**
     * @param array<int, string> $targetRoles
     * @param array<int, int> $targetUserIds
     * @param array<int, int> $additionalRecipientUserIds
     * @return array<int, array{id:int,email:string,nom:string}>
     */
    private function findRecipientUsersWithEmail(
        int $caserneId,
        string $eventCode,
        array $targetRoles,
        array $targetUserIds,
        array $additionalRecipientUserIds,
        ?int $excludeUserId = null
    ): array {
        $users = $this->findRecipientUsers(
            $caserneId,
            $eventCode,
            $targetRoles,
            array_merge($targetUserIds, $additionalRecipientUserIds),
            'email',
            $excludeUserId
        );
        $result = [];
        foreach ($users as $user) {
            $email = trim((string) ($user['email'] ?? ''));
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                continue;
            }
            $id = (int) ($user['id'] ?? 0);
            if ($id <= 0) {
                continue;
            }
            $result[$id] = [
                'id' => $id,
                'email' => $email,
                'nom' => trim((string) ($user['nom'] ?? '')),
            ];
        }

        return array_values($result);
    }

    /**
     * @param array<int, string> $targetRoles
     * @param array<int, int> $targetUserIds
     * @return array<int, array<string, mixed>>
     */
    private function findRecipientUsers(
        int $caserneId,
        string $eventCode,
        array $targetRoles,
        array $targetUserIds,
        string $channel = 'in_app',
        ?int $excludeUserId = null
    ): array {
        $roles = $this->normalizeRoles($targetRoles);
        $userIds = $this->normalizeUserIds($targetUserIds);
        if ($roles === [] && $userIds === []) {
            return [];
        }

        $channel = strtolower(trim($channel));
        $channelClause = $channel === 'email'
            ? 'COALESCE(ns.email_enabled, 1) = 1'
            : 'COALESCE(ns.in_app_enabled, 1) = 1';

        $connection = Database::getConnection();
        $params = [
            'caserne_id' => $caserneId,
            'event_code' => $eventCode,
        ];
        $rolePlaceholders = [];
        foreach ($roles as $index => $roleCode) {
            $key = 'role_' . $index;
            $rolePlaceholders[] = 'CONVERT(:' . $key . ' USING utf8mb4) COLLATE utf8mb4_unicode_ci';
            $params[$key] = $roleCode;
        }
        $userPlaceholders = [];
        foreach ($userIds as $index => $userId) {
            $key = 'user_id_' . $index;
            $userPlaceholders[] = ':' . $key;
            $params[$key] = $userId;
        }

        $targetClauses = [];
        if ($rolePlaceholders !== []) {
            $targetClauses[] = 'LOWER(
                    TRIM(
                        COALESCE(
                            NULLIF(CONVERT(uc.role_code USING utf8mb4) COLLATE utf8mb4_unicode_ci, \'\'),
                            CONVERT(u.role USING utf8mb4) COLLATE utf8mb4_unicode_ci
                        )
                    )
                ) COLLATE utf8mb4_unicode_ci IN (' . implode(',', $rolePlaceholders) . ')';
        }
        if ($userPlaceholders !== []) {
            $targetClauses[] = 'u.id IN (' . implode(',', $userPlaceholders) . ')';
        }

        $sql = '
            SELECT DISTINCT u.id, u.email, u.nom
            FROM utilisateurs u
            INNER JOIN utilisateur_casernes uc ON uc.utilisateur_id = u.id
            LEFT JOIN notification_subscriptions ns
                   ON ns.utilisateur_id = u.id
                  AND ns.event_code = :event_code
            WHERE u.actif = 1
              AND uc.caserne_id = :caserne_id
              AND (' . implode(' OR ', $targetClauses) . ')
              AND ' . $channelClause . '
        ';
        if ($excludeUserId !== null && $excludeUserId > 0) {
            $sql .= ' AND u.id <> :exclude_user_id';
            $params['exclude_user_id'] = $excludeUserId;
        }

        $statement = $connection->prepare($sql);
        $statement->execute($params);

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<int, array{id:int,email:string,nom:string}> $recipients
     */
    private function sendEmailNotifications(int $caserneId, string $eventCode, string $title, string $message, ?string $link, array $recipients, array $context = []): bool
    {
        $this->lastEmailError = '';
        $config = $this->readEmailConfig();
        if (!$config['enabled']) {
            $this->lastEmailError = 'Canal email desactive.';
            return false;
        }
        $from = (string) $config['from'];
        $fromName = (string) $config['from_name'];
        $appUrl = rtrim((string) Env::get('APP_URL', ''), '/');

        $subject = '[VerifApp] ' . trim($title);
        if (mb_strlen($subject) > 180) {
            $subject = mb_substr($subject, 0, 180);
        }
        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');

        $bodies = $this->buildEmailBodies($caserneId, $eventCode, $title, $message, $link, $context, $appUrl);
        $bodyText = $bodies['text'];
        $bodyHtml = $bodies['html'];

        $boundary = 'verifapp_' . md5((string) microtime(true) . (string) mt_rand());
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'From: ' . ($fromName !== '' ? ($fromName . ' <' . $from . '>') : $from),
        ];
        $headerText = implode("\r\n", $headers);
        $mailBody = $this->buildMultipartBody($boundary, $bodyText, $bodyHtml);
        $hasRecipient = false;
        $hasSent = false;

        foreach ($recipients as $recipient) {
            $email = trim((string) ($recipient['email'] ?? ''));
            if ($email === '') {
                continue;
            }
            $hasRecipient = true;
            if ((string) $config['transport'] === 'smtp') {
                $sent = $this->sendViaSmtp((array) $config, $email, $subject, $bodyText, $bodyHtml, $from, $fromName);
            } else {
                $sent = @mail($email, $encodedSubject, $mailBody, $headerText);
                if (!$sent) {
                    $this->lastEmailError = 'mail() PHP a retourne false.';
                }
            }
            if ($sent) {
                $hasSent = true;
            }
        }

        if (!$hasRecipient) {
            $this->lastEmailError = 'Aucun destinataire email valide.';
        } elseif (!$hasSent && $this->lastEmailError === '') {
            $this->lastEmailError = 'Envoi email refuse par le transport.';
        }

        return $hasRecipient && $hasSent;
    }

    /**
     * @return array{
     *   enabled: bool,
     *   from: string,
     *   from_name: string,
     *   transport: string,
     *   smtp_host: string,
     *   smtp_port: int,
     *   smtp_security: string,
     *   smtp_auth: bool,
     *   smtp_user: string,
     *   smtp_pass: string,
     *   smtp_timeout: int
     * }
     */
    private function readEmailConfig(): array
    {
        $settingRepository = new AppSettingRepository();
        $read = static function (string $settingKey, string $envKey, string $default = '') use ($settingRepository): string {
            if ($settingRepository->isAvailable()) {
                $value = $settingRepository->get($settingKey);
                if ($value !== null && trim((string) $value) !== '') {
                    return trim((string) $value);
                }
            }

            return trim((string) (Env::get($envKey, $default) ?? $default));
        };

        $enabled = $read('notifications_email_enabled', 'NOTIFICATIONS_EMAIL_ENABLED', '0') === '1';
        $from = $read('notifications_email_from', 'NOTIFICATIONS_EMAIL_FROM', 'no-reply@verifapp.local');
        $fromName = $read('notifications_email_from_name', 'NOTIFICATIONS_EMAIL_FROM_NAME', 'VerifApp');
        $transport = strtolower($read('notifications_email_transport', 'NOTIFICATIONS_EMAIL_TRANSPORT', 'mail'));
        if (!in_array($transport, ['mail', 'smtp'], true)) {
            $transport = 'mail';
        }
        $smtpHost = $read('notifications_email_smtp_host', 'NOTIFICATIONS_EMAIL_SMTP_HOST', '');
        $smtpPortRaw = $read('notifications_email_smtp_port', 'NOTIFICATIONS_EMAIL_SMTP_PORT', '587');
        $smtpPort = ctype_digit($smtpPortRaw) ? (int) $smtpPortRaw : 587;
        if ($smtpPort <= 0 || $smtpPort > 65535) {
            $smtpPort = 587;
        }
        $smtpSecurity = strtolower($read('notifications_email_smtp_security', 'NOTIFICATIONS_EMAIL_SMTP_SECURITY', 'tls'));
        if (!in_array($smtpSecurity, ['none', 'tls', 'ssl'], true)) {
            $smtpSecurity = 'tls';
        }
        $smtpAuth = $read('notifications_email_smtp_auth', 'NOTIFICATIONS_EMAIL_SMTP_AUTH', '1') === '1';
        $smtpUser = $read('notifications_email_smtp_user', 'NOTIFICATIONS_EMAIL_SMTP_USER', '');
        $smtpPass = $read('notifications_email_smtp_pass', 'NOTIFICATIONS_EMAIL_SMTP_PASS', '');
        $smtpTimeoutRaw = $read('notifications_email_smtp_timeout', 'NOTIFICATIONS_EMAIL_SMTP_TIMEOUT', '12');
        $smtpTimeout = ctype_digit($smtpTimeoutRaw) ? (int) $smtpTimeoutRaw : 12;
        if ($smtpTimeout < 3 || $smtpTimeout > 60) {
            $smtpTimeout = 12;
        }

        return [
            'enabled' => $enabled,
            'from' => $from,
            'from_name' => $fromName,
            'transport' => $transport,
            'smtp_host' => $smtpHost,
            'smtp_port' => $smtpPort,
            'smtp_security' => $smtpSecurity,
            'smtp_auth' => $smtpAuth,
            'smtp_user' => $smtpUser,
            'smtp_pass' => $smtpPass,
            'smtp_timeout' => $smtpTimeout,
        ];
    }

    /**
     * @param array<string, mixed> $config
     */
    private function sendViaSmtp(array $config, string $toEmail, string $subject, string $bodyText, string $bodyHtml, string $fromEmail, string $fromName): bool
    {
        $host = trim((string) ($config['smtp_host'] ?? ''));
        $port = (int) ($config['smtp_port'] ?? 587);
        $security = (string) ($config['smtp_security'] ?? 'tls');
        $authEnabled = (bool) ($config['smtp_auth'] ?? true);
        $username = trim((string) ($config['smtp_user'] ?? ''));
        $password = (string) ($config['smtp_pass'] ?? '');
        $timeout = (int) ($config['smtp_timeout'] ?? 12);

        if ($host === '' || $port <= 0) {
            $this->lastEmailError = 'SMTP host/port invalide.';
            return false;
        }
        if ($authEnabled && ($username === '' || $password === '')) {
            $this->lastEmailError = 'SMTP auth active mais user/pass manquants.';
            return false;
        }

        $remoteHost = $security === 'ssl' ? 'ssl://' . $host : $host;
        $socket = @stream_socket_client($remoteHost . ':' . $port, $errno, $errstr, $timeout);
        if (!is_resource($socket)) {
            $this->lastEmailError = 'Connexion SMTP impossible: ' . $host . ':' . $port . ' (' . $errstr . ')';
            return false;
        }

        stream_set_timeout($socket, $timeout);

        if (!$this->smtpExpect($socket, [220])) {
            $this->lastEmailError = 'SMTP: banniere serveur invalide.';
            fclose($socket);
            return false;
        }

        $helloHost = parse_url((string) Env::get('APP_URL', ''), PHP_URL_HOST);
        if (!is_string($helloHost) || trim($helloHost) === '') {
            $helloHost = 'localhost';
        }

        if (!$this->smtpCommand($socket, 'EHLO ' . $helloHost, [250])) {
            $this->lastEmailError = 'SMTP: EHLO refuse.';
            fclose($socket);
            return false;
        }

        if ($security === 'tls') {
            if (!$this->smtpCommand($socket, 'STARTTLS', [220])) {
                $this->lastEmailError = 'SMTP: STARTTLS refuse.';
                fclose($socket);
                return false;
            }
            $cryptoEnabled = @stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($cryptoEnabled !== true) {
                $this->lastEmailError = 'SMTP: echec negotiation TLS.';
                fclose($socket);
                return false;
            }
            if (!$this->smtpCommand($socket, 'EHLO ' . $helloHost, [250])) {
                $this->lastEmailError = 'SMTP: EHLO apres TLS refuse.';
                fclose($socket);
                return false;
            }
        }

        if ($authEnabled) {
            $authOk = false;
            if ($this->smtpCommand($socket, 'AUTH LOGIN', [334])) {
                if ($this->smtpCommand($socket, base64_encode($username), [334])) {
                    if ($this->smtpCommand($socket, base64_encode($password), [235])) {
                        $authOk = true;
                    }
                }
            }
            if (!$authOk) {
                $plain = base64_encode("\0" . $username . "\0" . $password);
                if ($this->smtpCommand($socket, 'AUTH PLAIN ' . $plain, [235])) {
                    $authOk = true;
                }
            }
            if (!$authOk) {
                $this->lastEmailError = 'SMTP: authentification refusee (LOGIN/PLAIN).';
                fclose($socket);
                return false;
            }
        }

        if (!$this->smtpCommand($socket, 'MAIL FROM:<' . $fromEmail . '>', [250])) {
            $this->lastEmailError = 'SMTP: MAIL FROM refuse.';
            fclose($socket);
            return false;
        }
        if (!$this->smtpCommand($socket, 'RCPT TO:<' . $toEmail . '>', [250, 251])) {
            $this->lastEmailError = 'SMTP: RCPT TO refuse pour ' . $toEmail . '.';
            fclose($socket);
            return false;
        }
        if (!$this->smtpCommand($socket, 'DATA', [354])) {
            $this->lastEmailError = 'SMTP: DATA refuse.';
            fclose($socket);
            return false;
        }

        $encodedSubject = mb_encode_mimeheader($subject, 'UTF-8');
        $displayFrom = $fromName !== '' ? mb_encode_mimeheader($fromName, 'UTF-8') . ' <' . $fromEmail . '>' : $fromEmail;
        $boundary = 'verifapp_' . md5((string) microtime(true) . (string) mt_rand());
        $multipartBody = $this->buildMultipartBody($boundary, $bodyText, $bodyHtml);
        $payload = implode("\r\n", [
            'Date: ' . date('r'),
            'From: ' . $displayFrom,
            'To: <' . $toEmail . '>',
            'Subject: ' . $encodedSubject,
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
            'Content-Transfer-Encoding: 8bit',
            '',
            str_replace(["\r\n", "\r"], "\n", $multipartBody),
        ]);
        $payload = str_replace("\n", "\r\n", $payload);
        fwrite($socket, $payload . "\r\n.\r\n");
        if (!$this->smtpExpect($socket, [250])) {
            $this->lastEmailError = 'SMTP: echec validation du message.';
            fclose($socket);
            return false;
        }

        $this->smtpCommand($socket, 'QUIT', [221]);
        fclose($socket);

        return true;
    }

    /**
     * @param array<string, mixed> $context
     * @return array{text:string,html:string}
     */
    private function buildEmailBodies(int $caserneId, string $eventCode, string $title, string $message, ?string $link, array $context, string $appUrl): array
    {
        $caserneLabel = $this->resolveCaserneLabel($caserneId);
        $href = null;
        if ($link !== null && trim($link) !== '') {
            $href = trim($link);
            if (str_starts_with($href, '/')) {
                $href = $appUrl !== '' ? ($appUrl . $href) : $href;
            }
        }

        if ($eventCode === 'anomaly.created') {
            return $this->buildAnomalyEmailBodies($caserneLabel, $title, $message, $href, $context);
        }

        if ($eventCode === 'pharmacy.output.created' && isset($context['lines']) && is_array($context['lines'])) {
            $declarant = trim((string) ($context['declarant'] ?? ''));
            $textRows = [];
            $htmlRows = [];
            foreach ($context['lines'] as $line) {
                if (!is_array($line)) {
                    continue;
                }
                $article = trim((string) ($line['article'] ?? 'Article'));
                $quantity = isset($line['quantity']) ? (float) $line['quantity'] : 0.0;
                $comment = trim((string) ($line['comment'] ?? ''));
                $quantityLabel = $this->formatQuantity($quantity);
                $textRows[] = '- ' . $article . ' : ' . $quantityLabel . ($comment !== '' ? (' | ' . $comment) : '');
                $htmlRows[] = '<tr>'
                    . '<td style="padding:8px;border:1px solid #dbe5f1;">' . $this->escapeHtml($article) . '</td>'
                    . '<td style="padding:8px;border:1px solid #dbe5f1;text-align:right;white-space:nowrap;">' . $this->escapeHtml($quantityLabel) . '</td>'
                    . '<td style="padding:8px;border:1px solid #dbe5f1;">' . ($comment !== '' ? $this->escapeHtml($comment) : '-') . '</td>'
                    . '</tr>';
            }

            $textLines = [
                trim($title),
                '',
                trim($message),
            ];
            if ($declarant !== '') {
                $textLines[] = 'Declarant: ' . $declarant;
            }
            if ($textRows !== []) {
                $textLines[] = '';
                $textLines[] = 'Detail des articles:';
                foreach ($textRows as $row) {
                    $textLines[] = $row;
                }
            }
            if ($href !== null) {
                $textLines[] = '';
                $textLines[] = 'Ouvrir les sorties: ' . $href;
            }
            $textLines[] = '';
            $textLines[] = 'Caserne: ' . $caserneLabel;

            $html = '<!doctype html><html><body style="margin:0;padding:16px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">'
                . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #dbe5f1;border-radius:12px;overflow:hidden;">'
                . '<tr><td style="padding:16px 20px;background:#0f172a;color:#ffffff;">'
                . '<div style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;">VerifApp</div>'
                . '<div style="font-size:22px;font-weight:700;margin-top:6px;">' . $this->escapeHtml($title) . '</div>'
                . '</td></tr>'
                . '<tr><td style="padding:16px 20px;">'
                . '<p style="margin:0 0 8px 0;font-size:15px;line-height:1.5;">' . $this->escapeHtml($message) . '</p>'
                . ($declarant !== '' ? ('<p style="margin:0 0 14px 0;font-size:14px;color:#334155;"><strong>Declarant:</strong> ' . $this->escapeHtml($declarant) . '</p>') : '')
                . ($htmlRows !== [] ? ('<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">'
                    . '<thead><tr>'
                    . '<th align="left" style="padding:8px;border:1px solid #dbe5f1;background:#f8fafc;">Article</th>'
                    . '<th align="right" style="padding:8px;border:1px solid #dbe5f1;background:#f8fafc;">Quantite</th>'
                    . '<th align="left" style="padding:8px;border:1px solid #dbe5f1;background:#f8fafc;">Commentaire</th>'
                    . '</tr></thead><tbody>' . implode('', $htmlRows) . '</tbody></table>') : '')
                . ($href !== null ? ('<p style="margin:16px 0 0 0;"><a href="' . $this->escapeHtml($href) . '" style="display:inline-block;padding:10px 14px;border-radius:8px;background:#0f172a;color:#ffffff;text-decoration:none;font-weight:600;">Ouvrir les sorties</a></p>') : '')
                . '<p style="margin:16px 0 0 0;font-size:12px;color:#64748b;">Caserne: <strong>' . $this->escapeHtml($caserneLabel) . '</strong></p>'
                . '</td></tr></table></body></html>';

            return [
                'text' => implode("\r\n", $textLines),
                'html' => $html,
            ];
        }

        $bodyLines = [
            trim($title),
            '',
            trim($message),
        ];
        if ($href !== null) {
            $bodyLines[] = '';
            $bodyLines[] = 'Lien: ' . $href;
        }
        $bodyLines[] = '';
        $bodyLines[] = 'Caserne: ' . $caserneLabel;

        $fallbackText = implode("\r\n", $bodyLines);
        $fallbackHtml = '<!doctype html><html><body style="font-family:Arial,Helvetica,sans-serif;color:#0f172a;">'
            . '<h2 style="margin:0 0 12px 0;">' . $this->escapeHtml($title) . '</h2>'
            . '<p style="margin:0 0 8px 0;">' . nl2br($this->escapeHtml($message)) . '</p>'
            . ($href !== null ? ('<p style="margin:8px 0;"><a href="' . $this->escapeHtml($href) . '">Ouvrir le lien</a></p>') : '')
            . '<p style="margin:8px 0 0 0;color:#64748b;font-size:12px;">Caserne: <strong>' . $this->escapeHtml($caserneLabel) . '</strong></p>'
            . '</body></html>';

        return [
            'text' => $fallbackText,
            'html' => $fallbackHtml,
        ];
    }

    /**
     * @param array<string, mixed> $context
     * @return array{text:string,html:string}
     */
    private function buildAnomalyEmailBodies(
        string $caserneLabel,
        string $title,
        string $message,
        ?string $href,
        array $context
    ): array {
        $vehicle = trim((string) ($context['vehicle'] ?? 'Engin non renseigne'));
        $poste = trim((string) ($context['poste'] ?? 'Poste non renseigne'));
        $notifier = trim((string) ($context['notifier'] ?? 'Non renseigne'));
        $globalComment = trim((string) ($context['global_comment'] ?? ''));
        $anomalies = isset($context['anomalies']) && is_array($context['anomalies'])
            ? $context['anomalies']
            : [];

        $textRows = [];
        $htmlRows = [];
        foreach ($anomalies as $anomaly) {
            if (!is_array($anomaly)) {
                continue;
            }
            $label = trim((string) ($anomaly['label'] ?? 'Controle non conforme'));
            $zone = trim((string) ($anomaly['zone'] ?? ''));
            $value = trim((string) ($anomaly['value'] ?? ''));
            $comment = trim((string) ($anomaly['comment'] ?? ''));
            $details = [];
            if ($value !== '') {
                $details[] = 'Valeur: ' . $value;
            }
            if ($comment !== '') {
                $details[] = $comment;
            }
            $detailText = $details !== [] ? implode(' | ', $details) : 'Non-conforme';
            $textRows[] = '- ' . $label
                . ($zone !== '' ? ' (' . $zone . ')' : '')
                . ' : ' . $detailText;
            $htmlRows[] = '<tr>'
                . '<td style="padding:10px;border:1px solid #dbe5f1;font-weight:700;">' . $this->escapeHtml($label) . '</td>'
                . '<td style="padding:10px;border:1px solid #dbe5f1;">' . ($zone !== '' ? $this->escapeHtml($zone) : '-') . '</td>'
                . '<td style="padding:10px;border:1px solid #dbe5f1;">'
                . ($value !== '' ? ('<div><strong>Valeur:</strong> ' . $this->escapeHtml($value) . '</div>') : '')
                . ($comment !== '' ? ('<div' . ($value !== '' ? ' style="margin-top:4px;"' : '') . '>' . $this->escapeHtml($comment) . '</div>') : '')
                . ($value === '' && $comment === '' ? 'Non&#8209;conforme' : '')
                . '</td>'
                . '</tr>';
        }

        if ($textRows === []) {
            $textRows[] = '- Une non-conformite a ete signalee.';
            $htmlRows[] = '<tr><td colspan="3" style="padding:10px;border:1px solid #dbe5f1;">Une non-conformite a ete signalee.</td></tr>';
        }

        $textLines = [
            trim($title),
            '',
            trim($message),
            '',
            'Engin: ' . $vehicle,
            'Poste: ' . $poste,
            'Signale par: ' . $notifier,
            '',
            'Anomalies:',
            ...$textRows,
        ];
        if ($globalComment !== '') {
            $textLines[] = '';
            $textLines[] = 'Commentaire general: ' . $globalComment;
        }
        if ($href !== null) {
            $textLines[] = '';
            $textLines[] = 'Voir les anomalies: ' . $href;
        }
        $textLines[] = '';
        $textLines[] = 'Caserne: ' . $caserneLabel;

        $html = '<!doctype html><html><body style="margin:0;padding:16px;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #dbe5f1;border-radius:8px;overflow:hidden;">'
            . '<tr><td style="padding:18px 20px;background:#0f172a;color:#ffffff;">'
            . '<div style="font-size:12px;letter-spacing:0.08em;text-transform:uppercase;opacity:0.8;">VerifApp - Anomalie terrain</div>'
            . '<div style="font-size:21px;font-weight:700;margin-top:6px;">' . $this->escapeHtml($title) . '</div>'
            . '</td></tr>'
            . '<tr><td style="padding:18px 20px;">'
            . '<p style="margin:0 0 16px 0;font-size:15px;line-height:1.5;">' . $this->escapeHtml($message) . '</p>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:16px;border-collapse:collapse;font-size:14px;">'
            . '<tr><td style="width:120px;padding:7px 0;color:#64748b;">Engin</td><td style="padding:7px 0;font-weight:700;">' . $this->escapeHtml($vehicle) . '</td></tr>'
            . '<tr><td style="width:120px;padding:7px 0;color:#64748b;">Poste</td><td style="padding:7px 0;font-weight:700;">' . $this->escapeHtml($poste) . '</td></tr>'
            . '<tr><td style="width:120px;padding:7px 0;color:#64748b;">Signale par</td><td style="padding:7px 0;font-weight:700;">' . $this->escapeHtml($notifier) . '</td></tr>'
            . '</table>'
            . '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;font-size:14px;">'
            . '<thead><tr>'
            . '<th align="left" style="padding:9px 10px;border:1px solid #dbe5f1;background:#f8fafc;">Anomalie</th>'
            . '<th align="left" style="padding:9px 10px;border:1px solid #dbe5f1;background:#f8fafc;">Emplacement</th>'
            . '<th align="left" style="padding:9px 10px;border:1px solid #dbe5f1;background:#f8fafc;">Detail terrain</th>'
            . '</tr></thead><tbody>' . implode('', $htmlRows) . '</tbody></table>'
            . ($globalComment !== '' ? ('<div style="margin-top:14px;padding:10px 12px;border-left:4px solid #94a3b8;background:#f8fafc;font-size:14px;"><strong>Commentaire general:</strong><br>' . nl2br($this->escapeHtml($globalComment)) . '</div>') : '')
            . ($href !== null ? ('<p style="margin:18px 0 0 0;"><a href="' . $this->escapeHtml($href) . '" style="display:inline-block;padding:10px 14px;border-radius:6px;background:#0f172a;color:#ffffff;text-decoration:none;font-weight:700;">Voir les anomalies</a></p>') : '')
            . '<p style="margin:18px 0 0 0;font-size:12px;color:#64748b;">Caserne: <strong>' . $this->escapeHtml($caserneLabel) . '</strong></p>'
            . '</td></tr></table></body></html>';

        return [
            'text' => implode("\r\n", $textLines),
            'html' => $html,
        ];
    }

    private function buildMultipartBody(string $boundary, string $bodyText, string $bodyHtml): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $bodyText);
        $html = str_replace(["\r\n", "\r"], "\n", $bodyHtml);

        $lines = [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $text,
            '--' . $boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            '',
            $html,
            '--' . $boundary . '--',
            '',
        ];

        return implode("\r\n", $lines);
    }

    private function formatQuantity(float $value): string
    {
        if (abs($value - round($value)) < 0.00001) {
            return (string) (int) round($value) . ' u';
        }

        return number_format($value, 2, ',', ' ') . ' u';
    }

    private function escapeHtml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private function resolveCaserneLabel(int $caserneId): string
    {
        if ($caserneId <= 0) {
            return 'Non definie';
        }

        try {
            $caserne = (new CaserneRepository())->findById($caserneId);
            if (is_array($caserne)) {
                $name = trim((string) ($caserne['nom'] ?? ''));
                if ($name !== '') {
                    return $name;
                }
            }
        } catch (\Throwable $throwable) {
            // silent fallback on ID label
        }

        return '#' . $caserneId;
    }

    /**
     * @param resource $socket
     * @param array<int, int> $expectedCodes
     */
    private function smtpCommand($socket, string $command, array $expectedCodes): bool
    {
        fwrite($socket, $command . "\r\n");

        return $this->smtpExpect($socket, $expectedCodes);
    }

    /**
     * @param resource $socket
     * @param array<int, int> $expectedCodes
     */
    private function smtpExpect($socket, array $expectedCodes): bool
    {
        $lastCode = null;
        while (($line = fgets($socket, 515)) !== false) {
            $line = rtrim($line, "\r\n");
            if (strlen($line) < 3 || !ctype_digit(substr($line, 0, 3))) {
                continue;
            }
            $lastCode = (int) substr($line, 0, 3);
            $isLastLine = strlen($line) >= 4 ? $line[3] !== '-' : true;
            if ($isLastLine) {
                break;
            }
        }

        return $lastCode !== null && in_array($lastCode, $expectedCodes, true);
    }

    /**
     * @return array{enabled: bool, roles: array<int, string>, users: array<int, int>, groups: array<int, int>, in_app_enabled: bool, email_enabled: bool}
     */
    private function readEventSettings(?int $caserneId, string $eventCode): array
    {
        $catalog = self::eventCatalog();
        $meta = $catalog[$eventCode] ?? null;
        if ($meta === null) {
            return [
                'enabled' => false,
                'roles' => [],
                'users' => [],
                'groups' => [],
                'in_app_enabled' => true,
                'email_enabled' => false,
            ];
        }

        $eventKey = str_replace('.', '_', $eventCode);
        $defaultRoles = $this->normalizeRoles((array) ($meta['default_roles'] ?? []));
        $enabledRaw = $this->readScopedSetting('notifications_event_' . $eventKey . '_enabled', $caserneId, '1');
        $rolesRaw = $this->readScopedSetting('notifications_event_' . $eventKey . '_roles', $caserneId, implode(',', $defaultRoles));
        $usersRaw = $this->readScopedSetting('notifications_event_' . $eventKey . '_users', $caserneId, '');
        $groupsRaw = $this->readScopedSetting('notifications_event_' . $eventKey . '_groups', $caserneId, '');
        $inAppRaw = $this->readScopedSetting('notifications_channel_in_app_enabled', $caserneId, '1');
        $emailRaw = $this->readScopedSetting('notifications_channel_email_enabled', $caserneId, '0');

        $roles = $this->normalizeRoles(explode(',', $rolesRaw));
        $users = $this->normalizeUserIds(explode(',', $usersRaw));
        if ($roles === [] && $users === [] && trim($rolesRaw) === '' && trim($usersRaw) === '') {
            $roles = $defaultRoles;
        }

        return [
            'enabled' => $enabledRaw !== '0',
            'roles' => $roles,
            'users' => $users,
            'groups' => $this->normalizeUserIds(explode(',', $groupsRaw)),
            'in_app_enabled' => $inAppRaw !== '0',
            'email_enabled' => $emailRaw === '1',
        ];
    }

    private function readScopedSetting(string $key, ?int $caserneId, string $default): string
    {
        $settingRepository = new AppSettingRepository();
        if (!$settingRepository->isAvailable()) {
            return $default;
        }

        if ($caserneId !== null && $caserneId > 0) {
            $scoped = $settingRepository->get($key . '_caserne_' . $caserneId);
            if ($scoped !== null && trim($scoped) !== '') {
                return trim($scoped);
            }
        }

        $global = $settingRepository->get($key);
        if ($global !== null && trim($global) !== '') {
            return trim($global);
        }

        return $default;
    }

    /**
     * @param array<int, string> $roles
     * @return array<int, string>
     */
    private function normalizeRoles(array $roles): array
    {
        $normalized = [];
        foreach ($roles as $roleCode) {
            $value = strtolower(trim((string) $roleCode));
            if ($value === '' || $value === '__none__') {
                continue;
            }
            $normalized[$value] = $value;
        }

        return array_values($normalized);
    }

    /**
     * @param array<int, int|string> $userIds
     * @return array<int, int>
     */
    private function normalizeUserIds(array $userIds): array
    {
        $normalized = [];
        foreach ($userIds as $userId) {
            $value = (int) $userId;
            if ($value > 0) {
                $normalized[$value] = $value;
            }
        }

        return array_values($normalized);
    }

    private function findUserIdsForGroups(int $caserneId, array $groupIds): array
    {
        $groupIds = $this->normalizeUserIds($groupIds);
        if ($caserneId <= 0 || $groupIds === []) {
            return [];
        }

        $params = ['caserne_id' => $caserneId];
        $placeholders = [];
        foreach ($groupIds as $index => $groupId) {
            $key = 'group_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $groupId;
        }

        try {
            $statement = Database::getConnection()->prepare('
                SELECT DISTINCT ngm.utilisateur_id
                FROM notification_group_members ngm
                INNER JOIN notification_groups ng ON ng.id = ngm.group_id
                WHERE ng.caserne_id = :caserne_id
                  AND ng.actif = 1
                  AND ng.id IN (' . implode(',', $placeholders) . ')
            ');
            $statement->execute($params);

            return array_values(array_filter(array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN) ?: [])));
        } catch (\Throwable) {
            return [];
        }
    }

    private function vehicleAnomalySettingPrefix(int $vehicleId): string
    {
        return 'notifications_anomaly_vehicle_' . $vehicleId;
    }

    private function hasNotificationsTable(): bool
    {
        if ($this->notificationsTableExists !== null) {
            return $this->notificationsTableExists;
        }

        $connection = Database::getConnection();
        try {
            $statement = $connection->query("SHOW TABLES LIKE 'notifications'");
            $this->notificationsTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->notificationsTableExists = false;
        }

        return $this->notificationsTableExists;
    }

    private function hasRecipientsTable(): bool
    {
        if ($this->recipientsTableExists !== null) {
            return $this->recipientsTableExists;
        }

        $connection = Database::getConnection();
        try {
            $statement = $connection->query("SHOW TABLES LIKE 'notification_recipients'");
            $this->recipientsTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->recipientsTableExists = false;
        }

        return $this->recipientsTableExists;
    }

    private function hasSubscriptionsTable(): bool
    {
        if ($this->subscriptionsTableExists !== null) {
            return $this->subscriptionsTableExists;
        }

        $connection = Database::getConnection();
        try {
            $statement = $connection->query("SHOW TABLES LIKE 'notification_subscriptions'");
            $this->subscriptionsTableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->subscriptionsTableExists = false;
        }

        return $this->subscriptionsTableExists;
    }
}
