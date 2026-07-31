<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\ClientIpResolver;
use App\Core\Database;
use PDO;
use Throwable;

final class QrAccessLogRepository
{
    private string $lastError = '';

    public function isAvailable(): bool
    {
        try {
            $statement = Database::getConnection()->query("SHOW TABLES LIKE 'qr_access_logs'");

            return $statement !== false && $statement->fetchColumn() !== false;
        } catch (Throwable $throwable) {
            $this->recordError('controle disponibilite', $throwable);
            return false;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function logOpen(int $caserneId, string $module, ?int $vehicleId, string $token): ?int
    {
        if ($caserneId <= 0 || !in_array($module, ['verifications', 'pharmacy', 'inventory'], true)) {
            $this->lastError = 'Contexte QR invalide.';
            return null;
        }

        try {
            $network = ClientIpResolver::resolve($_SERVER);
            $statement = Database::getConnection()->prepare('
                INSERT INTO qr_access_logs (
                    caserne_id, module, vehicule_id, utilisateur_id, nom_saisi,
                    token_fingerprint, ip_address, proxy_ip_address, user_agent, referer, session_fingerprint
                ) VALUES (
                    :caserne_id, :module, :vehicule_id, :utilisateur_id, :nom_saisi,
                    :token_fingerprint, :ip_address, :proxy_ip_address, :user_agent, :referer, :session_fingerprint
                )
            ');
            $managerUser = is_array($_SESSION['manager_user'] ?? null) ? $_SESSION['manager_user'] : [];
            $userId = (int) ($managerUser['id'] ?? 0);
            $name = trim((string) ($managerUser['nom'] ?? ''));
            $statement->execute([
                'caserne_id' => $caserneId,
                'module' => $module,
                'vehicule_id' => $vehicleId !== null && $vehicleId > 0 ? $vehicleId : null,
                'utilisateur_id' => $userId > 0 ? $userId : null,
                'nom_saisi' => $name !== '' ? mb_substr($name, 0, 150) : null,
                'token_fingerprint' => $token !== '' ? hash('sha256', $token) : null,
                'ip_address' => mb_substr($network['client_ip'], 0, 45),
                'proxy_ip_address' => $network['proxy_ip'] !== null ? mb_substr($network['proxy_ip'], 0, 45) : null,
                'user_agent' => mb_substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? ''), 0, 500) ?: null,
                'referer' => mb_substr((string) ($_SERVER['HTTP_REFERER'] ?? ''), 0, 500) ?: null,
                'session_fingerprint' => session_id() !== '' ? hash('sha256', session_id()) : null,
            ]);
            $id = (int) Database::getConnection()->lastInsertId();
            if ($id > 0) {
                $_SESSION['qr_access_log_id_' . $module] = $id;
            }

            return $id > 0 ? $id : null;
        } catch (Throwable $throwable) {
            $this->recordError('enregistrement ouverture', $throwable);
            return null;
        }
    }

    public function attachIdentity(string $module, string $name, ?int $userId = null): void
    {
        $logId = (int) ($_SESSION['qr_access_log_id_' . $module] ?? 0);
        $name = trim($name);
        if ($logId <= 0 || ($name === '' && ($userId ?? 0) <= 0)) {
            return;
        }

        try {
            $statement = Database::getConnection()->prepare('
                UPDATE qr_access_logs
                SET nom_saisi = CASE WHEN :nom_saisi = \'\' THEN nom_saisi ELSE :nom_saisi END,
                    utilisateur_id = COALESCE(:utilisateur_id, utilisateur_id),
                    identity_updated_at = NOW()
                WHERE id = :id
            ');
            $statement->execute([
                'id' => $logId,
                'nom_saisi' => mb_substr($name, 0, 150),
                'utilisateur_id' => $userId !== null && $userId > 0 ? $userId : null,
            ]);
        } catch (Throwable $throwable) {
            $this->recordError('association identite', $throwable);
        }
    }

    public function findRecent(?int $caserneId, int $limit = 200): array
    {
        return $this->findAll([], $caserneId, $limit);
    }

    /**
     * @param array<string, string> $filters
     * @return array<int, array<string, mixed>>
     */
    public function findAll(array $filters, ?int $caserneId, int $limit = 200): array
    {
        $limit = max(10, min(500, $limit));
        try {
            $where = [];
            $params = [];
            if ($caserneId !== null) {
                $where[] = 'q.caserne_id = :caserne_id';
                $params['caserne_id'] = $caserneId;
            }
            if (($filters['date_from'] ?? '') !== '') {
                $where[] = 'q.opened_at >= :date_from';
                $params['date_from'] = $filters['date_from'] . ' 00:00:00';
            }
            if (($filters['date_to'] ?? '') !== '') {
                $where[] = 'q.opened_at <= :date_to';
                $params['date_to'] = $filters['date_to'] . ' 23:59:59';
            }
            if (($filters['module'] ?? '') !== '') {
                $where[] = 'q.module = :module';
                $params['module'] = $filters['module'];
            }
            if (($filters['identity'] ?? '') !== '') {
                $where[] = '(u.nom LIKE :identity OR q.nom_saisi LIKE :identity)';
                $params['identity'] = '%' . $filters['identity'] . '%';
            }
            if (($filters['ip_address'] ?? '') !== '') {
                $where[] = '(q.ip_address LIKE :ip_address OR q.proxy_ip_address LIKE :ip_address)';
                $params['ip_address'] = '%' . $filters['ip_address'] . '%';
            }

            $sql = '
                SELECT
                    q.*,
                    c.nom AS caserne_nom,
                    v.nom AS vehicule_nom,
                    COALESCE(u.nom, q.nom_saisi) AS identite
                FROM qr_access_logs q
                INNER JOIN casernes c ON c.id = q.caserne_id
                LEFT JOIN vehicules v ON v.id = q.vehicule_id
                LEFT JOIN utilisateurs u ON u.id = q.utilisateur_id
                ' . ($where !== [] ? 'WHERE ' . implode(' AND ', $where) : '') . '
                ORDER BY q.opened_at DESC, q.id DESC
                LIMIT :limit_rows
            ';
            $statement = Database::getConnection()->prepare($sql);
            foreach ($params as $key => $value) {
                $statement->bindValue(':' . $key, $value, $key === 'caserne_id' ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $statement->bindValue(':limit_rows', $limit, PDO::PARAM_INT);
            $statement->execute();

            return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $throwable) {
            $this->recordError('lecture journal', $throwable);
            return [];
        }
    }

    private function recordError(string $operation, Throwable $throwable): void
    {
        $this->lastError = 'Erreur pendant la ' . $operation . '.';
        error_log(sprintf(
            '[VerifApp QR audit] %s: %s',
            $operation,
            $throwable->getMessage()
        ));
    }
}
