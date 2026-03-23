<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Database;
use PDO;

final class TypeVehiculeRepository
{
    public function findAll(): array
    {
        $connection = Database::getConnection();

        $sql = '
            SELECT
                id,
                nom
            FROM type_vehicules
            ORDER BY nom ASC
        ';

        $statement = $connection->query($sql);

        if ($statement === false) {
            return [];
        }

        return $statement->fetchAll(PDO::FETCH_ASSOC);
    }
}
