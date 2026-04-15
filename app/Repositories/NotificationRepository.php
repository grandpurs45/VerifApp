<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class NotificationRepository
{
    private ?bool $notificationsTableExists = null;
    private ?bool $recipientsTableExists = null;
    private ?bool $subscriptionsTableExists = null;

    /**
     * @return array<string, array{label: string, description: string, default_roles: array<int, string>}>
     */
    public static function eventCatalog(): array
    {
        return [
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
        ?string $actorName = null
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
        if (!$settings['enabled'] || !$settings['in_app_enabled']) {
            return true;
        }

        $recipients = $this->findRecipientUserIds($caserneId, $eventCode, $settings['roles'], $actorUserId);
        if ($recipients === []) {
            return true;
        }

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
            foreach ($recipients as $userId) {
                $insertRecipient->execute([
                    'notification_id' => $notificationId,
                    'utilisateur_id' => $userId,
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
            UPDATE notification_recipients nr
            INNER JOIN notifications n ON n.id = nr.notification_id
            SET nr.lu = 1, nr.lu_le = NOW()
            WHERE nr.utilisateur_id = :utilisateur_id
              AND nr.lu = 0
        ';
        if ($caserneId !== null && $caserneId > 0) {
            $sql .= ' AND n.caserne_id = :caserne_id';
            $params['caserne_id'] = $caserneId;
        }

        $statement = $connection->prepare($sql);

        return $statement->execute($params);
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
                'email_enabled' => false,
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
    public function saveSubscriptionsByUser(int $userId, array $inAppByEvent): bool
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
                    'email_enabled' => 0,
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
     * @return array<string, array{enabled: bool, roles: array<int, string>, in_app_enabled: bool, email_enabled: bool}>
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
     * @param array<string, bool> $enabledByEvent
     * @param array<string, array<int, string>> $rolesByEvent
     */
    public function saveAdminSettings(
        ?int $caserneId,
        bool $inAppEnabled,
        bool $emailEnabled,
        array $enabledByEvent,
        array $rolesByEvent
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
            if ($selectedRoles === []) {
                $selectedRoles = $defaultRoles;
            }

            $saveMap['notifications_event_' . $eventKey . '_enabled' . $suffix] =
                (isset($enabledByEvent[$eventCode]) && $enabledByEvent[$eventCode]) ? '1' : '0';
            $saveMap['notifications_event_' . $eventKey . '_roles' . $suffix] = implode(',', $selectedRoles);
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
     * @return array<int, int>
     */
    private function findRecipientUserIds(int $caserneId, string $eventCode, array $targetRoles, ?int $excludeUserId = null): array
    {
        if ($targetRoles === []) {
            return [];
        }

        $roles = $this->normalizeRoles($targetRoles);
        if ($roles === []) {
            return [];
        }

        $connection = Database::getConnection();
        $params = [
            'caserne_id' => $caserneId,
            'event_code' => $eventCode,
        ];
        $placeholders = [];
        foreach ($roles as $index => $roleCode) {
            $key = 'role_' . $index;
            $placeholders[] = 'CONVERT(:' . $key . ' USING utf8mb4) COLLATE utf8mb4_unicode_ci';
            $params[$key] = $roleCode;
        }

        $sql = '
            SELECT DISTINCT u.id
            FROM utilisateurs u
            INNER JOIN utilisateur_casernes uc ON uc.utilisateur_id = u.id
            LEFT JOIN notification_subscriptions ns
                   ON ns.utilisateur_id = u.id
                  AND ns.event_code = :event_code
            WHERE u.actif = 1
              AND uc.caserne_id = :caserne_id
              AND LOWER(
                    TRIM(
                        COALESCE(
                            NULLIF(CONVERT(uc.role_code USING utf8mb4) COLLATE utf8mb4_unicode_ci, \'\'),
                            CONVERT(u.role USING utf8mb4) COLLATE utf8mb4_unicode_ci
                        )
                    )
                ) COLLATE utf8mb4_unicode_ci IN (' . implode(',', $placeholders) . ')
              AND COALESCE(ns.in_app_enabled, 1) = 1
        ';
        if ($excludeUserId !== null && $excludeUserId > 0) {
            $sql .= ' AND u.id <> :exclude_user_id';
            $params['exclude_user_id'] = $excludeUserId;
        }

        $statement = $connection->prepare($sql);
        $statement->execute($params);
        $rows = $statement->fetchAll(PDO::FETCH_COLUMN) ?: [];

        $result = [];
        foreach ($rows as $value) {
            $id = (int) $value;
            if ($id > 0) {
                $result[$id] = $id;
            }
        }

        return array_values($result);
    }

    /**
     * @return array{enabled: bool, roles: array<int, string>, in_app_enabled: bool, email_enabled: bool}
     */
    private function readEventSettings(?int $caserneId, string $eventCode): array
    {
        $catalog = self::eventCatalog();
        $meta = $catalog[$eventCode] ?? null;
        if ($meta === null) {
            return [
                'enabled' => false,
                'roles' => [],
                'in_app_enabled' => true,
                'email_enabled' => false,
            ];
        }

        $eventKey = str_replace('.', '_', $eventCode);
        $defaultRoles = $this->normalizeRoles((array) ($meta['default_roles'] ?? []));
        $enabledRaw = $this->readScopedSetting('notifications_event_' . $eventKey . '_enabled', $caserneId, '1');
        $rolesRaw = $this->readScopedSetting('notifications_event_' . $eventKey . '_roles', $caserneId, implode(',', $defaultRoles));
        $inAppRaw = $this->readScopedSetting('notifications_channel_in_app_enabled', $caserneId, '1');
        $emailRaw = $this->readScopedSetting('notifications_channel_email_enabled', $caserneId, '0');

        $roles = $this->normalizeRoles(explode(',', $rolesRaw));
        if ($roles === []) {
            $roles = $defaultRoles;
        }

        return [
            'enabled' => $enabledRaw !== '0',
            'roles' => $roles,
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
            if ($value === '') {
                continue;
            }
            $normalized[$value] = $value;
        }

        return array_values($normalized);
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
