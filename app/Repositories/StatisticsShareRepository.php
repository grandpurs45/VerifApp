<?php
declare(strict_types=1);
namespace App\Repositories;

final class StatisticsShareRepository
{
    private function key(int $caserneId): string
    {
        if ($caserneId <= 0) throw new \InvalidArgumentException('Caserne obligatoire.');
        return 'public_statistics_token_caserne_'.$caserneId;
    }

    public function getToken(int $caserneId): ?string
    {
        $value = (new AppSettingRepository())->get($this->key($caserneId));
        return is_string($value) && preg_match('/^[a-f0-9]{64}$/D',$value) === 1 ? $value : null;
    }

    public function isValid(int $caserneId, string $token): bool
    {
        if ($caserneId <= 0 || preg_match('/^[a-f0-9]{64}$/D',$token) !== 1) return false;
        $stored = $this->getToken($caserneId);
        return $stored !== null && hash_equals($stored,$token);
    }

    public function regenerate(int $caserneId): string
    {
        $token = bin2hex(random_bytes(32));
        if (!(new AppSettingRepository())->set($this->key($caserneId),$token)) throw new \RuntimeException('Impossible de generer le lien.');
        return $token;
    }

    public function revoke(int $caserneId): bool
    {
        return (new AppSettingRepository())->delete($this->key($caserneId));
    }
}
