<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Repositories\VerificationRepository;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Core/Autoloader.php';
Autoloader::register();
set_error_handler(static function (int $severity, string $message, string $file, int $line): never {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE verifications (id INT, caserne_id INT)');
$db->exec('CREATE TABLE verification_lignes (id INT, verification_id INT, controle_id INT, resultat TEXT, commentaire TEXT)');
$db->exec('CREATE TABLE controles (id INT, zone_id INT, zone TEXT, ordre INT, libelle TEXT)');
$db->exec('CREATE TABLE zones (id INT, ordre INT)');
$db->exec('INSERT INTO verifications VALUES (1, 10), (2, 20)');
$db->exec("INSERT INTO controles VALUES (1, 1, 'Alpha', 1, 'A'), (2, 2, 'Zulu', 1, 'B'), (3, 2, 'Zulu', 0, 'C')");
$db->exec('INSERT INTO zones VALUES (1, 20), (2, 10)');
$db->exec("INSERT INTO verification_lignes VALUES (1, 1, 1, 'ok', ''), (2, 1, 2, 'ok', ''), (3, 1, 3, 'nok', 'Manquant'), (4, 2, 1, 'ok', '')");
$connectionProperty = new ReflectionProperty(Database::class, 'connection');
$previousConnection = $connectionProperty->getValue();
$connectionProperty->setValue(null, $db);
try {
    foreach ([true, false] as $hasZoneOrder) {
        if (!$hasZoneOrder) {
            $db->exec('DROP TABLE zones');
            $db->exec('CREATE TABLE zones (id INT)');
        }
        $repo = new VerificationRepository();
        foreach (['anomaliesTableExists' => false, 'valeurSaisieColumnExists' => false, 'zoneOrderColumnExists' => $hasZoneOrder] as $name => $flag) {
            (new ReflectionProperty($repo, $name))->setValue($repo, $flag);
        }
        $rows = $repo->findLinesByVerificationId(1, 10);
        $check(array_map('intval', array_column($rows, 'controle_id')) === ($hasZoneOrder ? [3, 2, 1] : [1, 3, 2]), 'Wrong zone or control order');
        $check($repo->findLinesByVerificationId(1, 20) === [], 'Cross-caserne access');
        $check($repo->findLinesByVerificationId(999, 10) === [], 'Unknown verification returned lines');
        $check(count($repo->findLinesByVerificationId(1)) === 3, 'Unscoped detail regression');
        $check($rows[0]['anomalie_id'] === null, 'Missing-anomaly schema regression');
    }
    echo "OK: verification detail without warnings, configured zone order, legacy fallback and caserne scope\n";
} finally {
    $connectionProperty->setValue(null, $previousConnection);
    restore_error_handler();
}
