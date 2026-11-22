<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Repositories\VehicleRepository;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Core/Autoloader.php';
Autoloader::register();
$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE type_vehicules (id INT PRIMARY KEY, caserne_id INT, nom TEXT)');
$db->exec('CREATE TABLE vehicules (id INT PRIMARY KEY, caserne_id INT, nom TEXT, type_vehicule_id INT, actif INT, verification_active INT, verification_frequency TEXT, UNIQUE(caserne_id,type_vehicule_id,nom))');
$db->exec('CREATE TABLE postes (id INTEGER PRIMARY KEY AUTOINCREMENT, caserne_id INT, nom TEXT, code TEXT, type_vehicule_id INT, UNIQUE(caserne_id,code))');
$db->exec('CREATE TABLE controles (id INT PRIMARY KEY, caserne_id INT, vehicule_id INT, poste_id INT, zone_id INT, actif INT, type_saisie TEXT, seuil_min INT)');
$db->exec('CREATE TABLE zones (id INT, vehicule_id INT, nom TEXT)');
$db->exec('CREATE TABLE verifications (id INT, vehicule_id INT, poste_id INT)');
$db->exec('CREATE TABLE app_settings (cle TEXT, valeur TEXT)');
$db->exec("INSERT INTO type_vehicules VALUES (1,10,'VSAV'),(2,10,'FPT'),(3,20,'VSR')");
$db->exec("INSERT INTO vehicules VALUES (1,10,'VSAV 75',1,1,1,'twice_daily'),(2,10,'VSAV 76',1,0,0,'daily'),(3,20,'VSR 5',3,1,1,'daily'),(4,10,'FPT 9',2,1,1,'daily')");
$db->exec("INSERT INTO postes VALUES (1,10,'Conducteur','COND_VSAV',1),(2,10,'Equipier','EQ_VSAV',1),(3,10,'Conducteur','COND_FPT',2),(4,20,'Equipier','EQ_VSR',3),(5,10,'Autre','P2_2',1)");
$db->exec("INSERT INTO controles VALUES (1,10,1,1,1,1,'curseur',50),(2,10,1,2,1,0,'mesure',20),(3,10,2,1,2,1,'statut',NULL),(4,20,3,4,3,1,'statut',NULL)");
$db->exec("INSERT INTO zones VALUES (1,1,'Cellule'),(2,2,'Cabine'),(3,3,'Coffre')");
$db->exec('INSERT INTO verifications VALUES (1,1,1),(2,1,2)');
$db->exec("INSERT INTO app_settings VALUES ('qr_vehicle_10_1','unchanged-token')");
$property = new ReflectionProperty(Database::class, 'connection');
$previous = $property->getValue();
$property->setValue(null, $db);
try {
    $repo = new VehicleRepository();
    $snapshot = static function () use ($db): array {
        $data = [];
        foreach (['vehicules','postes','controles','zones','verifications','app_settings'] as $table) {
            $data[$table] = $db->query('SELECT * FROM ' . $table)->fetchAll(PDO::FETCH_ASSOC);
        }
        return $data;
    };
    $before = $snapshot();
    $check(!$repo->updateIdentity(1,'VSR 75',3,10),'Cross-station type accepted');
    $check(!$repo->updateIdentity(3,'FPT 75',2,10),'Cross-station vehicle accepted');
    $check(!$repo->updateIdentity(1,'FPT 9',2,10),'Duplicate identity accepted');
    $check(!$repo->updateIdentity(999,'FPT 75',2,10),'Unknown vehicle accepted');
    $check($snapshot() === $before,'Rejected change altered data');
    $db->exec("CREATE TRIGGER fail_identity BEFORE UPDATE ON vehicules WHEN NEW.id=1 BEGIN SELECT RAISE(ABORT,'simulated failure'); END");
    try {
        $repo->updateIdentity(1,'FPT 75',2,10);
        throw new RuntimeException('Expected SQL failure');
    } catch (PDOException) {
        $check(!$db->inTransaction() && $snapshot() === $before,'Failed change was not rolled back');
    }
    $db->exec('DROP TRIGGER fail_identity');
    $check($repo->updateIdentity(1,'FPT 75',2,10),'Identity update failed');
    $after = $snapshot();
    $check(count($after['vehicules']) === 4 && $after['vehicules'][0]['id'] === 1,'Vehicle duplicated');
    $check($after['vehicules'][0]['nom'] === 'FPT 75' && (int)$after['vehicules'][0]['type_vehicule_id'] === 2,'Wrong identity');
    foreach (['actif','verification_active','verification_frequency'] as $field) {
        $check($after['vehicules'][0][$field] === $before['vehicules'][0][$field],'Verification settings overwritten');
    }
    $check((int)$after['controles'][0]['poste_id'] === 3,'Existing target poste not reused');
    $newPoste = $db->query("SELECT * FROM postes WHERE type_vehicule_id=2 AND nom='Equipier'")->fetch(PDO::FETCH_ASSOC);
    $check($newPoste && (int)$newPoste['caserne_id'] === 10 && $newPoste['code'] === 'P2_2_1','Missing poste not safely copied');
    $check((int)$after['controles'][1]['poste_id'] === (int)$newPoste['id'],'Inactive control not remapped');
    foreach (['zones','verifications','app_settings'] as $table) $check($after[$table] === $before[$table],'History/QR/zones changed');
    foreach ([2,3] as $index) $check($after['controles'][$index] === $before['controles'][$index],'Another vehicle changed');
    foreach (['id','vehicule_id','zone_id','actif','type_saisie','seuil_min'] as $field) {
        $check($after['controles'][0][$field] === $before['controles'][0][$field],'Material configuration changed');
    }
    $posteCount = count($after['postes']);
    $check($repo->updateIdentity(1,'FPT 80',2,10),'Indicatif-only update failed');
    $check(count($snapshot()['postes']) === $posteCount,'Indicatif-only update created postes');
    echo "OK: rename, type change, poste reuse/copy, inactive material, caserne scope, duplicate rejection, rollback, history and QR preservation\n";
} finally {
    $property->setValue(null, $previous);
}
