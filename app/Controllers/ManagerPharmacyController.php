<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PharmacyRepository;

final class ManagerPharmacyController
{
    public function index(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $repository = new PharmacyRepository();
        $articles = $repository->findAllArticles($caserneId);
        $movementGroups = $repository->findLastOutputGroups($caserneId, 10);
        $stats = $repository->getStats($caserneId);
        $isAvailable = $repository->isAvailable();
        $managerUser = $_SESSION['manager_user'] ?? null;

        require dirname(__DIR__, 2) . '/public/views/manager_pharmacy.php';
    }

    public function outputs(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $repository = new PharmacyRepository();
        $isAvailable = $repository->isAvailable();
        $filters = [
            'date_from' => isset($_GET['date_from']) ? (string) $_GET['date_from'] : '',
            'date_to' => isset($_GET['date_to']) ? (string) $_GET['date_to'] : '',
            'article' => isset($_GET['article']) ? trim((string) $_GET['article']) : '',
            'declarant' => isset($_GET['declarant']) ? trim((string) $_GET['declarant']) : '',
        ];
        $movementGroups = $repository->findOutputGroups($caserneId, $filters, 120);
        $managerUser = $_SESSION['manager_user'] ?? null;

        require dirname(__DIR__, 2) . '/public/views/manager_pharmacy_outputs.php';
    }

    public function articleSave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));
        $unit = trim((string) ($_POST['unite'] ?? 'u'));
        $stockRaw = str_replace(',', '.', trim((string) ($_POST['stock_actuel'] ?? '0')));
        $alertRaw = str_replace(',', '.', trim((string) ($_POST['seuil_alerte'] ?? '')));
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';

        $stockValue = $this->parseNonNegativeInteger($stockRaw);
        if ($name === '' || $unit === '' || $stockValue === null) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=invalid_article');
        }

        $alertThreshold = null;
        if ($alertRaw !== '') {
            $alertValue = $this->parseNonNegativeInteger($alertRaw);
            if ($alertValue === null) {
                $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=invalid_article');
            }
            $alertThreshold = (float) $alertValue;
            if ($alertThreshold <= 0) {
                $alertThreshold = null;
            }
        }

        $repository = new PharmacyRepository();
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_save_failed');
        }
        $ok = $repository->saveArticle(
            $caserneId,
            $id,
            $name,
            $unit,
            (float) $stockValue,
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

    private function resolveManagerCaserneId(): ?int
    {
        $caserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;

        return $caserneId > 0 ? $caserneId : null;
    }

    private function parseNonNegativeInteger(string $raw): ?int
    {
        if ($raw === '') {
            return null;
        }

        $normalized = str_replace(',', '.', trim($raw));
        if (!is_numeric($normalized)) {
            return null;
        }

        $value = (float) $normalized;
        if ($value < 0) {
            return null;
        }

        if (abs($value - round($value)) > 0.00001) {
            return null;
        }

        return (int) round($value);
    }
}
