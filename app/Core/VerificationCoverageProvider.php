<?php
declare(strict_types=1);
namespace App\Core;

use App\Repositories\ControleRepository;
use App\Repositories\PosteRepository;

final class VerificationCoverageProvider
{
    public function expectedPostesForVehicles(array $vehicles, int $caserneId): array
    {
        $postes = new PosteRepository();
        $controls = new ControleRepository();
        $expected = [];
        foreach ($vehicles as $vehicle) {
            $id = (int)($vehicle['id'] ?? 0);
            if ($id <= 0) continue;
            foreach ($postes->findByVehicleId($id,$caserneId) as $poste) {
                $posteId = (int)($poste['id'] ?? 0);
                if ($posteId <= 0 || $controls->findByVehicleAndPosteId($id,$posteId,$caserneId) === []) continue;
                $expected[$id.'|'.$posteId] = [
                    'vehicule_id'=>$id,'vehicule_nom'=>(string)($vehicle['nom'] ?? ''),
                    'poste_id'=>$posteId,'poste_nom'=>(string)($poste['nom'] ?? ''),
                    'verification_frequency'=>(string)($vehicle['verification_frequency'] ?? 'daily'),
                ];
            }
        }
        return $expected;
    }
}
