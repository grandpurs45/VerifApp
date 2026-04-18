<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\CaserneRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\PharmacyRepository;

final class PharmacyController
{
    public function access(): void
    {
        $caserneId = isset($_GET['caserne_id']) ? (int) $_GET['caserne_id'] : 0;
        $next = isset($_GET['next']) ? (string) $_GET['next'] : 'form';
        if (!in_array($next, ['form', 'inventory_form'], true)) {
            $next = 'form';
        }
        $configuredToken = $next === 'inventory_form'
            ? $this->getInventoryToken($caserneId > 0 ? $caserneId : null)
            : $this->getPharmacyToken($caserneId > 0 ? $caserneId : null);
        if ($configuredToken === '' && $next === 'inventory_form') {
            $configuredToken = $this->getPharmacyToken($caserneId > 0 ? $caserneId : null);
        }
        $providedToken = isset($_GET['token']) ? (string) $_GET['token'] : '';

        if ($configuredToken === '') {
            $_SESSION['pharmacy_access'] = true;
            $this->storePharmacyCaserneContext($caserneId);
            $this->redirect('/index.php?controller=pharmacy&action=' . $next);
        }

        if ($caserneId <= 0) {
            $this->redirect('/index.php?controller=pharmacy&action=denied');
        }

        if (hash_equals($configuredToken, $providedToken)) {
            $_SESSION['pharmacy_access'] = true;
            $this->storePharmacyCaserneContext($caserneId);
            $this->redirect('/index.php?controller=pharmacy&action=' . $next);
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
        $articles = $repository->findAllArticles($caserneId, true, true);
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
        $availableArticles = $repository->findAllArticles($caserneId, true, true);
        $articlesById = [];
        foreach ($availableArticles as $article) {
            $articlesById[(int) ($article['id'] ?? 0)] = $article;
        }
        $lines = [];
        $notificationLines = [];
        $hasOtherLine = false;

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

            $isOtherArticle = $articleRaw === 'other';
            $quantityValue = $isOtherArticle ? 1 : $this->parsePositiveInteger($quantityRaw);
            if ((!ctype_digit($articleRaw) && !$isOtherArticle) || $quantityValue === null) {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
            }
            $articleId = $isOtherArticle ? 0 : (int) $articleRaw;
            $article = $isOtherArticle ? null : ($articlesById[$articleId] ?? null);
            if (!$isOtherArticle && $article === null) {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
            }

            $motif = '';
            if (!$isOtherArticle && $motifRaw !== '') {
                if (!in_array($motifRaw, ['perime', 'utilise', 'perdu'], true)) {
                    $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
                }
                $motif = $motifRaw;
            }
            $reasonRequired = !$isOtherArticle && (int) ($article['motif_sortie_obligatoire'] ?? 0) === 1;
            if ($reasonRequired && $motif === '') {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
            }

            if ($motif === 'utilise' && $interventionRaw === '') {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
            }

            if ($isOtherArticle && mb_strlen($comment) < 5) {
                $this->redirect('/index.php?controller=pharmacy&action=form&error=other_comment_required');
            }
            if ($isOtherArticle) {
                $hasOtherLine = true;
            }

            $composedComment = $comment;
            if ($motif === 'perime') {
                $composedComment = trim('[Motif: Materiel perime] ' . $composedComment);
            } elseif ($motif === 'utilise') {
                $composedComment = trim('[Motif: Utilise en intervention ' . $interventionRaw . '] ' . $composedComment);
            } elseif ($motif === 'perdu') {
                $composedComment = trim('[Motif: Materiel perdu] ' . $composedComment);
            } elseif ($isOtherArticle) {
                $composedComment = trim('[Article: Autre hors liste] ' . $composedComment);
            }

            $lines[] = [
                'article_id' => $articleId,
                'quantite' => (float) $quantityValue,
                'article_libre_nom' => $isOtherArticle ? 'Autre (hors liste)' : null,
                'commentaire' => $composedComment === '' ? null : $composedComment,
            ];
            $notificationLines[] = [
                'article' => $isOtherArticle ? 'Autre (hors liste)' : (string) ($article['nom'] ?? ''),
                'quantity' => (float) $quantityValue,
                'comment' => $composedComment,
            ];
        }

        if ($lines === []) {
            $this->redirect('/index.php?controller=pharmacy&action=form&error=invalid');
        }

        if ($hasOtherLine && !$repository->supportsFreeLabelOutputs()) {
            $this->redirect('/index.php?controller=pharmacy&action=form&error=other_requires_migration');
        }

        $ok = $repository->recordOutputs($caserneId, $lines, $declarant);

        if (!$ok) {
            $this->redirect('/index.php?controller=pharmacy&action=form&error=stock');
        }

        $notificationRepository = new NotificationRepository();
        $notificationRepository->createForCaserneEvent(
            $caserneId,
            'pharmacy.output.created',
            'Sortie pharmacie enregistree',
            count($lines) . ' ligne(s) enregistree(s) par ' . $declarant,
            '/index.php?controller=manager_pharmacy&action=outputs',
            null,
            $declarant,
            [
                'declarant' => $declarant,
                'lines' => $notificationLines,
            ]
        );

        $this->redirect('/index.php?controller=pharmacy&action=form&success=1&items=' . count($lines));
    }

    public function inventoryForm(): void
    {
        $caserneId = $this->resolvePharmacyCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=pharmacy&action=denied');
        }

        $repository = new PharmacyRepository();
        $isAvailable = $repository->isAvailable();
        $inventoryAvailable = $repository->hasInventoryModule();
        $articles = $repository->findAllArticles($caserneId, true, true);
        $success = isset($_GET['success']) && (string) $_GET['success'] === '1';
        $savedItems = isset($_GET['items']) && ctype_digit((string) $_GET['items']) ? (int) $_GET['items'] : 0;
        $errorCode = isset($_GET['error']) ? (string) $_GET['error'] : '';

        require dirname(__DIR__, 2) . '/public/views/pharmacy_inventory_form.php';
    }

    public function inventorySave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=pharmacy&action=inventory_form');
        }

        $caserneId = $this->resolvePharmacyCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=pharmacy&action=denied');
        }

        $declarant = trim((string) ($_POST['declarant'] ?? ''));
        if ($declarant === '') {
            $this->redirect('/index.php?controller=pharmacy&action=inventory_form&error=declarant_required');
        }

        $repository = new PharmacyRepository();
        if (!$repository->hasInventoryModule()) {
            $this->redirect('/index.php?controller=pharmacy&action=inventory_form&error=inventory_unavailable');
        }

        $articleIds = is_array($_POST['article_id'] ?? null) ? $_POST['article_id'] : [];
        $countedStocks = is_array($_POST['stock_compte'] ?? null) ? $_POST['stock_compte'] : [];
        $comments = is_array($_POST['commentaire'] ?? null) ? $_POST['commentaire'] : [];
        $extraNames = is_array($_POST['article_libre_nom'] ?? null) ? $_POST['article_libre_nom'] : [];
        $extraStocks = is_array($_POST['stock_compte_libre'] ?? null) ? $_POST['stock_compte_libre'] : [];
        $extraComments = is_array($_POST['commentaire_libre'] ?? null) ? $_POST['commentaire_libre'] : [];
        $note = trim((string) ($_POST['note'] ?? ''));

        $max = max(count($articleIds), count($countedStocks), count($comments));
        $lines = [];
        for ($i = 0; $i < $max; $i++) {
            $articleId = isset($articleIds[$i]) ? (int) $articleIds[$i] : 0;
            $countedRaw = isset($countedStocks[$i]) ? trim((string) $countedStocks[$i]) : '';
            $comment = isset($comments[$i]) ? trim((string) $comments[$i]) : '';
            if ($articleId <= 0 && $countedRaw === '' && $comment === '') {
                continue;
            }
            if ($articleId <= 0 || $countedRaw === '') {
                $this->redirect('/index.php?controller=pharmacy&action=inventory_form&error=inventory_missing_values');
            }
            if ($this->parseNonNegativeInteger($countedRaw) === null) {
                $this->redirect('/index.php?controller=pharmacy&action=inventory_form&error=inventory_invalid_qty');
            }

            $lines[] = [
                'article_id' => $articleId,
                'stock_compte' => $countedRaw,
                'commentaire' => $comment,
            ];
        }

        $extraMax = max(count($extraNames), count($extraStocks), count($extraComments));
        for ($i = 0; $i < $extraMax; $i++) {
            $name = trim((string) ($extraNames[$i] ?? ''));
            $stock = trim((string) ($extraStocks[$i] ?? ''));
            $comment = trim((string) ($extraComments[$i] ?? ''));
            if ($name === '' && $stock === '' && $comment === '') {
                continue;
            }
            if ($name === '' || $stock === '') {
                $this->redirect('/index.php?controller=pharmacy&action=inventory_form&error=inventory_missing_values');
            }
            if ($this->parseNonNegativeInteger($stock) === null) {
                $this->redirect('/index.php?controller=pharmacy&action=inventory_form&error=inventory_invalid_qty');
            }
            $lines[] = [
                'article_id' => 0,
                'article_libre_nom' => $name,
                'stock_compte' => $stock,
                'commentaire' => $comment,
            ];
        }

        if ($lines === []) {
            $this->redirect('/index.php?controller=pharmacy&action=inventory_form&error=invalid');
        }

        $ok = $repository->createInventory($caserneId, $declarant, $note, $lines);
        if (!$ok) {
            $this->redirect('/index.php?controller=pharmacy&action=inventory_form&error=inventory_save_failed');
        }

        $notificationRepository = new NotificationRepository();
        $notificationRepository->createForCaserneEvent(
            $caserneId,
            'pharmacy.inventory.created',
            'Inventaire pharmacie saisi',
            count($lines) . ' ligne(s) inventoriee(s) par ' . $declarant,
            '/index.php?controller=manager_pharmacy&action=inventories',
            null,
            $declarant
        );

        $this->redirect('/index.php?controller=pharmacy&action=inventory_form&success=1&items=' . count($lines));
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

    private function getInventoryToken(?int $caserneId): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable()) {
            if ($caserneId !== null && $caserneId > 0) {
                $scoped = $repository->get('inventory_qr_token_caserne_' . $caserneId);
                if ($scoped !== null && trim($scoped) !== '') {
                    return trim($scoped);
                }
            }

            $global = $repository->get('inventory_qr_token');
            if ($global !== null && trim($global) !== '') {
                return trim($global);
            }
        }

        return trim((string) (Env::get('INVENTORY_QR_TOKEN', '') ?? ''));
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

    private function parseNonNegativeInteger(string $raw): ?int
    {
        if ($raw === '' || !preg_match('/^\d+$/', $raw)) {
            return null;
        }

        $value = (int) $raw;
        if ($value < 0) {
            return null;
        }

        return $value;
    }
}
