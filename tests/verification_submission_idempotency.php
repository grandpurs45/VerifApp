<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Repositories\VerificationRepository;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Core/Autoloader.php';
Autoloader::register();
$check = static function (bool $ok, string $message): void { if (!$ok) throw new RuntimeException($message); };
$db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$db->sqliteCreateFunction('NOW', static fn(): string => '2026-11-22 12:00:00');
$db->exec('CREATE TABLE verifications (id INTEGER PRIMARY KEY AUTOINCREMENT, caserne_id INT, vehicule_id INT, poste_id INT, utilisateur_id INT, agent TEXT, garde_duree_heures INT, date_heure TEXT, statut_global TEXT, commentaire_global TEXT, submission_key TEXT UNIQUE, submission_hash TEXT)');
$db->exec('CREATE TABLE verification_lignes (id INTEGER PRIMARY KEY AUTOINCREMENT, verification_id INT, controle_id INT, resultat TEXT, valeur_saisie NUMERIC, commentaire TEXT, photo TEXT)');
$db->exec('CREATE TABLE anomalies (id INTEGER PRIMARY KEY AUTOINCREMENT, verification_ligne_id INT, statut TEXT, priorite TEXT, commentaire TEXT, date_creation TEXT, date_resolution TEXT)');
$property = new ReflectionProperty(Database::class, 'connection');
$previous = $property->getValue(); $property->setValue(null,$db);
try {
    $repo = new VerificationRepository();
    foreach (['submissionSchema'=>true,'utilisateurColumnExists'=>true,'guardDurationColumnExists'=>true,'valeurSaisieColumnExists'=>true,'anomaliesTableExists'=>true,'anomalyOccurrencesTableExists'=>false] as $field=>$value) {
        (new ReflectionProperty($repo,$field))->setValue($repo,$value);
    }
    $lines=[['controle_id'=>1,'resultat'=>'nok','commentaire'=>'Manquant','valeur_saisie'=>null],['controle_id'=>2,'resultat'=>'ok','commentaire'=>null,'valeur_saisie'=>150.0]];
    $key=str_repeat('a',64);
    $create=static fn(array $data,string $token): int => $repo->createWithLines(10,1,2,null,'Gaucher',12,null,$data,$token);
    $first=$create($lines,$key);
    $check(!$repo->wasSubmissionReplayed(),'First submission marked as replay');
    for ($i=0;$i<4;$i++) {
        $check($create(array_reverse($lines),$key)===$first,'Replay returned a new verification');
        $check($repo->wasSubmissionReplayed(),'Replay not signaled to controller');
        $check($repo->getLastAnomalyReports()===[],'Replay retained anomaly reports');
    }
    $check((int)$db->query('SELECT COUNT(*) FROM verifications')->fetchColumn()===1,'Repeated form created duplicate verifications');
    $check((int)$db->query('SELECT COUNT(*) FROM verification_lignes')->fetchColumn()===2,'Replay created duplicate material lines');
    $check((int)$db->query('SELECT COUNT(*) FROM anomalies')->fetchColumn()===1,'Replay created duplicate anomalies');
    $changed=$lines; $changed[0]['commentaire']='Autre reponse';
    try { $create($changed,$key); throw new RuntimeException('Conflicting token accepted'); } catch (DomainException) {}
    try { $repo->createWithLines(20,1,2,null,'Gaucher',12,null,$lines,$key); throw new RuntimeException('Cross-caserne token accepted'); } catch (DomainException) {}
    $second=$create($lines,str_repeat('b',64));
    $check($second!==$first && !$repo->wasSubmissionReplayed(),'Genuine new form blocked');
    $db->exec("CREATE TRIGGER reject_line BEFORE INSERT ON verification_lignes WHEN NEW.controle_id=99 BEGIN SELECT RAISE(ABORT,'test failure'); END");
    $broken=$lines; $broken[0]['controle_id']=99;
    try { $create($broken,str_repeat('c',64)); throw new RuntimeException('Expected line failure'); } catch (PDOException) {}
    $check(!$db->inTransaction(),'Failed submission left transaction open');
    $check((int)$db->query('SELECT COUNT(*) FROM verifications')->fetchColumn()===2,'Failed submission committed header or token');
    $check($create($lines,str_repeat('c',64))>$second,'Retry after rollback refused');
    try {
        $db->prepare('INSERT INTO verifications (submission_key) VALUES (?)')->execute([$key]);
        throw new RuntimeException('Database allowed duplicate token');
    } catch (PDOException $error) { $check((string)$error->getCode()==='23000','Unexpected unique constraint error'); }
    echo "OK: repeated form saved once, stable identity, payload conflict, station isolation, genuine new form, rollback and unique constraint\n";
} finally { $property->setValue(null,$previous); }
