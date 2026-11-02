<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Core\SliderMeasure;
use App\Controllers\VerificationController;
use App\Repositories\ControleRepository;

if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/app/Core/Autoloader.php';
Autoloader::register();
$check = static function (bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException($message); }
};
$config = SliderMeasure::configuration('0', '300', '10');
$check($config === ['min'=>0,'max'=>300,'step'=>10], 'Valid configuration refused');
foreach ([['0','0','1'],['10','0','1'],['0','100','0'],['0','100','-1'],['0','100','3'],['0','100','0.5'],['','100','1'],['0','INF','1'],['0','100000000','1'],[[],100,1]] as $invalid) {
    $check(SliderMeasure::configuration(...$invalid) === null, 'Invalid configuration accepted');
}
foreach ([0,30,50,180,200,300] as $value) $check(SliderMeasure::accepts($config,$value), 'Valid value refused');
foreach ([-10,301,305,17] as $value) $check(!SliderMeasure::accepts($config,$value), 'Out-of-scale or off-step value accepted');
$negative=SliderMeasure::configuration(-20,40,5);
$check($negative !== null && SliderMeasure::accepts($negative,-15), 'Negative scale not supported');
$method=new ReflectionMethod(VerificationController::class,'computeResultForNumericControl');
$controller=new VerificationController();
foreach (['mesure','curseur'] as $type) {
    foreach ([0=>'nok',30=>'nok',50=>'ok',180=>'ok',200=>'ok',210=>'nok',300=>'nok'] as $value=>$expected) {
        $result=$method->invoke($controller,['type_saisie'=>$type,'seuil_min'=>50,'seuil_max'=>200],(float)$value);
        $check($result===$expected,'Wrong conformity at '.$value.' for '.$type);
    }
    foreach ([49=>'nok',50=>'nok',51=>'ok',200=>'ok',201=>'nok'] as $value=>$expected) {
        $result=$method->invoke($controller,['type_saisie'=>$type,'seuil_min'=>50,'seuil_max'=>200,'seuil_min_exclusif'=>1],(float)$value);
        $check($result===$expected,'Wrong exclusive minimum at '.$value.' for '.$type);
    }
    $check($method->invoke($controller,['type_saisie'=>$type,'seuil_min'=>null,'seuil_max'=>200,'seuil_min_exclusif'=>1],0.0)==='ok','Absent minimum regression');
}

// SQL persistence is exercised without connecting to any application database.
$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec('CREATE TABLE controles (id INTEGER PRIMARY KEY AUTOINCREMENT, caserne_id INT, libelle TEXT, type_saisie TEXT, valeur_attendue NUMERIC, unite TEXT, seuil_min NUMERIC, seuil_max NUMERIC, curseur_min INT, curseur_max INT, curseur_pas INT, poste_id INT, vehicule_id INT, zone_id INT, zone TEXT, ordre INT, actif INT)');
$db->exec('ALTER TABLE controles ADD COLUMN seuil_min_exclusif INT NOT NULL DEFAULT 0');
$db->exec("CREATE TABLE postes (id INT, nom TEXT); INSERT INTO postes VALUES(1,'Conducteur')");
$db->exec("CREATE TABLE zones (id INT, nom TEXT); INSERT INTO zones VALUES(1,'Cellule')");
$db->exec("CREATE TABLE vehicules (id INT, nom TEXT); INSERT INTO vehicules VALUES(1,'VSAV test')");
(new ReflectionProperty(Database::class,'connection'))->setValue(null,$db);
$repo=new ControleRepository();
foreach (['sliderSchema'=>true,'hasInputSchema'=>true,'isHierarchicalSchema'=>true,'exclusiveMinimumSchema'=>true] as $name=>$flag) {
    (new ReflectionProperty($repo,$name))->setValue($repo,$flag);
}
$check($repo->create('Bouteille',1,'',1,true,10,1,1,'curseur',null,'bars',50,200,$config,true),'Creation failed');
$id=(int)$db->lastInsertId();
foreach ([$repo->findByPosteId(1,10),$repo->findByVehicleAndPosteId(1,1,10),$repo->findAllDetailed(10),$repo->findByVehicleIdDetailed(1,10)] as $rows) {
    $check(count($rows)===1 && (int)$rows[0]['curseur_max']===300 && (int)$rows[0]['curseur_pas']===10, 'Scale lost when reading');
    $check((int)$rows[0]['seuil_min_exclusif']===1,'Exclusive minimum lost when reading');
}
$check($repo->findByVehicleAndPosteId(1,1,20)===[], 'Cross-caserne read');
$check($repo->update($id,'Bouteille',1,'',1,true,10,1,1,'curseur',null,'bars',50,200,['min'=>0,'max'=>400,'step'=>5]),'Update failed');
$check((int)$repo->findByVehicleIdDetailed(1,10)[0]['curseur_max']===400,'Updated scale lost');
$check((int)$repo->findByVehicleIdDetailed(1,10)[0]['seuil_min_exclusif']===0,'Minimum option not cleared');
$source=$repo->findByVehicleIdDetailed(1,10)[0];
$copyConfig=SliderMeasure::configuration($source['curseur_min'],$source['curseur_max'],$source['curseur_pas']);
$check($repo->create('Copie',1,'',2,true,10,1,1,$source['type_saisie'],null,$source['unite'],50,200,$copyConfig),'Copy failed');
$check((int)$repo->findByVehicleIdDetailed(1,10)[1]['curseur_pas']===5,'Copy lost step');
$check($repo->update($id,'Bouteille',1,'',1,true,10,1,1,'mesure',null,'bars',50,200),'Switch to standard measure failed');
$check($repo->findByVehicleIdDetailed(1,10)[0]['curseur_min']===null,'Old scale not cleared');
(new ReflectionProperty($repo,'isHierarchicalSchema'))->setValue($repo,false);
$check($repo->create('Legacy',1,'Zone libre',3,true,10,null,null,'curseur',null,'bars',50,200,$config),'Legacy branch failed');
$legacyId=(int)$db->lastInsertId();
$check($repo->update($legacyId,'Legacy',1,'Zone libre',3,true,10,null,null,'curseur',null,'bars',50,200,$config,true),'Legacy update failed');
$check(count($repo->findAllDetailed(10))===3,'Legacy read failed');
$legacyRows=$repo->findAllDetailed(10);
$legacyRow=array_values(array_filter($legacyRows,fn($row)=>(int)$row['id']===$legacyId))[0];
$check((int)$legacyRow['seuil_min_exclusif']===1,'Legacy minimum option lost');
(new ReflectionProperty($repo,'exclusiveMinimumSchema'))->setValue($repo,false);
$check(!$repo->create('Not migrated minimum',1,'Zone libre',1,true,10,null,null,'mesure',null,'bars',50,200,null,true),'Unmigrated exclusive minimum silently accepted');
(new ReflectionProperty($repo,'sliderSchema'))->setValue($repo,false);
$check(!$repo->create('Not migrated',1,'Zone libre',1,true,10,null,null,'curseur',null,'bars',50,200,$config),'Unmigrated schema accepted a slider');
$check($repo->create('Normal',1,'Zone libre',1,true,10,null,null,'mesure',null,'bars',50,200),'Old schema standard measure regression');
echo "OK: configuration, scale/step validation, thresholds, persistence, reads, editing, copy, caserne scope and schema compatibility\n";
