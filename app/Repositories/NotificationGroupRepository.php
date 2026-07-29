<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;
use Throwable;

final class NotificationGroupRepository
{
    public function findAll(int $caserneId): array
    {
        if ($caserneId <= 0) {
            return [];
        }

        $statement = Database::getConnection()->prepare('
            SELECT
                ng.id,
                ng.nom,
                ng.actif,
                GROUP_CONCAT(ngm.utilisateur_id ORDER BY ngm.utilisateur_id) AS member_ids,
                COUNT(ngm.utilisateur_id) AS member_count
            FROM notification_groups ng
            LEFT JOIN notification_group_members ngm ON ngm.group_id = ng.id
            WHERE ng.caserne_id = :caserne_id
            GROUP BY ng.id, ng.nom, ng.actif
            ORDER BY ng.nom
        ');
        $statement->execute(['caserne_id' => $caserneId]);

        $groups = $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($groups as &$group) {
            $rawIds = trim((string) ($group['member_ids'] ?? ''));
            $group['member_ids'] = $rawIds === ''
                ? []
                : array_values(array_filter(array_map('intval', explode(',', $rawIds))));
        }
        unset($group);

        return $groups;
    }

    public function save(int $caserneId, int $groupId, string $name, array $userIds): bool
    {
        $name = trim($name);
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if ($caserneId <= 0 || $name === '' || mb_strlen($name) > 120) {
            return false;
        }

        $connection = Database::getConnection();
        try {
            $connection->beginTransaction();
            if ($groupId > 0) {
                $statement = $connection->prepare('
                    UPDATE notification_groups
                    SET nom = :nom, actif = 1
                    WHERE id = :id AND caserne_id = :caserne_id
                ');
                $statement->execute(['id' => $groupId, 'caserne_id' => $caserneId, 'nom' => $name]);
                if ($statement->rowCount() === 0) {
                    $check = $connection->prepare('SELECT 1 FROM notification_groups WHERE id = :id AND caserne_id = :caserne_id');
                    $check->execute(['id' => $groupId, 'caserne_id' => $caserneId]);
                    if ($check->fetchColumn() === false) {
                        $connection->rollBack();
                        return false;
                    }
                }
            } else {
                $statement = $connection->prepare('INSERT INTO notification_groups (caserne_id, nom) VALUES (:caserne_id, :nom)');
                $statement->execute(['caserne_id' => $caserneId, 'nom' => $name]);
                $groupId = (int) $connection->lastInsertId();
            }

            $allowed = $connection->prepare('
                SELECT u.id
                FROM utilisateurs u
                INNER JOIN utilisateur_casernes uc ON uc.utilisateur_id = u.id
                WHERE uc.caserne_id = :caserne_id AND u.actif = 1
            ');
            $allowed->execute(['caserne_id' => $caserneId]);
            $allowedIds = array_fill_keys(array_map('intval', $allowed->fetchAll(PDO::FETCH_COLUMN) ?: []), true);

            $delete = $connection->prepare('DELETE FROM notification_group_members WHERE group_id = :group_id');
            $delete->execute(['group_id' => $groupId]);
            $insert = $connection->prepare('
                INSERT INTO notification_group_members (group_id, utilisateur_id)
                VALUES (:group_id, :utilisateur_id)
            ');
            foreach ($userIds as $userId) {
                if (isset($allowedIds[$userId])) {
                    $insert->execute(['group_id' => $groupId, 'utilisateur_id' => $userId]);
                }
            }

            $connection->commit();
            return true;
        } catch (Throwable) {
            if ($connection->inTransaction()) {
                $connection->rollBack();
            }
            return false;
        }
    }

    public function delete(int $caserneId, int $groupId): bool
    {
        if ($caserneId <= 0 || $groupId <= 0) {
            return false;
        }

        $statement = Database::getConnection()->prepare(
            'DELETE FROM notification_groups WHERE id = :id AND caserne_id = :caserne_id'
        );
        $statement->execute(['id' => $groupId, 'caserne_id' => $caserneId]);

        return $statement->rowCount() > 0;
    }
}
