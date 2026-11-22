<?php
declare(strict_types=1);
namespace App\Repositories {
    final class NotificationRepository { public function isAvailable(): bool { return false; } }
    final class AppSettingRepository { public function isAvailable(): bool { return false; } }
    final class VehicleRepository {
        public function findAllVerificationEnabled($caserne): array {
            if ($caserne!==10) throw new \RuntimeException('Wrong station');
            return [['id'=>1,'nom'=>'VSSUAP <007>','verification_frequency'=>'twice_daily']];
        }
    }
    final class PosteRepository {
        public function findByVehicleId($vehicle,$caserne): array {
            if ($vehicle!==1 || $caserne!==10) throw new \RuntimeException('Wrong expected scope');
            return [['id'=>1,'nom'=>'Conducteur'],['id'=>2,'nom'=>'Equipier'],['id'=>3,'nom'=>'Chef']];
        }
    }
    final class ControleRepository { public function findByVehicleAndPosteId(...$args): array { return [['id'=>1]]; } }
    final class VerificationRepository {
        public function findAvailableCoverageYears($caserne,$vehicle): array {
            if ($caserne!==10 || !in_array($vehicle,[null,1],true)) throw new \RuntimeException('Wrong year scope');
            return $GLOBALS['fixtureYears'];
        }
        public function findAnnualDailyPosteStats($year,$caserne,$vehicle,$hour): array {
            if ($caserne!==10 || !in_array($vehicle,[null,1],true) || $hour!==18) throw new \RuntimeException('Wrong stats scope');
            $GLOBALS['yearQueries'][]=$year;
            if (!in_array($year,$GLOBALS['fixtureYears'],true)) return [];
            $rows=[]; $today=new \DateTimeImmutable('today');
            for ($month=1;$month<=12;$month++) {
                $start=new \DateTimeImmutable(sprintf('%04d-%02d-01',$year,$month));
                if ($start>$today) continue;
                $days=$start->format('Y-m')===$today->format('Y-m')?(int)$today->format('j'):(int)$start->format('t');
                $target=(int)floor($days*6*(55+(($month*7+$year)%40))/100);
                for($day=1;$day<=$days;$day++) for($poste=1;$poste<=3;$poste++) {
                    if($target<=0) continue;
                    $both=$target>=2; $target-=$both?2:1;
                    $rows[]=['jour'=>sprintf('%04d-%02d-%02d',$year,$month,$day),'vehicule_id'=>1,'poste_id'=>$poste,'total_verifs'=>1,'conformes'=>1,'non_conformes'=>0,'morning_covered'=>1,'evening_covered'=>$both?1:0];
                }
            }
            return $rows;
        }
    }
}
namespace {
    if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
    require dirname(__DIR__).'/app/Core/Autoloader.php'; \App\Core\Autoloader::register();
    $renderYearlyView=static function(array $query,array $years=[2026,2025]): string {
        $_GET=array_merge(['controller'=>'verifications','action'=>'yearly'],$query);
        $_SESSION=['manager_user'=>['role'=>'admin','caserne_id'=>10,'caserne_nom'=>'INGRE','nom'=>'QA']];
        $GLOBALS['fixtureYears']=$years; $GLOBALS['yearQueries']=[];
        ob_start(); (new \App\Controllers\VerificationController())->yearly(); return ob_get_clean();
    };
    $check=static function(bool $ok,string $message): void {if(!$ok) throw new \RuntimeException($message);};
    $parse=static function(string $html): array {
        if(!preg_match('~<script type="application/json" id="coverage-year-data">(.*?)</script>~s',$html,$match)) throw new \RuntimeException('Missing chart data');
        return json_decode($match[1],true,512,JSON_THROW_ON_ERROR);
    };
    $html=$renderYearlyView(['year'=>'2026','compare_year'=>'2025','vehicule_id'=>'1']);
    $data=$parse($html);
    $check(count($data['datasets'])===2 && count($data['datasets'][0]['data'])===12,'Missing comparison or months');
    $check($GLOBALS['yearQueries']===[2026,2025],'Queries not batched per year');
    $check(str_contains($html,'VSSUAP &lt;007&gt;') && !str_contains($html,'>VSSUAP <007>'),'Vehicle label not escaped');
    foreach($data['datasets'] as $dataset) foreach($dataset['links'] as $url) $check(str_contains($url,'vehicule_id=1') && str_contains($url,'action=monthly'),'Drill-down lost vehicle');
    $single=$parse($renderYearlyView(['year'=>'2026','compare_year'=>'2026']));
    $check(count($single['datasets'])===1,'Same year compared twice');
    $singleYearHtml=$renderYearlyView(['year'=>'2026'],[2026]);
    $check(str_contains($singleYearHtml,'Aucune autre annee'),'Single-year state missing');
    $invalid=$parse($renderYearlyView(['year'=>'invalid','vehicule_id'=>'999','compare_year'=>'9999']));
    $check(count($invalid['datasets'])===1,'Invalid comparison accepted');
    $check(str_contains($invalid['datasets'][0]['links'][0],'vehicule_id=0'),'Unknown vehicle scope not reset');
    $futureYear=(int)date('Y')+1;
    $future=$parse($renderYearlyView(['year'=>(string)$futureYear]));
    $check(array_filter($future['datasets'][0]['data'],static fn($value)=>$value!==null)===[],'Future year plotted as zero');
    echo "OK: yearly controller/view, comparison, one-year state, filters, year batching, empty/future data and escaping (no database)\n";
}
