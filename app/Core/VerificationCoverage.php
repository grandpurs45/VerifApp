<?php

declare(strict_types=1);

namespace App\Core;

use DateTimeImmutable;

final class VerificationCoverage
{
    public static function month(int $year, int $month, array $expectedPostes, array $rows, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        $start = new DateTimeImmutable(sprintf('%04d-%02d-01', $year, $month));
        $daysInMonth = (int)$start->format('t');
        $eligibleDays = $start > $today->modify('first day of this month') ? 0
            : ($start->format('Y-m') === $today->format('Y-m') ? (int)$today->format('j') : $daysInMonth);
        $expectedChecks = 0;
        foreach ($expectedPostes as $poste) $expectedChecks += ($poste['verification_frequency'] ?? 'daily') === 'twice_daily' ? 2 : 1;
        $daily = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $daily[sprintf('%04d-%02d-%02d',$year,$month,$day)] = [
                'postes_verifies'=>[], 'postes_verifies_count'=>0, 'matin_couverts'=>0, 'soir_couverts'=>0, 'jour_couverts'=>0,
                'postes_attendus'=>$expectedChecks, 'pourcentage'=>0, 'complet'=>false, 'eligible'=>$day <= $eligibleDays,
                'total_verifs'=>0, 'conformes'=>0, 'non_conformes'=>0,
            ];
        }
        $totals = ['total_verifs'=>0,'conformes'=>0,'non_conformes'=>0,'postes_couverts'=>0,'jours_complets'=>0];
        foreach ($rows as $row) {
            $date = (string)($row['jour'] ?? '');
            if (!isset($daily[$date])) continue;
            foreach (['total_verifs','conformes','non_conformes'] as $field) {
                $daily[$date][$field] += (int)($row[$field] ?? 0);
                $totals[$field] += (int)($row[$field] ?? 0);
            }
            $key = (int)($row['vehicule_id'] ?? 0) . '|' . (int)($row['poste_id'] ?? 0);
            if (!isset($expectedPostes[$key])) continue;
            if (($expectedPostes[$key]['verification_frequency'] ?? 'daily') === 'twice_daily') {
                if ((int)($row['morning_covered'] ?? 0) === 1) $daily[$date]['postes_verifies'][$key.'|matin'] = true;
                if ((int)($row['evening_covered'] ?? 0) === 1) $daily[$date]['postes_verifies'][$key.'|soir'] = true;
            } else {
                $daily[$date]['postes_verifies'][$key.'|jour'] = true;
            }
        }
        foreach ($daily as &$day) {
            $count = count($day['postes_verifies']);
            $day['postes_verifies_count'] = $count;
            foreach (array_keys($day['postes_verifies']) as $key) {
                $field = str_ends_with($key,'|matin') ? 'matin_couverts' : (str_ends_with($key,'|soir') ? 'soir_couverts' : 'jour_couverts');
                $day[$field]++;
            }
            $day['pourcentage'] = $expectedChecks > 0 ? min(100,(int)round($count / $expectedChecks * 100)) : 0;
            $day['complet'] = $day['eligible'] && $expectedChecks > 0 && $count >= $expectedChecks;
            if ($day['eligible']) {
                $totals['postes_couverts'] += $count;
                if ($day['complet']) $totals['jours_complets']++;
            }
            unset($day['postes_verifies']);
        }
        unset($day);
        $expected = $expectedChecks * $eligibleDays;
        return [
            'dailyCoverage'=>$daily, 'totals'=>$totals, 'daysInMonth'=>$daysInMonth, 'eligibleDays'=>$eligibleDays,
            'expectedPostesPerDay'=>$expectedChecks, 'expectedChecks'=>$expected,
            'rate'=>$expected > 0 ? min(100,(int)round($totals['postes_couverts'] / $expected * 100)) : 0,
        ];
    }

    public static function year(int $year, array $expectedPostes, array $rows, ?DateTimeImmutable $today = null): array
    {
        $today ??= new DateTimeImmutable('today');
        $byMonth = [];
        foreach ($rows as $row) {
            if (str_starts_with((string)($row['jour'] ?? ''),sprintf('%04d-',$year))) $byMonth[(int)substr($row['jour'],5,2)][] = $row;
        }
        $months = []; $covered = 0; $expected = 0; $verifications = 0;
        for ($month = 1; $month <= 12; $month++) {
            $summary = self::month($year,$month,$expectedPostes,$byMonth[$month] ?? [],$today);
            $months[$month] = [
                'rate'=>$summary['eligibleDays'] > 0 && $summary['expectedChecks'] > 0 ? $summary['rate'] : null,
                'covered'=>$summary['totals']['postes_couverts'], 'expected'=>$summary['expectedChecks'],
                'verifications'=>$summary['totals']['total_verifs'], 'eligibleDays'=>$summary['eligibleDays'],
                'daysInMonth'=>$summary['daysInMonth'],
            ];
            $covered += $summary['totals']['postes_couverts'];
            $expected += $summary['expectedChecks'];
            $verifications += $summary['totals']['total_verifs'];
        }
        return ['year'=>$year,'months'=>$months,'covered'=>$covered,'expected'=>$expected,'verifications'=>$verifications,
            'rate'=>$expected > 0 ? min(100,(int)round($covered / $expected * 100)) : null];
    }
}
