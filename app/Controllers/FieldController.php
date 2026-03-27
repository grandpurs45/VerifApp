<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;

final class FieldController
{
    public function access(): void
    {
        $configuredToken = (string) (Env::get('FIELD_QR_TOKEN', '') ?? '');
        $providedToken = isset($_GET['token']) ? (string) $_GET['token'] : '';

        if ($configuredToken === '' || hash_equals($configuredToken, $providedToken)) {
            $_SESSION['field_access'] = true;
            $this->redirect('/index.php?controller=home&action=index');
        }

        $this->redirect('/index.php?controller=field&action=denied');
    }

    public function denied(): void
    {
        require dirname(__DIR__, 2) . '/public/views/field_denied.php';
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
