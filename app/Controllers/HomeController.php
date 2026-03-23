<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\VehicleRepository;

final class HomeController
{
    public function index(): void
    {
        $vehicleRepository = new VehicleRepository();
        $vehicles = $vehicleRepository->findAllActive();

        require dirname(__DIR__, 2) . '/public/views/home.php';
    }
}