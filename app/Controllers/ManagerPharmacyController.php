<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Env;
use App\Repositories\AppSettingRepository;
use App\Repositories\PharmacyRepository;
use Throwable;

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
        $inventoryAvailable = $repository->hasInventoryModule();
        $managerUser = $_SESSION['manager_user'] ?? null;
        $appUrl = $this->resolvePublicBaseUrl();
        $pharmacyToken = $this->getScopedSettingValue('pharmacy_qr_token', 'PHARMACY_QR_TOKEN', $caserneId, '');
        $caserneParam = '&caserne_id=' . $caserneId;
        $pharmacyFormPath = '/index.php?controller=pharmacy&action=access'
            . ($pharmacyToken !== '' ? '&token=' . rawurlencode($pharmacyToken) : '')
            . $caserneParam;
        $pharmacyFormUrl = $appUrl !== '' ? $appUrl . $pharmacyFormPath : $pharmacyFormPath;

        require dirname(__DIR__, 2) . '/public/views/manager_pharmacy.php';
    }

    public function inventories(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $repository = new PharmacyRepository();
        $isAvailable = $repository->isAvailable();
        $inventoryAvailable = $repository->hasInventoryModule();
        $articles = $repository->findAllArticles($caserneId, true);
        $inventories = $repository->findLastInventories($caserneId, 12);
        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $managerUser = $_SESSION['manager_user'] ?? null;
        $appUrl = $this->resolvePublicBaseUrl();
        $inventoryToken = $this->getScopedSettingValue('inventory_qr_token', 'INVENTORY_QR_TOKEN', $caserneId, '');
        if ($inventoryToken === '') {
            $inventoryToken = $this->getScopedSettingValue('pharmacy_qr_token', 'PHARMACY_QR_TOKEN', $caserneId, '');
        }
        $caserneParam = '&caserne_id=' . $caserneId;
        $inventoryMobilePath = '/index.php?controller=pharmacy&action=access'
            . ($inventoryToken !== '' ? '&token=' . rawurlencode($inventoryToken) : '')
            . $caserneParam
            . '&next=inventory_form';
        $inventoryMobileUrl = $appUrl !== '' ? $appUrl . $inventoryMobilePath : $inventoryMobilePath;

        require dirname(__DIR__, 2) . '/public/views/manager_pharmacy_inventories.php';
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
            'ack_status' => isset($_GET['ack_status']) && in_array((string) $_GET['ack_status'], ['all', 'pending', 'ack'], true)
                ? (string) $_GET['ack_status']
                : 'pending',
            'summary_scope' => isset($_GET['summary_scope']) && in_array((string) $_GET['summary_scope'], ['all', 'pending'], true)
                ? (string) $_GET['summary_scope']
                : 'pending',
        ];
        $movementGroups = $repository->findOutputGroups($caserneId, $filters, 120);
        $lastOrder = $repository->findLastOrder($caserneId);
        $summarySinceLastOrder = $repository->findSummarySinceLastOrder($caserneId, (string) $filters['summary_scope'] === 'pending');
        $summaryTotalQuantity = 0;
        $summaryTotalLines = 0;
        foreach ($summarySinceLastOrder as $line) {
            $summaryTotalQuantity += (int) round((float) ($line['quantite_totale'] ?? 0));
            $summaryTotalLines += (int) ($line['lignes'] ?? 0);
        }
        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $managerUser = $_SESSION['manager_user'] ?? null;

        require dirname(__DIR__, 2) . '/public/views/manager_pharmacy_outputs.php';
    }

    public function exportOrderCsv(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $summaryScope = isset($_GET['summary_scope']) && in_array((string) $_GET['summary_scope'], ['all', 'pending'], true)
            ? (string) $_GET['summary_scope']
            : 'pending';
        $onlyPending = $summaryScope === 'pending';

        $repository = new PharmacyRepository();
        $rows = $repository->findSummarySinceLastOrder($caserneId, $onlyPending);
        $lastOrder = $repository->findLastOrder($caserneId);

        $filename = 'commande_pharmacie_' . date('Ymd_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $out = fopen('php://output', 'wb');
        if ($out === false) {
            exit;
        }

        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['VerifApp', 'Export commande pharmacie']);
        fputcsv($out, ['Date export', date('Y-m-d H:i:s')]);
        fputcsv($out, ['Caserne ID', (string) $caserneId]);
        fputcsv($out, ['Mode synthese', $onlyPending ? 'reste_a_traiter_non_acquitte' : 'toutes_sorties']);
        fputcsv($out, ['Derniere commande', (string) ($lastOrder['commande_le'] ?? 'aucune')]);
        fputcsv($out, []);
        fputcsv($out, ['Article', 'Unite', 'Quantite_a_recommander', 'Lignes_sorties']);

        foreach ($rows as $row) {
            fputcsv($out, [
                (string) ($row['article_nom'] ?? ''),
                (string) ($row['article_unite'] ?? 'u'),
                (string) ((int) round((float) ($row['quantite_totale'] ?? 0))),
                (string) ((int) ($row['lignes'] ?? 0)),
            ]);
        }

        fclose($out);
        exit;
    }

    public function orderPrint(): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $summaryScope = isset($_GET['summary_scope']) && in_array((string) $_GET['summary_scope'], ['all', 'pending'], true)
            ? (string) $_GET['summary_scope']
            : 'pending';
        $onlyPending = $summaryScope === 'pending';

        $repository = new PharmacyRepository();
        $rows = $repository->findSummarySinceLastOrder($caserneId, $onlyPending);
        $lastOrder = $repository->findLastOrder($caserneId);
        $managerUser = $_SESSION['manager_user'] ?? null;
        $caserneName = is_array($managerUser) ? trim((string) ($managerUser['caserne_nom'] ?? '')) : '';
        $generatedAt = date('Y-m-d H:i:s');
        $totalQuantity = 0;
        $totalLines = 0;
        foreach ($rows as $row) {
            $totalQuantity += (int) round((float) ($row['quantite_totale'] ?? 0));
            $totalLines += (int) ($row['lignes'] ?? 0);
        }

        require dirname(__DIR__, 2) . '/public/views/manager_pharmacy_order_print.php';
    }

    public function outputAcknowledge(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=outputs');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $sortieKey = trim((string) ($_POST['sortie_key'] ?? ''));
        if ($sortieKey === '') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=outputs&error=ack_invalid');
        }

        $managerName = trim((string) ($_SESSION['manager_user']['nom'] ?? 'Gestionnaire'));
        $repository = new PharmacyRepository();
        $ok = $repository->acknowledgeOutputGroup($caserneId, $sortieKey, $managerName);
        if (!$ok) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=outputs&error=ack_failed');
        }

        $this->redirect('/index.php?controller=manager_pharmacy&action=outputs&success=ack_saved');
    }

    public function markOrder(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=outputs');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $note = trim((string) ($_POST['note'] ?? ''));
        $managerName = trim((string) ($_SESSION['manager_user']['nom'] ?? 'Gestionnaire'));

        $repository = new PharmacyRepository();
        $ok = $repository->createOrderMark($caserneId, $managerName, $note);
        if (!$ok) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=outputs&error=order_failed');
        }

        $this->redirect('/index.php?controller=manager_pharmacy&action=outputs&success=order_saved');
    }

    public function inventorySave(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=inventories');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $repository = new PharmacyRepository();
        if (!$repository->hasInventoryModule()) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=inventories&error=inventory_unavailable');
        }

        $createdBy = trim((string) ($_POST['declarant'] ?? ($_SESSION['manager_user']['nom'] ?? '')));
        $note = trim((string) ($_POST['note'] ?? ''));
        $articleIds = isset($_POST['article_id']) && is_array($_POST['article_id']) ? $_POST['article_id'] : [];
        $countedStocks = isset($_POST['stock_compte']) && is_array($_POST['stock_compte']) ? $_POST['stock_compte'] : [];
        $comments = isset($_POST['commentaire']) && is_array($_POST['commentaire']) ? $_POST['commentaire'] : [];
        $extraNames = isset($_POST['article_libre_nom']) && is_array($_POST['article_libre_nom']) ? $_POST['article_libre_nom'] : [];
        $extraStocks = isset($_POST['stock_compte_libre']) && is_array($_POST['stock_compte_libre']) ? $_POST['stock_compte_libre'] : [];
        $extraComments = isset($_POST['commentaire_libre']) && is_array($_POST['commentaire_libre']) ? $_POST['commentaire_libre'] : [];

        $max = max(count($articleIds), count($countedStocks), count($comments));
        $lines = [];
        for ($i = 0; $i < $max; $i++) {
            $articleId = isset($articleIds[$i]) ? (int) $articleIds[$i] : 0;
            $countedRaw = isset($countedStocks[$i]) ? trim((string) $countedStocks[$i]) : '';
            if ($articleId > 0 && $countedRaw === '') {
                $this->redirect('/index.php?controller=manager_pharmacy&action=inventories&error=inventory_missing_values');
            }
            $lines[] = [
                'article_id' => $articleId,
                'stock_compte' => $countedRaw,
                'commentaire' => isset($comments[$i]) ? (string) $comments[$i] : '',
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
                $this->redirect('/index.php?controller=manager_pharmacy&action=inventories&error=inventory_missing_values');
            }
            $lines[] = [
                'article_id' => 0,
                'article_libre_nom' => $name,
                'stock_compte' => $stock,
                'commentaire' => $comment,
            ];
        }

        $ok = $repository->createInventory($caserneId, $createdBy, $note, $lines);
        if (!$ok) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=inventories&error=inventory_save_failed');
        }

        $this->redirect('/index.php?controller=manager_pharmacy&action=inventories&success=inventory_saved');
    }

    public function inventoryShow(int $inventoryId): void
    {
        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $repository = new PharmacyRepository();
        $isAvailable = $repository->isAvailable();
        $inventoryAvailable = $repository->hasInventoryModule();
        $inventory = $repository->findInventoryById($caserneId, $inventoryId);
        if ($inventory === null) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=inventories&error=inventory_not_found');
        }
        $lines = $repository->findInventoryLines($caserneId, $inventoryId);
        $success = isset($_GET['success']) ? (string) $_GET['success'] : '';
        $error = isset($_GET['error']) ? (string) $_GET['error'] : '';
        $managerUser = $_SESSION['manager_user'] ?? null;

        require dirname(__DIR__, 2) . '/public/views/manager_pharmacy_inventory_show.php';
    }

    public function inventoryApply(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=inventories');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager&action=dashboard');
        }

        $inventoryId = isset($_POST['inventory_id']) ? (int) $_POST['inventory_id'] : 0;
        if ($inventoryId <= 0) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=inventories&error=inventory_not_found');
        }

        $managerName = trim((string) ($_SESSION['manager_user']['nom'] ?? 'Gestionnaire'));
        $repository = new PharmacyRepository();
        $status = $repository->applyInventoryToStock($caserneId, $inventoryId, $managerName);
        if ($status === 'ok') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=inventory_show&id=' . $inventoryId . '&success=inventory_applied');
        }
        if ($status === 'already_applied') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=inventory_show&id=' . $inventoryId . '&error=inventory_already_applied');
        }
        if ($status === 'not_found') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=inventories&error=inventory_not_found');
        }

        $this->redirect('/index.php?controller=manager_pharmacy&action=inventory_show&id=' . $inventoryId . '&error=inventory_apply_failed');
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
        $outputReasonRequired = isset($_POST['motif_sortie_obligatoire']) && (string) $_POST['motif_sortie_obligatoire'] === '1';

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
        try {
            $ok = $repository->saveArticle(
                $caserneId,
                $id,
                $name,
                $unit,
                (float) $stockValue,
                $alertThreshold,
                $active,
                $outputReasonRequired
            );
        } catch (Throwable $throwable) {
            $message = strtolower($throwable->getMessage());
            if (str_contains($message, 'duplicate entry') || str_contains($message, 'uq_pharmacie_articles_caserne_nom')) {
                $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_duplicate');
            }
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_save_failed');
        }

        if (!$ok) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_save_failed');
        }

        $this->redirect('/index.php?controller=manager_pharmacy&action=index&success=article_saved');
    }

    public function articleDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index');
        }

        $articleId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($articleId <= 0) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_delete_invalid');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_delete_failed');
        }

        $repository = new PharmacyRepository();
        $status = $repository->deleteArticle($caserneId, $articleId);
        if ($status === 'ok') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&success=article_deleted');
        }

        if ($status === 'has_movements') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_delete_has_movements');
        }

        if ($status === 'has_inventories') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_delete_has_inventories');
        }

        if ($status === 'not_found' || $status === 'invalid') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_delete_not_found');
        }

        $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_delete_failed');
    }

    public function articleForceDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index');
        }

        $articleId = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($articleId <= 0) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_delete_invalid');
        }

        $caserneId = $this->resolveManagerCaserneId();
        if ($caserneId === null) {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_delete_failed');
        }

        $repository = new PharmacyRepository();
        $status = $repository->forceDeleteArticle($caserneId, $articleId);
        if ($status === 'ok') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&success=article_force_deleted');
        }
        if ($status === 'must_inactive') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_force_delete_must_inactive');
        }
        if ($status === 'not_found' || $status === 'invalid') {
            $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_delete_not_found');
        }

        $this->redirect('/index.php?controller=manager_pharmacy&action=index&error=article_force_delete_failed');
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

    private function resolvePublicBaseUrl(): string
    {
        $requestHost = isset($_SERVER['HTTP_HOST']) ? trim((string) $_SERVER['HTTP_HOST']) : '';
        if ($requestHost !== '') {
            $isHttps =
                (!empty($_SERVER['HTTPS']) && (string) $_SERVER['HTTPS'] !== 'off') ||
                (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
            $scheme = $isHttps ? 'https' : 'http';

            return $scheme . '://' . $requestHost;
        }

        return rtrim((string) (Env::get('APP_URL', '') ?? ''), '/');
    }

    private function getSettingValue(string $settingKey, string $envKey, string $default): string
    {
        $repository = new AppSettingRepository();
        if ($repository->isAvailable()) {
            $value = $repository->get($settingKey);
            if ($value !== null && trim($value) !== '') {
                return trim($value);
            }
        }

        return trim((string) (Env::get($envKey, $default) ?? $default));
    }

    private function getScopedSettingValue(string $settingKey, string $envKey, ?int $caserneId, string $default): string
    {
        if ($caserneId !== null && $caserneId > 0) {
            $scoped = $this->getSettingValue($settingKey . '_caserne_' . $caserneId, $envKey, '');
            if ($scoped !== '') {
                return $scoped;
            }
        }

        return $this->getSettingValue($settingKey, $envKey, $default);
    }
}
