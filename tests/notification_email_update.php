<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Repositories\AnomalyRepository;
use App\Repositories\NotificationRepository;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Core/Autoloader.php';
Autoloader::register();
$check = static function (bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
};
$context = [
    'vehicle' => 'VSAV 75', 'poste' => 'Conducteur', 'notifier' => 'OUDARD',
    'status' => 'resolue', 'priority' => 'moyenne', 'assignee' => 'Responsable test',
    'anomalies' => [['label' => 'Bouteille O2 5l', 'zone' => 'Cellule', 'comment' => 'Remplacee <test> & controlee']],
];
$builder = new ReflectionMethod(NotificationRepository::class, 'buildEmailBodies');
$repo = new NotificationRepository();
$bodies = $builder->invoke($repo, 0, 'anomaly.updated', 'Anomalie #17 mise a jour', 'Statut: resolue / Priorite: moyenne', '/index.php?controller=anomalies&action=index', $context, 'https://example.invalid');
foreach (['VSAV 75', 'Conducteur', 'OUDARD', 'resolue', 'moyenne', 'Responsable test', 'Bouteille O2 5l', 'Cellule'] as $value) {
    $check(str_contains($bodies['html'], $value) && str_contains($bodies['text'], $value), 'Missing update detail: ' . $value);
}
$check(str_contains($bodies['html'], 'Suivi d anomalie'), 'Generic template used instead of detailed update template');
$check(str_contains($bodies['html'], 'Mis a jour par') && !str_contains($bodies['html'], 'Signale par'), 'Update attributed as original report');
$check(str_contains($bodies['html'], 'Commentaire de traitement'), 'Missing treatment heading');
$check(str_contains($bodies['html'], 'Remplacee &lt;test&gt; &amp; controlee') && !str_contains($bodies['html'], '<test>'), 'Unescaped treatment comment');
$check(str_contains($bodies['html'], 'https://example.invalid/index.php?controller=anomalies&amp;action=index'), 'Invalid action URL');
$context['anomalies'][0]['comment'] = '';
$empty = $builder->invoke($repo, 0, 'anomaly.updated', 'Anomalie #17', 'Mise a jour.', null, $context, '');
$check(str_contains($empty['html'], 'Aucun commentaire de traitement.') && !str_contains($empty['html'], 'Non-conforme'), 'Empty treatment presented as a new non-conformity');

// The additional ID filter must not bypass the active station scope.
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE anomalies (id INT, verification_ligne_id INT, statut TEXT, priorite TEXT, commentaire TEXT, date_creation TEXT, date_resolution TEXT)');
$db->exec('CREATE TABLE verification_lignes (id INT, verification_id INT, controle_id INT)');
$db->exec('CREATE TABLE verifications (id INT, caserne_id INT, vehicule_id INT, poste_id INT, date_heure TEXT, agent TEXT)');
$db->exec('CREATE TABLE vehicules (id INT, nom TEXT)');
$db->exec('CREATE TABLE postes (id INT, nom TEXT)');
$db->exec('CREATE TABLE controles (id INT, zone TEXT, libelle TEXT)');
$db->exec("INSERT INTO anomalies VALUES (17,1,'resolue','moyenne','Remplacee','2026-11-02','2026-11-02'),(18,2,'ouverte','haute',NULL,'2026-11-02',NULL)");
$db->exec('INSERT INTO verification_lignes VALUES (1,1,1),(2,2,1)');
$db->exec("INSERT INTO verifications VALUES (1,10,1,1,'2026-11-02','QA'),(2,20,1,1,'2026-11-02','QA')");
$db->exec("INSERT INTO vehicules VALUES (1,'VSAV 75')");
$db->exec("INSERT INTO postes VALUES (1,'Conducteur')");
$db->exec("INSERT INTO controles VALUES (1,'Cellule','Bouteille O2 5l')");
$connection = new ReflectionProperty(Database::class, 'connection');
$previous = $connection->getValue();
$connection->setValue(null, $db);
try {
    $anomalies = new AnomalyRepository();
    foreach (['tableExists' => true, 'assigneeColumnExists' => false, 'occurrencesTableExists' => false] as $name => $value) {
        (new ReflectionProperty($anomalies, $name))->setValue($anomalies, $value);
    }
    $rows = $anomalies->findAll(['id' => 17], 10);
    $check(count($rows) === 1 && $rows[0]['controle_libelle'] === 'Bouteille O2 5l', 'Missing scoped anomaly context');
    $check($anomalies->findAll(['id' => 18], 10) === [], 'Cross-station anomaly leaked');
    $check($anomalies->findAll(['id' => 999], 10) === [], 'Unknown anomaly filter ignored');
} finally {
    $connection->setValue(null, $previous);
}
echo "OK: detailed update HTML/text, actor, status, treatment, escaping and caserne-scoped context (no email sent)\n";
