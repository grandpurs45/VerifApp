<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Repositories\VehicleRepository;
use App\Repositories\VerificationRepository;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Core/Autoloader.php';
Autoloader::register();
$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
// PDO binds execute-array parameters as strings; use padded hours for SQLite comparisons.
$db->sqliteCreateFunction('HOUR', static fn(string $date): string => substr($date, 11, 2), 1);
$db->exec('CREATE TABLE type_vehicules (id INT, caserne_id INT, nom TEXT)');
$db->exec('CREATE TABLE vehicules (id INT, caserne_id INT, nom TEXT, type_vehicule_id INT)');
$db->exec('CREATE TABLE postes (id INTEGER PRIMARY KEY AUTOINCREMENT, caserne_id INT, nom TEXT, code TEXT, type_vehicule_id INT)');
$db->exec('CREATE TABLE controles (id INT, vehicule_id INT, caserne_id INT, poste_id INT)');
$db->exec('CREATE TABLE verifications (id INT, vehicule_id INT, caserne_id INT, poste_id INT, date_heure TEXT, statut_global TEXT, garde_duree_heures INT)');
$db->exec('CREATE TABLE verification_lignes (verification_id INT, controle_id INT)');
$db->exec('CREATE INDEX idx_lines_verification ON verification_lignes (verification_id)');
$db->exec("INSERT INTO type_vehicules VALUES (1,10,'VSAV'),(2,10,'VSSUAP'),(3,20,'VSAV'),(4,10,'FPT')");
$db->exec("INSERT INTO vehicules VALUES (1,10,'VSAV 75',1),(2,10,'VSAV 76',1),(3,20,'VSAV 99',3)");
$db->exec("INSERT INTO postes VALUES (1,10,'Conducteur VSAV','C1',1),(2,10,'Equipier VSAV','E1',1),(3,10,'Chef VSAV','A1',1),(4,10,'Conducteur VSSUAP','C2',2),(5,10,'Equipier VSSUAP','E2',2),(6,10,'Chef VSSUAP','A2',2),(7,20,'Conducteur VSAV','C3',3),(8,10,'Conducteur FPT','C4',4),(9,10,'Equipier FPT','E4',4),(10,10,'Chef FPT','A4',4)");
$db->exec('INSERT INTO controles VALUES (1,1,10,1),(2,1,10,2),(3,1,10,3),(4,2,10,1),(5,3,20,7)');
$nextId = 0;
$insert = static function (int $day, int $hour, int $poste, int $guard, array $controls, string $status = 'conforme', int $vehicle = 1, int $caserne = 10) use ($db, &$nextId): void {
    $id = ++$nextId;
    $db->prepare('INSERT INTO verifications VALUES (?,?,?,?,?,?,?)')->execute([$id,$vehicle,$caserne,$poste,sprintf('2026-11-%02d %02d:00:00',$day,$hour),$status,$guard]);
    foreach ($controls as $control) $db->prepare('INSERT INTO verification_lignes VALUES (?,?)')->execute([$id,$control]);
};
$insert(1,8,1,12,[1]); $insert(1,19,1,12,[1]);
$insert(1,8,2,24,[2],'non_conforme'); $insert(1,8,3,24,[3]);
foreach ([1,2,3] as $poste) $insert(2,8,$poste,12,[$poste]);
$insert(2,9,1,12,[1]);
$insert(3,8,1,24,[1]); $insert(3,8,2,12,[2]); $insert(3,19,3,12,[3]);
$insert(5,8,1,12,[1]);
$insert(1,8,1,24,[4],'conforme',2);
$insert(1,8,7,24,[5],'conforme',3,20);
$property = new ReflectionProperty(Database::class, 'connection');
$previous = $property->getValue(); $property->setValue(null,$db);
try {
    $stats = new VerificationRepository();
    (new ReflectionProperty($stats,'guardDurationColumnExists'))->setValue($stats,true);
    $read = static fn(): array => $stats->findMonthlyDailyPosteStats(2026,11,10,1);
    $before = $read();
    $historical = $db->query('SELECT * FROM verifications ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
    $vehicles = new VehicleRepository();
    $check($vehicles->updateIdentity(1,'VSSUAP 007',2,10),'Type change failed');
    $after = $read();
    $expected = $before;
    foreach ($expected as &$row) $row['poste_id'] = (int)$row['poste_id'] + 3;
    unset($row);
    $check($after === $expected,'Historical coverage/counts changed after renaming');
    $summarize = static function (array $rows): array {
        $days=[];
        foreach ($rows as $row) {
            $date=$row['jour'];
            $days[$date]['slots']=($days[$date]['slots']??0)+(int)$row['morning_covered']+(int)$row['evening_covered'];
            $days[$date]['verifs']=($days[$date]['verifs']??0)+(int)$row['total_verifs'];
            $days[$date]['nok']=($days[$date]['nok']??0)+(int)$row['non_conformes'];
        }
        return $days;
    };
    $days=$summarize($after);
    $check($days['2026-11-01']===['slots'=>6,'verifs'=>4,'nok'=>1],'24h guards or full day broken: '.json_encode($after));
    $check($days['2026-11-02']===['slots'=>3,'verifs'=>4,'nok'=>0],'Morning-only or duplicate handling broken');
    $check($days['2026-11-03']['slots']===4,'Mixed 12h/24h coverage broken');
    $check($db->query('SELECT * FROM verifications ORDER BY id')->fetchAll(PDO::FETCH_ASSOC)===$historical,'Historical records modified');
    $insert(5,19,4,12,[1]);
    $day5=array_values(array_filter($read(),fn(array $row): bool=>$row['jour']==='2026-11-05'));
    $check(count($day5)===1 && (int)$day5[0]['total_verifs']===2 && (int)$day5[0]['morning_covered']===1 && (int)$day5[0]['evening_covered']===1,'Old/new poste IDs were double counted or not merged');
    // The legacy copied poste is kept for history, then repaired to the current poste.
    $db->exec("INSERT INTO postes VALUES (20,10,'Conducteur VSAV','P2_1',2)");
    $db->exec('UPDATE controles SET poste_id=20 WHERE id=1');
    $insert(4,8,20,24,[1]);
    $check($vehicles->updateIdentity(1,'VSSUAP 007',2,10),'Legacy role repair failed');
    $day4=array_values(array_filter($read(),fn(array $row): bool=>$row['jour']==='2026-11-04'));
    $check(count($day4)===1 && (int)$day4[0]['poste_id']===4 && (int)$day4[0]['morning_covered']===1 && (int)$day4[0]['evening_covered']===1,'Coverage lost after legacy repair');
    $beforeSecondChange=$summarize($read());
    $check($vehicles->updateIdentity(1,'FPT 007',4,10),'Second type change failed');
    $check($summarize($read())===$beforeSecondChange,'Coverage changed after a second type change');
    foreach ($read() as $row) $check(in_array((int)$row['poste_id'],[8,9,10],true),'Old role retained in coverage');
    $insert(6,8,999,24,[1,2]);
    $ambiguous=array_values(array_filter($read(),fn(array $row): bool=>$row['jour']==='2026-11-06'));
    $check(count($ambiguous)===1 && (int)$ambiguous[0]['poste_id']===999,'Ambiguous material split guessed a role');
    $insert(7,8,999,24,[4,5]);
    $foreign=array_values(array_filter($read(),fn(array $row): bool=>$row['jour']==='2026-11-07'));
    $check(count($foreign)===1 && (int)$foreign[0]['poste_id']===999,'Foreign vehicle/station material used for coverage');
    $check($stats->findMonthlyDailyPosteStats(2026,11,20,1)===[],'Station scope bypassed');
    $check($stats->findMonthlyDailyPosteStats(2026,12,10,1)===[],'Month filter ignored');
    echo "OK: monthly coverage preserved across type changes and legacy repair, 12h/24h, deduplication, raw totals, history and caserne scope\n";
} finally {
    $property->setValue(null,$previous);
}
