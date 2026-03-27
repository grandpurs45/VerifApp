<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PharmacyRepository;

final class ManagerPharmacyController
{
    public function index(): void
    {
        $repository = new PharmacyRepository();
        $articles = $repository->findAllArticles();
        $movements = $repository->findLastMovements(80);
        $stats = $repository->getStats();
        $isAvailable = $repository->isAvailable();
        $managerUser = $_SESSION['manager_user'] ?? null;

        require dirname(__DIR__, 2) . '/public/views/manager_pharmacy.php';
    }

    public function articleSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));
        $unit = trim((string) ($_POST['unite'] ?? 'u'));
        $stockRaw = trim((string) ($_POST['stock_actuel'] ?? '0'));
        $alertRaw = trim((string) ($_POST['seuil_alerte'] ?? ''));
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';

        if ($name === '' || $unit === '' || !is_numeric($stockRaw) || (float) $stockRaw < 0) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=invalid_article');
        }

        $alertThreshold = null;
        if ($alertRaw !== '') {
            if (!is_numeric($alertRaw) || (float) $alertRaw < 0) {
                $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=invalid_article');
            }
            $alertThreshold = (float) $alertRaw;
        }

        $repository = new PharmacyRepository();
        $ok = $repository->saveArticle(
            $id,
            $name,
            $unit,
            (float) $stockRaw,
            $alertThreshold,
            $active
        );

        if (!$ok) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_save_failed');
        }

        $this->redirect('/index.php?controller=manager_pharmacy&action=index&success=article_saved');
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }
}
