<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\PharmacyRepository;

final class PharmacyController
{
    public function access(): void
    {
        $configuredToken = $this->getPharmacyToken();
        $providedToken = isset($_GET['token']) ? (string) $_GET['token'] : '';

        if ($configuredToken === '' || hash_equals($configuredToken, $providedToken)) {
            $_SESSION['pharmacy_access'] = true;
            $this->redirect('/index.php?controller=pharmacy&action=form');
        }

        $this->redirect('/index.php?controller=pharmacy&action=denied');
    }

    public function form(): void
    {
        $repository = new PharmacyRepository();
        $isAvailable = $repository->isAvailable();
        $articles = $repository->findAllArticles(true);
        $success = isset($_GET['success']) && (string) $_GET['success'] === '1';
        $successItems = isset($_GET['items']) && ctype_digit((string) $_GET['items']) ? (int) $_GET['items'] : 0;
        $errorCode = isset($_GET['error']) ? (string) $_GET['error'] : '';

        require dirname(__DIR__, 2) . '/public/views/pharmacy_form.php';
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=pharmacy&action=form');
        }

        $articleIds = is_array($_POST['article_id'] ?? null) ? $_POST['article_id'] : [];
        $quantities = is_array($_POST['quantite'] ?? null) ? $_POST['quantite'] : [];
        $comments = is_array($_POST['commentaire_ligne'] ?? null) ? $_POST['commentaire_ligne'] : [];
        $declarant = trim((string) ($_POST['declarant'] ?? ''));
        $lines = [];

        $maxRows = max(count($articleIds), count($quantities), count($comments));
        for ($index = 0; $index < $maxRows; $index++) {
            $articleRaw = isset($articleIds[$index]) ? trim((string) $articleIds[$index]) : '';
            $quantityRaw = isset($quantities[$index]) ? trim((string) $quantities[$index]) : '';
            $comment = isset($comments[$index]) ? trim((string) $comments[$index]) : '';

            if ($articleRaw === '' && $quantityRaw === '' && $comment === '') {
                continue;
            }

            if (!ctype_digit($articleRaw) || $quantityRaw === '' || !is_numeric($quantityRaw) || (float) $quantityRaw <= 0) {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
            }

            $lines[] = [
                'article_id' => (int) $articleRaw,
                'quantite' => (float) $quantityRaw,
                'commentaire' => $comment === '' ? null : $comment,
            ];
        }

        if ($lines === []) {
            $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
        }

        $repository = new PharmacyRepository();
        $ok = $repository->recordOutputs($lines, $declarant);

        if (!$ok) {
            $this->redirect('/index.php?controller=pharmacy&action=form&error=stock');
        }

        $this->redirect('/index.php?controller=pharmacy&action=form&success=1&items=' . count($lines));
    }

    public function denied(): void
    {
        require dirname(__DIR__, 2) . '/public/views/pharmacy_denied.php';
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    private function getPharmacyToken(): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable()) {
            $token = $repository->get('pharmacy_qr_token');
            if ($token !== null && trim($token) !== '') {
                return trim($token);
            }
        }

        return trim((string) (Env::get('PHARMACY_QR_TOKEN', '') ?? ''));
    }
}
