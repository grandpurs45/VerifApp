<?php
declare(strict_types=1);
use App\Core\VerificationCoverage;
require dirname(__DIR__).'/app/Core/Autoloader.php';
App\Core\Autoloader::register();
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$check=static function(bool $ok,string $message): void {if(!$ok) throw new RuntimeException($message);};
$expected=['1|1'=>['verification_frequency'=>'twice_daily'],'1|2'=>['verification_frequency'=>'daily']];
$rows=[
 ['jour'=>'2026-11-01','vehicule_id'=>1,'poste_id'=>1,'morning_covered'=>1,'evening_covered'=>1,'total_verifs'=>1,'conformes'=>0,'non_conformes'=>1],
 ['jour'=>'2026-11-01','vehicule_id'=>1,'poste_id'=>2,'morning_covered'=>1,'evening_covered'=>0,'total_verifs'=>1,'conformes'=>1,'non_conformes'=>0],
 ['jour'=>'2026-11-02','vehicule_id'=>1,'poste_id'=>1,'morning_covered'=>1,'evening_covered'=>0,'total_verifs'=>3],
];
$today=new DateTimeImmutable('2026-11-02');
$month=VerificationCoverage::month(2026,11,$expected,$rows,$today);
$check($month['expectedPostesPerDay']===3 && $month['expectedChecks']===6,'Expected mixed-frequency slots incorrect');
$check($month['totals']['postes_couverts']===4 && $month['totals']['total_verifs']===5 && $month['rate']===67,'Coverage confused with form totals or conformity');
$check($month['dailyCoverage']['2026-11-01']['pourcentage']===100 && $month['dailyCoverage']['2026-11-02']['pourcentage']===33,'Daily coverage changed');
$check(!$month['dailyCoverage']['2026-11-03']['eligible'],'Future day eligible');
$year=VerificationCoverage::year(2026,$expected,$rows,$today);
$check($year['months'][11]['rate']===$month['rate'] && $year['months'][11]['expected']===$month['expectedChecks'],'Annual/monthly mismatch');
$check($year['months'][12]['rate']===null && $year['months'][12]['expected']===0,'Future month plotted as zero');
$check($year['months'][1]['rate']===0,'Past month without forms differs from monthly view');
$empty=VerificationCoverage::year(2026,[],$rows,$today);
$check($empty['rate']===null && $empty['months'][11]['rate']===null,'No objective plotted as zero');
$future=VerificationCoverage::year(2027,$expected,[],$today);
$check($future['expected']===0 && $future['rate']===null,'Future year denominator included');
$one=['1|1'=>['verification_frequency'=>'daily']];
$leap=VerificationCoverage::month(2024,2,$one,[],new DateTimeImmutable('2026-11-22'));
$check($leap['daysInMonth']===29 && $leap['expectedChecks']===29,'Leap year incorrect');
$weightedRows=[];
for($day=1;$day<=31;$day++) $weightedRows[]=['jour'=>sprintf('2026-01-%02d',$day),'vehicule_id'=>1,'poste_id'=>1,'total_verifs'=>1];
$weighted=VerificationCoverage::year(2026,$one,$weightedRows,new DateTimeImmutable('2026-02-28'));
$check($weighted['rate']===(int)round(31/59*100) && $weighted['rate']!==50,'Year rate is an unweighted mean');
$duplicate=VerificationCoverage::month(2026,11,$expected,array_merge($rows,[$rows[0]]),$today);
$check($duplicate['totals']['postes_couverts']===4,'Duplicate rows doubled covered slots');
echo "OK: shared monthly/annual coverage, mixed frequencies, 24h slots, weighted year, leap year, future/empty states and no double counting\n";
