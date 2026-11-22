<?php
declare(strict_types=1);
use App\Core\Autoloader;use App\Core\Database;use App\Repositories\AnomalyRepository;
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/app/Core/Autoloader.php';Autoloader::register();
$check=static function(bool $ok,string $message):void{if(!$ok)throw new RuntimeException($message);};
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE verifications (id INT,caserne_id INT,vehicule_id INT,date_heure TEXT)');
$db->exec('CREATE TABLE verification_lignes (id INT,verification_id INT,resultat TEXT)');
$db->exec('CREATE TABLE anomalies (id INT,verification_ligne_id INT,statut TEXT,priorite TEXT,date_creation TEXT,date_resolution TEXT)');
$db->exec("INSERT INTO verifications VALUES(1,10,1,'2026-11-01'),(2,10,1,'2026-10-01'),(3,10,2,'2026-11-01'),(4,20,1,'2026-11-01')");
$db->exec("INSERT INTO verification_lignes VALUES(1,1,'nok'),(2,1,'nok'),(3,2,'nok'),(4,3,'nok'),(5,4,'nok')");
$db->exec("INSERT INTO anomalies VALUES(1,1,'ouverte','haute','2026-11-01',NULL),(2,2,'resolue','moyenne','2026-11-01','2026-11-15'),(3,3,'en_cours','critique','2026-10-01',NULL),(4,4,'ouverte','moyenne','2026-11-01',NULL),(5,5,'resolue','critique','2026-11-01','2026-11-16')");
$property=new ReflectionProperty(Database::class,'connection');$previous=$property->getValue();$property->setValue(null,$db);
try{
 $repo=new AnomalyRepository();(new ReflectionProperty($repo,'tableExists'))->setValue($repo,true);
 $stats=$repo->getPublicStatistics(10,'2026-11-01','2026-12-01',1);
 $check($stats===['new'=>2,'resolved'=>1,'waiting'=>1,'in_progress'=>1,'urgent'=>2,'reports'=>2],'Period/stock statistics or vehicle/station scope incorrect');
 $all=$repo->getPublicStatistics(10,'2026-11-01','2026-12-01');$check($all['new']===3&&$all['reports']===3&&$all['waiting']===2,'Whole-station totals incorrect');
 try{$repo->getPublicStatistics(0,'2026-11-01','2026-12-01');throw new RuntimeException('Unscoped query accepted');}catch(InvalidArgumentException){}
 echo "OK: public anomaly counters distinguish monthly events/current stock and enforce station/vehicle scope\n";
}finally{$property->setValue(null,$previous);}
