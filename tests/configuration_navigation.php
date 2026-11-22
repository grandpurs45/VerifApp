<?php

declare(strict_types=1);

namespace App\Repositories {
    final class NotificationRepository { public function isAvailable(): bool { return false; } }
    final class AppSettingRepository { public function isAvailable(): bool { return false; } }
    final class RoleRepository { public function isAvailable(): bool { return false; } }
}
namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require dirname(__DIR__) . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    $renderConfigurationShell = static function (string $controller, string $action, string $role = 'admin'): string {
        $_GET = ['controller' => $controller, 'action' => $action];
        $_SESSION = ['manager_user' => ['role' => $role, 'nom' => 'QA', 'caserne_nom' => 'INGRE']];
        $pageHeading = $action === 'types' ? 'Types & postes' : 'Vehicules & materiel';
        ob_start();
        require dirname(__DIR__) . '/public/views/partials/backoffice_shell_top.php';
        echo '<section class="bg-white p-4"><h2>Configuration test</h2></section>';
        require dirname(__DIR__) . '/public/views/partials/backoffice_shell_bottom.php';
        return ob_get_clean();
    };
    $check = static function (bool $ok, string $message): void {
        if (!$ok) throw new \RuntimeException($message);
    };
    foreach (['types', 'type_detail', 'vehicles', 'vehicle_detail', 'vehicle_zones'] as $action) {
        $html = $renderConfigurationShell('manager_assets', $action);
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html);
        libxml_clear_errors(); libxml_use_internal_errors($previous);
        $xpath = new \DOMXPath($dom);
        $activeAction = in_array($action, ['types', 'type_detail'], true) ? 'types' : 'vehicles';
        $links = $xpath->query('//nav[@aria-label="Configuration des verifications"]/a');
        $navigation = $xpath->query('//nav[@aria-label="Configuration des verifications"]')->item(0);
        $check(str_contains($navigation->getAttribute('class'), 'lg:hidden'), 'Duplicate desktop menu remains visible');
        $check($links->length === 2, 'Missing configuration navigation on ' . $action);
        $active = $xpath->query('//nav[@aria-label="Configuration des verifications"]/a[@aria-current="page"]');
        $check($active->length === 1 && str_contains($active->item(0)->getAttribute('href'), 'action=' . $activeAction), 'Wrong active section on ' . $action);
        $sidebarActive = $xpath->query('//*[@data-verification-configuration]//a[@aria-current="page"]');
        $check($sidebarActive->length === 1 && str_contains($sidebarActive->item(0)->getAttribute('href'), 'action=' . $activeAction), 'Wrong sidebar section');
        $check(!str_contains($html, 'Parc &amp; materiel'), 'Old ambiguous module label remains');
    }
    $history = $renderConfigurationShell('verifications', 'history');
    $check(str_contains($history, 'data-verification-configuration') && str_contains($history, 'Types &amp; postes'), 'Configuration hidden from verification history');
    $restricted = $renderConfigurationShell('manager_pharmacy', 'index', 'resp_pharma');
    $check(!str_contains($restricted, 'data-verification-configuration') && !str_contains($restricted, 'controller=manager_assets'), 'Configuration shown without assets.manage');
    echo "OK: configuration links, active section on types/vehicle subpages, verification grouping and permission filtering (no database)\n";
}
