<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\CaserneRepository;

final class FieldController
{
    public function access(): void
    {
        $configuredToken = $this->getFieldToken();
        $providedToken = isset($_GET['token']) ? (string) $_GET['token'] : '';
        $caserneId = isset($_GET['caserne_id']) ? (int) $_GET['caserne_id'] : 0;

        if ($configuredToken === '' || hash_equals($configuredToken, $providedToken)) {
            $_SESSION['field_access'] = true;
            $this->storeFieldCaserneContext($caserneId);
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

    private function getFieldToken(): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable()) {
            $token = $repository->get('field_qr_token');
            if ($token !== null && trim($token) !== '') {
                return trim($token);
            }
        }

        return trim((string) (Env::get('FIELD_QR_TOKEN', '') ?? ''));
    }

    private function storeFieldCaserneContext(int $caserneId): void
    {
        if ($caserneId > 0) {
            $caserneRepository = new CaserneRepository();
            $caserne = $caserneRepository->findById($caserneId);
            if ($caserne !== null && (int) ($caserne['actif'] ?? 0) === 1) {
                $_SESSION['field_caserne_id'] = (int) $caserne['id'];
                $_SESSION['field_caserne_nom'] = (string) $caserne['nom'];
                return;
            }
        }

        unset($_SESSION['field_caserne_id'], $_SESSION['field_caserne_nom']);
    }
}
