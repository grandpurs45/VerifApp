<?php

declare(strict_types=1);

namespace App\Repositories {
    final class VehicleRepository { public function findById(...$args): array { return ['id'=>1,'actif'=>1,'verification_active'=>1]; } }
    final class PosteRepository { public function findByIdForVehicle(...$args): array { return ['id'=>2]; } }
    final class ControleRepository { public function findByVehicleAndPosteId(...$args): array { return [['id'=>1,'type_saisie'=>'statut']]; } }
    final class VerificationRepository {
        public function createWithLines(...$args): int {
            $GLOBALS['called']=true;
            if (($GLOBALS['argv'][1]??'')==='conflict') throw new \DomainException('conflict');
            return 42;
        }
        public function wasSubmissionReplayed(): bool { return true; }
        public function getLastAnomalyReports(): array { throw new \RuntimeException('Replay reached notification generation'); }
    }
    final class QrAccessLogRepository { public function attachIdentity(...$args): void { throw new \RuntimeException('Replay reached QR side effects'); } }
    final class NotificationRepository { public function __construct() { throw new \RuntimeException('Replay sent a notification'); } }
}
namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require dirname(__DIR__).'/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    if (!isset($argv[1])) {
        foreach (['replay','conflict','missing_key'] as $case) {
            $process=proc_open([PHP_BINARY,__FILE__,$case],[1=>['pipe','w'],2=>['pipe','w']],$pipes);
            $out=stream_get_contents($pipes[1]); $err=stream_get_contents($pipes[2]);
            fclose($pipes[1]);fclose($pipes[2]);$code=proc_close($process);
            if ($code!==0 || $err!=='' || trim($out)!=='OK '.$case) { fwrite(STDERR,$out.$err);exit(1); }
            echo $out;
        }
        exit;
    }
    $called=false;
    $_SESSION=['field_caserne_id'=>10]; $_SERVER['REQUEST_METHOD']='POST';
    $_POST=['vehicle_id'=>1,'poste_id'=>2,'agent'=>'QA','resultats'=>['1'=>'nok'],'_submission_key'=>$argv[1]==='missing_key'?'':str_repeat('a',64)];
    register_shutdown_function(static function () use (&$called): void {
        $case=$GLOBALS['argv'][1];
        if ($called!==($case!=='missing_key') || error_get_last()!==null) { fwrite(STDERR,'Unexpected controller side effect');exit(1); }
        echo 'OK '.$case.PHP_EOL;
    });
    (new \App\Controllers\VerificationController())->store();
}
