<?php

declare(strict_types=1);

namespace App\Repositories {
    final class VehicleRepository {
        public function findById(...$args): array { return ['id'=>1,'actif'=>1,'verification_active'=>1]; }
    }
    final class PosteRepository {
        public function findByIdForVehicle(...$args): array { return ['id'=>1]; }
    }
    final class ControleRepository {
        public function findByVehicleAndPosteId(...$args): array {
            return [['id'=>1,'type_saisie'=>'curseur','curseur_min'=>0,'curseur_max'=>300,'curseur_pas'=>10,'seuil_min'=>50,'seuil_max'=>200,'seuil_min_exclusif'=>str_ends_with($GLOBALS['argv'][1] ?? '', '_exclusive') ? 1 : 0,'libelle'=>'Bouteille','zone'=>'Cellule','unite'=>'bars']];
        }
    }
    final class VerificationRepository {
        public function createWithLines(...$args): int { throw new \CapturedSliderSubmission($args[7]); }
    }
}
namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require dirname(__DIR__).'/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    final class CapturedSliderSubmission extends RuntimeException {
        public function __construct(public array $lines) { parent::__construct('captured'); }
    }
    $cases=['missing'=>null,'blank'=>'','negative'=>'-10','outside'=>'310','off_step'=>'17','fraction'=>'12.5','zero'=>'0','low'=>'30','limit'=>'50','valid'=>'180','high'=>'300','limit_exclusive'=>'50','above_exclusive'=>'60'];
    $name=$argv[1]??'';
    if ($name==='') {
        foreach ($cases as $case=>$value) {
            $process=proc_open([PHP_BINARY,__FILE__,$case],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
            $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]);
            fclose($pipes[1]); fclose($pipes[2]);
            $code=proc_close($process);
            if ($code!==0 || $err!=='' || trim($out)!=='OK '.$case) { fwrite(STDERR,$case.': '.$out.$err); exit(1); }
            echo $out;
        }
        exit;
    }
    if (!array_key_exists($name,$cases)) exit(1);
    $_SESSION=['field_caserne_id'=>1];
    $_SERVER['REQUEST_METHOD']='POST';
    $_POST=['vehicle_id'=>1,'poste_id'=>1,'agent'=>'QA','valeurs'=>$cases[$name]===null?[]:['1'=>$cases[$name]]];
    $captured=null;
    register_shutdown_function(static function () use (&$captured,$name,$cases): void {
        $valid=in_array($name,['zero','low','limit','valid','high','limit_exclusive','above_exclusive'],true);
        if (($captured!==null)!==$valid) { fwrite(STDERR,'Unexpected submission accepted/rejected'); exit(1); }
        if ($valid) {
            $value=(float)$cases[$name];
            $expected=$value<50 || $value>200 || ($name==='limit_exclusive') ? 'nok' : 'ok';
            if ($captured[0]['resultat']!==$expected || $captured[0]['valeur_saisie']!==$value) { fwrite(STDERR,'Wrong stored value/conformity'); exit(1); }
        }
        echo 'OK '.$name.PHP_EOL;
    });
    try { (new \App\Controllers\VerificationController())->store(); }
    catch (CapturedSliderSubmission $result) { $captured=$result->lines; }
}
