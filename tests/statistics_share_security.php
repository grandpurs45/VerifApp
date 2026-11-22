<?php
declare(strict_types=1);
namespace App\Repositories {
    final class AppSettingRepository {
        public static array $values=[];
        public function get(string $key): ?string { return self::$values[$key]??null; }
        public function set(string $key,string $value): bool { self::$values[$key]=$value;return true; }
        public function delete(string $key): bool { unset(self::$values[$key]);return true; }
        public function isAvailable(): bool { return false; }
    }
    final class NotificationRepository { public function isAvailable(): bool { return false; } }
    final class RoleRepository { public function isAvailable(): bool { return false; } }
    final class CaserneRepository {
        public function findById($id): ?array { return $id===10?['id'=>10,'nom'=>'CENTRE <ALPHA>','actif'=>1]:null; }
    }
    final class VehicleRepository {
        public function findAllVerificationEnabled($caserne): array {
            if($caserne!==10)throw new \RuntimeException('Cross-station query');
            $GLOBALS['publicQueries']++;
            return [['id'=>1,'nom'=>'VSSUAP 007','verification_frequency'=>'twice_daily']];
        }
    }
    final class PosteRepository { public function findByVehicleId(...$args): array {return [['id'=>1,'nom'=>'Conducteur'],['id'=>2,'nom'=>'Equipier'],['id'=>3,'nom'=>'Chef']];} }
    final class ControleRepository { public function findByVehicleAndPosteId(...$args): array { return [['id'=>1]]; } }
    final class VerificationRepository {
        public function findAvailableCoverageYears($caserne,$vehicle): array { if($caserne!==10||!in_array($vehicle,[null,1],true))throw new \RuntimeException('Unscoped years');return [2026,2025]; }
        public function findAnnualDailyPosteStats($year,$caserne,$vehicle,$hour): array {
            if($caserne!==10||!in_array($vehicle,[null,1],true))throw new \RuntimeException('Unscoped statistics');
            $GLOBALS['publicYearQueries'][]=$year;
            $rows=[];
            for($month=1;$month<=12;$month++)for($day=1;$day<=20;$day++)for($poste=1;$poste<=3;$poste++) {
                if(($day+$poste+$month)%7===0)continue;
                $rows[]=['jour'=>sprintf('%04d-%02d-%02d',$year,$month,$day),'vehicule_id'=>1,'poste_id'=>$poste,'morning_covered'=>1,'evening_covered'=>($day+$month)%3?1:0,'total_verifs'=>1,'conformes'=>1,'non_conformes'=>0,'agent'=>'SECRET_AGENT','commentaire'=>'SECRET_TERRAIN'];
            }
            return $rows;
        }
    }
    final class AnomalyRepository {
        public function getPublicStatistics($caserne,$start,$end,$vehicle): array {
            if($caserne!==10||!in_array($vehicle,[null,1],true))throw new \RuntimeException('Unscoped anomalies');
            return ['new'=>7,'resolved'=>4,'waiting'=>12,'in_progress'=>3,'urgent'=>2,'reports'=>18];
        }
    }
}
namespace {
    if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
    require dirname(__DIR__).'/app/Core/Autoloader.php';\App\Core\Autoloader::register();
    set_error_handler(static function(int $severity,string $message,string $file,int $line): never {throw new \ErrorException($message,0,$severity,$file,$line);});
    $check=static function(bool $ok,string $message): void {if(!$ok)throw new \RuntimeException($message);};
    $links=new \App\Repositories\StatisticsShareRepository();
    $a=$links->regenerate(10);$b=$links->regenerate(20);
    $check(strlen($a)===64&&$a!==$b,'Weak or duplicate tokens');
    $check($links->isValid(10,$a)&&!$links->isValid(20,$a)&&!$links->isValid(10,''),'Token scope bypassed');
    $rotated=$links->regenerate(10);$check(!$links->isValid(10,$a)&&$links->isValid(10,$rotated),'Old token survived rotation');
    $check($links->revoke(10)&&!$links->isValid(10,$rotated),'Revoked link remains valid');
    $check($links->isValid(20,$b),'Revocation affected another station');
    $token=$links->regenerate(10);
    $renderPublicStatistics=static function(array $filters=[])use($token): string {
        $_GET=array_merge(['caserne_id'=>10,'token'=>$token,'month'=>'2026-11','year'=>'2026','compare_year'=>'2025'],$filters);
        $_SERVER['REQUEST_METHOD']='GET';
        $_SESSION=[]; $GLOBALS['publicQueries']=0;$GLOBALS['publicYearQueries']=[];
        http_response_code(200);ob_start();(new \App\Controllers\StatisticsShareController())->view();return ob_get_clean();
    };
    foreach ([['token'=>''],['token'=>str_repeat('0',64)],['caserne_id'=>20],['caserne_id'=>0]] as $invalid) {
        $html=$renderPublicStatistics($invalid);
        $check(http_response_code()===404&&$GLOBALS['publicQueries']===0&&!str_contains($html,'CENTRE'),'Invalid link exposed data or ran stats');
    }
    $html=$renderPublicStatistics(['vehicule_id'=>99]);
    $check(http_response_code()===404&&!str_contains($html,'VSSUAP'),'Foreign vehicle leaked');
    $html=$renderPublicStatistics(['vehicule_id'=>1]);
    $check(http_response_code()===200&&str_contains($html,'CENTRE &lt;ALPHA&gt;'),'Public page missing or unsafe station label');
    $check(count($GLOBALS['publicYearQueries'])===2,'Public view repeated yearly queries');
    foreach (['SECRET_AGENT','SECRET_TERRAIN','controller=manager','controller=anomalies','action=show','action=export','action=delete','cdn.tailwindcss.com','<form method="post"'] as $forbidden) $check(!str_contains($html,$forbidden),'Public information/action leak: '.$forbidden);
    $check(str_contains($html,'id="planning"')&&str_contains($html,'id="coverage-year-chart"')&&str_contains($html,'Signalements terrain'),'Missing public views');
    preg_match('~<script type="application/json" id="coverage-year-data">(.*?)</script>~s',$html,$match);
    $data=json_decode($match[1],true,512,JSON_THROW_ON_ERROR);
    foreach($data['datasets'] as $dataset)foreach($dataset['links'] as $url){parse_str(parse_url($url,PHP_URL_QUERY),$query);$check($query['caserne_id']==10&&$query['token']===$token&&$query['vehicule_id']==1&&$query['controller']==='statistics_share','Chart link escaped public scope');}
    $_SESSION=['manager_user'=>['role'=>'verificateur','caserne_id'=>10]];$_POST=['share_action'=>'regenerate'];$_SERVER['REQUEST_METHOD']='POST';
    $before=\App\Repositories\AppSettingRepository::$values;ob_start();(new \App\Controllers\StatisticsShareController())->save();ob_end_clean();
    $check(http_response_code()===403&&$before===\App\Repositories\AppSettingRepository::$values,'Unauthorized link management allowed');
    $_SESSION=['manager_user'=>['role'=>'admin','caserne_id'=>10]];$_POST=['share_action'=>'regenerate','caserne_id'=>20];http_response_code(200);
    ob_start();(new \App\Controllers\StatisticsShareController())->save();ob_end_clean();
    $check($links->isValid(20,$b)&&!$links->isValid(10,$token),'Management trusted posted station');
    $check($links->getToken(10)!==$token,'Authorized rotation failed');
    http_response_code(200);restore_error_handler();
    echo "OK: public token generation/rotation/revocation, no-auth rendering, aggregate privacy, station/vehicle isolation, safe links and management rights\n";
}
