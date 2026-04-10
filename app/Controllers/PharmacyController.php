<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\CaserneRepository;
use App\Repositories\PharmacyRepository;

final class PharmacyController
{
    public function access(): void
    {
        $caserneId = isset($_GET['caserne_id']) ? (int) $_GET['caserne_id'] : 0;
        $configuredToken = $this->getPharmacyToken($caserneId > 0 ? $caserneId : null);
        $providedToken = isset($_GET['token']) ? (string) $_GET['token'] : '';

        if ($configuredToken === '') {
            $_SESSION['pharmacy_access'] = true;
            $this->storePharmacyCaserneContext($caserneId);
            $this->redirect('/index.php?controller=pharmacy&action=form');
        }

        if ($caserneId <= 0) {
            $this->redirect('/index.php?controller=pharmacy&action=denied');
        }

        if (hash_equals($configuredToken, $providedToken)) {
            $_SESSION['pharmacy_access'] = true;
            $this->storePharmacyCaserneContext($caserneId);
            $this->redirect('/index.php?controller=pharmacy&action=form');
        }

        $this->redirect('/index.php?controller=pharmacy&action=denied');
    }

    public function form(): void
    {
        $caserneId = $this->resolvePharmacyCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=pharmacy&action=denied');
        }

        $repository = new PharmacyRepository();
        $isAvailable = $repository->isAvailable();
        $articles = $repository->findAllArticles($caserneId, true);
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

        $caserneId = $this->resolvePharmacyCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=pharmacy&action=denied');
        }

        $articleIds = is_array($_POST['article_id'] ?? null) ? $_POST['article_id'] : [];
        $quantities = is_array($_POST['quantite'] ?? null) ? $_POST['quantite'] : [];
        $comments = is_array($_POST['commentaire_ligne'] ?? null) ? $_POST['commentaire_ligne'] : [];
        $motifs = is_array($_POST['motif'] ?? null) ? $_POST['motif'] : [];
        $interventionNumbers = is_array($_POST['intervention_numero'] ?? null) ? $_POST['intervention_numero'] : [];
        $declarant = trim((string) ($_POST['declarant'] ?? ''));
        if ($declarant === '') {
            $this->redirect('/index.php?controller=pharmacy&action=form&error=declarant_required');
        }
        $repository = new PharmacyRepository();
        $availableArticles = $repository->findAllArticles($caserneId, true);
        $articlesById = [];
        foreach ($availableArticles as $article) {
            $articlesById[(int) ($article['id'] ?? 0)] = $article;
        }
        $lines = [];

        $maxRows = max(
            count($articleIds),
            count($quantities),
            count($comments),
            count($motifs),
            count($interventionNumbers)
        );
        for ($index = 0; $index < $maxRows; $index++) {
            $articleRaw = isset($articleIds[$index]) ? trim((string) $articleIds[$index]) : '';
            $quantityRaw = isset($quantities[$index]) ? trim((string) $quantities[$index]) : '';
            $comment = isset($comments[$index]) ? trim((string) $comments[$index]) : '';
            $motifRaw = isset($motifs[$index]) ? trim((string) $motifs[$index]) : '';
            $interventionRaw = isset($interventionNumbers[$index]) ? trim((string) $interventionNumbers[$index]) : '';

            if (
                $articleRaw === ''
                && $quantityRaw === ''
                && $comment === ''
                && $motifRaw === ''
                && $interventionRaw === ''
            ) {
                continue;
            }

            $quantityValue = $this->parsePositiveInteger($quantityRaw);
            if (!ctype_digit($articleRaw) || $quantityValue === null) {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
            }
            $articleId = (int) $articleRaw;
            $article = $articlesById[$articleId] ?? null;
            if ($article === null) {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
            }

            $motif = '';
            if ($motifRaw !== '') {
                if (!in_array($motifRaw, ['perime', 'utilise'], true)) {
                    $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
                }
                $motif = $motifRaw;
            }
            $reasonRequired = (int) ($article['motif_sortie_obligatoire'] ?? 0) === 1;
            if ($reasonRequired && $motif === '') {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
            }

            if ($motif === 'utilise' && $interventionRaw === '') {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
            }

            $composedComment = $comment;
            if ($motif === 'perime') {
                $composedComment = trim('[Motif: Materiel perime] ' . $composedComment);
            } elseif ($motif === 'utilise') {
                $composedComment = trim('[Motif: Utilise en intervention ' . $interventionRaw . '] ' . $composedComment);
            }

            $lines[] = [
                'article_id' => $articleId,
                'quantite' => (float) $quantityValue,
                'commentaire' => $composedComment === '' ? null : $composedComment,
            ];
        }

        if ($lines === []) {
            $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
        }

        $ok = $repository->recordOutputs($caserneId, $lines, $declarant);

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

    private function getPharmacyToken(?int $caserneId): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable()) {
            if ($caserneId !== null && $caserneId > 0) {
                $scoped = $repository->get('pharmacy_qr_token_caserne_' . $caserneId);
                if ($scoped !== null && trim($scoped) !== '') {
                    return trim($scoped);
                }
            }

            $global = $repository->get('pharmacy_qr_token');
            if ($global !== null && trim($global) !== '') {
                return trim($global);
            }
        }

        return trim((string) (Env::get('PHARMACY_QR_TOKEN', '') ?? ''));
    }

    private function storePharmacyCaserneContext(int $caserneId): void
    {
        if ($caserneId > 0) {
            $caserneRepository = new CaserneRepository();
            $caserne = $caserneRepository->findById($caserneId);
            if ($caserne !== null && (int) ($caserne['actif'] ?? 0) === 1) {
                $_SESSION['pharmacy_caserne_id'] = (int) $caserne['id'];
                $_SESSION['pharmacy_caserne_nom'] = (string) $caserne['nom'];
                return;
            }
        }

        unset($_SESSION['pharmacy_caserne_id'], $_SESSION['pharmacy_caserne_nom']);
    }

    private function resolvePharmacyCaserneId(): ?int
    {
        $caserneId = isset($_SESSION['pharmacy_caserne_id']) ? (int) $_SESSION['pharmacy_caserne_id'] : 0;
        if ($caserneId > 0) {
            return $caserneId;
        }

        $managerCaserneId = isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;
        if ($managerCaserneId > 0) {
            return $managerCaserneId;
        }

        return null;
    }

    private function parsePositiveInteger(string $raw): ?int
    {
        if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
            return null;
        }

        $value = (int) $raw;
        if ($value <= 0) {
            return null;
        }

        return $value;
    }
}
