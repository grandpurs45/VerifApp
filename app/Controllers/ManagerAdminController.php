<?php

declare(strict_types=1);

namespace App\Controllers;

final class ManagerAdminController
{
    public function menu(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;

        require dirname(__DIR__, 2) . '/public/views/manager_admin.php';
    }
}
