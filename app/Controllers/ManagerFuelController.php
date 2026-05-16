<?php

declare(strict_types=1);

namespace App\Controllers;

final class ManagerFuelController
{
    public function index(): void
    {
        require dirname(__DIR__, 2) . '/public/views/manager_fuel.php';
    }
}
