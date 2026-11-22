<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Env;
use App\Core\ManagerAccess;
use App\Core\UrlHelper;
use App\Core\VerificationCoverage;
use App\Core\VerificationCoverageProvider;
use App\Repositories\AnomalyRepository;
use App\Repositories\AppSettingRepository;
use App\Repositories\CaserneRepository;
use App\Repositories\StatisticsShareRepository;
use App\Repositories\VehicleRepository;
use App\Repositories\VerificationRepository;
use DateTimeImmutable;

final class StatisticsShareController
{
    private function privateHeaders(): void
    {
        header('Cache-Control: private, no-store, max-age=0');
        header('Referrer-Policy: no-referrer');
        header('X-Robots-Tag: noindex, nofollow, noarchive');
        header('X-Content-Type-Options: nosniff');
    }

    private function unavailable(int $status = 404): void
    {
        http_response_code($status);
        echo '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Lien indisponible</title><body><h1>Lien indisponible</h1><p>Ce lien est invalide ou a ete desactive.</p></body></html>';
    }

    private function managerCaserneId(): int
    {
        $user = $_SESSION['manager_user'] ?? [];
        $id = (int)($user['caserne_id'] ?? 0);
        return $id > 0 && ManagerAccess::hasPermission((string)($user['role'] ?? ''),'users.manage') ? $id : 0;
    }

    public function manage(): void
    {
        $this->privateHeaders();
        $caserneId = $this->managerCaserneId();
        if ($caserneId <= 0) { $this->unavailable(403); return; }
        $caserne = (new CaserneRepository())->findById($caserneId);
        if (!$caserne) { $this->unavailable(); return; }
        $token = (new StatisticsShareRepository())->getToken($caserneId);
        $shareUrl = '';
        if ($token !== null) {
            $base = rtrim((string)Env::get('APP_URL',''),'/');
            if (!in_array(parse_url($base,PHP_URL_SCHEME),['http','https'],true)) $base = UrlHelper::resolvePublicBaseUrl();
            $shareUrl = $base . '/index.php?' . http_build_query(['controller'=>'statistics_share','action'=>'view','caserne_id'=>$caserneId,'token'=>$token]);
        }
        $error = (string)($_GET['error'] ?? '');
        $success = (string)($_GET['success'] ?? '');
        require dirname(__DIR__,2).'/public/views/statistics_share_manage.php';
    }

    public function save(): void
    {
        $this->privateHeaders();
        $caserneId = $this->managerCaserneId();
        if ($caserneId <= 0) { $this->unavailable(403); return; }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); header('Allow: POST'); return; }
        $action = is_string($_POST['share_action'] ?? null) ? $_POST['share_action'] : '';
        $repository = new StatisticsShareRepository();
        $failed = false;
        try {
            if ($action === 'generate') {
                if ($repository->getToken($caserneId) === null) $repository->regenerate($caserneId);
            } elseif ($action === 'regenerate') {
                $repository->regenerate($caserneId);
            } elseif ($action === 'revoke') {
                if (!$repository->revoke($caserneId)) $failed = true;
            } else { $failed = true; }
        } catch (\Throwable) { $failed = true; }
        header('Location: /index.php?controller=statistics_share&action=manage&'.($failed?'error=save_failed':'success=saved'));
    }

    public function view(): void
    {
        $this->privateHeaders();
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'");
        if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET',['GET','HEAD'],true)) { http_response_code(405); header('Allow: GET, HEAD'); return; }
        $caserneId = (int)($_GET['caserne_id'] ?? 0);
        $token = is_string($_GET['token'] ?? null) ? $_GET['token'] : '';
        if (!(new StatisticsShareRepository())->isValid($caserneId,$token)) { $this->unavailable(); return; }
        $caserne = (new CaserneRepository())->findById($caserneId);
        if (!$caserne || (int)($caserne['actif'] ?? 0) !== 1) { $this->unavailable(); return; }
        if (session_status() === PHP_SESSION_ACTIVE) session_write_close();
        $today = new DateTimeImmutable('today');
        $monthValue = is_string($_GET['month'] ?? null) ? $_GET['month'] : '';
        if (!preg_match('/^(20\d{2}|2100)-(0[1-9]|1[0-2])$/D',$monthValue)) $monthValue = $today->format('Y-m');
        [$monthYear,$month] = array_map('intval',explode('-',$monthValue));
        $selectedYear = filter_var($_GET['year'] ?? '',FILTER_VALIDATE_INT);
        if ($selectedYear === false || $selectedYear < 2000 || $selectedYear > 2100) $selectedYear = $monthYear;
        $vehicles = (new VehicleRepository())->findAllVerificationEnabled($caserneId);
        $vehiclesById = [];
        foreach ($vehicles as $vehicle) $vehiclesById[(int)$vehicle['id']] = $vehicle;
        $selectedVehicleId = max(0,(int)($_GET['vehicule_id'] ?? 0));
        if ($selectedVehicleId > 0 && !isset($vehiclesById[$selectedVehicleId])) { $this->unavailable(); return; }
        $expectedPostes = (new VerificationCoverageProvider())->expectedPostesForVehicles($selectedVehicleId > 0 ? [$vehiclesById[$selectedVehicleId]] : $vehicles,$caserneId);
        $setting = new AppSettingRepository();
        $eveningHour = (int)($setting->get('verification_evening_hour_caserne_'.$caserneId) ?? $setting->get('verification_evening_hour') ?? Env::get('VERIFICATION_EVENING_HOUR','18'));
        if ($eveningHour < 0 || $eveningHour > 23) $eveningHour = 18;
        $repository = new VerificationRepository();
        $availableYears = $repository->findAvailableCoverageYears($caserneId,$selectedVehicleId ?: null);
        $comparisonYear = filter_var($_GET['compare_year'] ?? '',FILTER_VALIDATE_INT);
        if ($comparisonYear === false || $comparisonYear === $selectedYear || !in_array($comparisonYear,$availableYears,true)) $comparisonYear = 0;
        $comparisonOptions = array_values(array_filter($availableYears,static fn(int $year): bool => $year !== $selectedYear));
        $yearOptions = array_values(array_unique(array_merge($availableYears,[$selectedYear,(int)$today->format('Y')]))); rsort($yearOptions);
        $rowsByYear = [];
        foreach (array_unique(array_filter([$monthYear,$selectedYear,$comparisonYear])) as $year) $rowsByYear[$year] = $repository->findAnnualDailyPosteStats($year,$caserneId,$selectedVehicleId ?: null,$eveningHour);
        $coverage = VerificationCoverage::month($monthYear,$month,$expectedPostes,$rowsByYear[$monthYear],$today);
        $dailyCoverage=$coverage['dailyCoverage']; $totals=$coverage['totals']; $daysInMonth=$coverage['daysInMonth'];
        $eligibleDays=$coverage['eligibleDays']; $expectedPostesPerDay=$coverage['expectedPostesPerDay']; $conformityRate=$coverage['rate'];
        $monthNames=['Janvier','Fevrier','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Decembre'];
        $monthLabel=$monthNames[$month-1].' '.$monthYear;
        $start = new DateTimeImmutable($monthValue.'-01');
        $anomalyStats = (new AnomalyRepository())->getPublicStatistics($caserneId,$start->format('Y-m-d').' 00:00:00',$start->modify('+1 month')->format('Y-m-d').' 00:00:00',$selectedVehicleId ?: null);
        $baseParams=['controller'=>'statistics_share','action'=>'view','caserne_id'=>$caserneId,'token'=>$token];
        $publicUrl = static fn(array $filters): string => '/index.php?'.http_build_query(array_merge($baseParams,$filters));
        $yearSummaries=[]; $chartDatasets=[];
        foreach (array_filter([$selectedYear,$comparisonYear]) as $year) {
            $summary=VerificationCoverage::year($year,$expectedPostes,$rowsByYear[$year],$today); $yearSummaries[$year]=$summary;
            $links=[];
            for ($m=1;$m<=12;$m++) $links[]=$publicUrl(['month'=>sprintf('%04d-%02d',$year,$m),'year'=>$selectedYear,'compare_year'=>$comparisonYear,'vehicule_id'=>$selectedVehicleId]).'#planning';
            $chartDatasets[]=['label'=>(string)$year,'data'=>array_column($summary['months'],'rate'),'months'=>array_values($summary['months']),'links'=>$links];
        }
        $primarySummary=$yearSummaries[$selectedYear];
        $chartData=['labels'=>['Jan','Fev','Mar','Avr','Mai','Juin','Juil','Aout','Sep','Oct','Nov','Dec'],'datasets'=>$chartDatasets];
        require dirname(__DIR__,2).'/public/views/statistics_public.php';
    }
}
