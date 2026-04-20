<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class LoginEventRepository
{
    private ?bool $tableExists = null;

    public function isAvailable(): bool
    {
        return $this->hasTable();
    }

    public function logEvent(
        ?int $caserneId,
        ?int $userId,
        string $identifier,
        string $ipAddress,
        string $userAgent,
        string $eventType,
        string $reason
    ): bool {
        if (!$this->hasTable()) {
            return false;
        }

        $eventType = strtolower(trim($eventType));
        if (!in_array($eventType, ['success', 'failure'], true)) {
            $eventType = 'failure';
        }

        $identifier = mb_substr(trim($identifier), 0, 190);
        if ($identifier === '') {
            $identifier = 'unknown';
        }

        $ipAddress = mb_substr(trim($ipAddress), 0, 64);
        if ($ipAddress === '') {
            $ipAddress = '0.0.0.0';
        }

        $userAgent = mb_substr(trim($userAgent), 0, 500);
        $reason = mb_substr(trim($reason), 0, 80);

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            INSERT INTO auth_login_events
                (caserne_id, user_id, identifier, ip_address, user_agent, event_type, reason)
            VALUES
                (:caserne_id, :user_id, :identifier, :ip_address, :user_agent, :event_type, :reason)
        ');

        return $statement->execute([
            'caserne_id' => $caserneId !== null && $caserneId > 0 ? $caserneId : null,
            'user_id' => $userId !== null && $userId > 0 ? $userId : null,
            'identifier' => $identifier,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'event_type' => $eventType,
            'reason' => $reason,
        ]);
    }

    public function findAll(array $filters, ?int $caserneId = null, int $limit = 300): array
    {
        if (!$this->hasTable()) {
            return [];
        }

        $where = [];
        $params = [];

        if ($caserneId !== null) {
            $where[] = '(
                e.caserne_id = :caserne_id
                OR (
                    e.caserne_id IS NULL
                    AND e.user_id IS NOT NULL
                    AND EXISTS (
                        SELECT 1
                        FROM utilisateur_casernes uc_scope
                        WHERE uc_scope.utilisateur_id = e.user_id
                          AND uc_scope.caserne_id = :caserne_id_scope
                    )
                )
            )';
            $params['caserne_id'] = $caserneId;
            $params['caserne_id_scope'] = $caserneId;
        }

        $dateFrom = trim((string) ($filters['date_from'] ?? ''));
        if ($dateFrom !== '') {
            $where[] = 'e.created_at >= :date_from';
            $params['date_from'] = $dateFrom . ' 00:00:00';
        }

        $dateTo = trim((string) ($filters['date_to'] ?? ''));
        if ($dateTo !== '') {
            $where[] = 'e.created_at <= :date_to';
            $params['date_to'] = $dateTo . ' 23:59:59';
        }

        $eventType = strtolower(trim((string) ($filters['event_type'] ?? '')));
        if (in_array($eventType, ['success', 'failure'], true)) {
            $where[] = 'e.event_type = :event_type';
            $params['event_type'] = $eventType;
        }

        $identifier = trim((string) ($filters['identifier'] ?? ''));
        if ($identifier !== '') {
            $where[] = 'e.identifier LIKE :identifier';
            $params['identifier'] = '%' . $identifier . '%';
        }

        $ipAddress = trim((string) ($filters['ip_address'] ?? ''));
        if ($ipAddress !== '') {
            $where[] = 'e.ip_address LIKE :ip_address';
            $params['ip_address'] = '%' . $ipAddress . '%';
        }

        $whereSql = $where !== [] ? 'WHERE ' . implode(' AND ', $where) : '';

        $connection = Database::getConnection();
        $statement = $connection->prepare('
            SELECT
                e.id,
                e.caserne_id,
                c.nom AS caserne_nom,
                e.user_id,
                u.nom AS user_nom,
                u.email AS user_email,
                e.identifier,
                e.ip_address,
                e.user_agent,
                e.event_type,
                e.reason,
                e.created_at
            FROM auth_login_events e
            LEFT JOIN casernes c ON c.id = e.caserne_id
            LEFT JOIN utilisateurs u ON u.id = e.user_id
            ' . $whereSql . '
            ORDER BY e.created_at DESC, e.id DESC
            LIMIT :limit
        ');
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value);
        }
        $statement->bindValue(':limit', max(1, min(2000, $limit)), PDO::PARAM_INT);
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    private function hasTable(): bool
    {
        if ($this->tableExists !== null) {
            return $this->tableExists;
        }

        try {
            $connection = Database::getConnection();
            $statement = $connection->query("SHOW TABLES LIKE 'auth_login_events'");
            $this->tableExists = $statement !== false && $statement->fetchColumn() !== false;
        } catch (\Throwable $throwable) {
            $this->tableExists = false;
        }

        return $this->tableExists;
    }
}
