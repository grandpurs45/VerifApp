<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;

final class ManagerAdminController
{
    public function menu(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;

        require dirname(__DIR__, 2) . '/public/views/manager_admin.php';
    }

    public function settings(): void
    {
        $managerUser = $_SESSION['manager_user'] ?? null;
        $sessionTimeout = (string) (Env::get('MANAGER_SESSION_TTL_MINUTES', '120') ?? '120');

        require dirname(__DIR__, 2) . '/public/views/manager_app_settings.php';
    }
}
