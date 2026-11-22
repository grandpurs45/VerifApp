<?php

declare(strict_types=1);

namespace App\Repositories {
    final class NotificationRepository { public function isAvailable(): bool { return false; } }
    final class AppSettingRepository { public function isAvailable(): bool { return false; } }
}
namespace {
    if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
    require dirname(__DIR__) . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();
    set_error_handler(static function(int $severity,string $message,string $file,int $line): never { throw new \ErrorException($message,0,$severity,$file,$line); });
    $renderMonthlyView = static function (bool $twiceDaily = true): string {
        $_GET=['controller'=>'verifications','action'=>'monthly'];
        $_SESSION=['manager_user'=>['role'=>'admin','nom'=>'QA','caserne_nom'=>'INGRE']];
        $monthValue='2026-11'; $monthLabel='novembre 2026'; $daysInMonth=30; $eligibleDays=2;
        $selectedVehicleId=1; $vehicles=[['id'=>1,'nom'=>'VSSUAP 007']];
        $verificationEveningHour=18;
        $expectedPostesPerDay=$twiceDaily?6:3;
        $conformityRate=100;
        $totals=['total_verifs'=>6,'jours_complets'=>2];
        $expectedPostes=[];
        foreach (['Conducteur','Equipier',"Chef d agres"] as $index=>$name) {
            $expectedPostes[]=['vehicule_nom'=>'VSSUAP 007','poste_nom'=>$name,'verification_frequency'=>$twiceDaily?'twice_daily':'daily'];
        }
        $dailyCoverage=[];
        foreach ([1,2] as $day) {
            $dailyCoverage[sprintf('2026-11-%02d',$day)]=[
                'postes_verifies_count'=>$expectedPostesPerDay,'postes_attendus'=>$expectedPostesPerDay,
                'pourcentage'=>100,'complet'=>true,'eligible'=>true,'non_conformes'=>0,
                'total_verifs'=>3,'matin_couverts'=>$twiceDaily?3:0,'soir_couverts'=>$twiceDaily?3:0,'jour_couverts'=>$twiceDaily?0:3,
            ];
        }
        ob_start();
        require dirname(__DIR__) . '/public/views/verifications_monthly.php';
        return ob_get_clean();
    };
    $check=static function(bool $ok,string $message):void { if(!$ok) throw new \RuntimeException($message); };
    foreach ([true,false] as $twiceDaily) {
        $html=$renderMonthlyView($twiceDaily);
        $dom=new \DOMDocument(); $previous=libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="UTF-8">'.$html);libxml_clear_errors();libxml_use_internal_errors($previous);
        $xpath=new \DOMXPath($dom);
        foreach (['Verifications enregistrees'=>'6','Creneaux attendus / jour'=>$twiceDaily?'6':'3','Taux de couverture'=>'100%'] as $label=>$value) {
            $metric=$xpath->query('//article[normalize-space(p[1])="'.$label.'"]/p[2]');
            $check($metric->length===1 && trim($metric->item(0)->textContent)===$value,'Incorrect metric label/value: '.$label);
        }
        $rows=$xpath->query('//table/tbody/tr');
        $cells=$rows->item(0)->getElementsByTagName('td');
        $check(trim($cells->item(1)->textContent)===($twiceDaily?'6':'3'),'Wrong covered slots');
        $check(trim($cells->item(5)->textContent)==='3','Three 24h forms were incorrectly doubled');
        $check(str_contains($html,'une verification en garde de 24 h couvre 2 creneaux'),'Missing 24h distinction');
        $check(!str_contains($html,'>Conformite<') && !str_contains($html,'Verifs attendues / jour'),'Ambiguous old label remains');
    }
    echo "OK: monthly labels distinguish recorded forms, daily expected slots and coverage; 3 forms / 6 slots and daily mode preserved\n";
    restore_error_handler();
}
