<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Repositories\NotificationRepository;
use PDO;
use Throwable;

final class PharmacyStockAlertService
{
    /**
     * @param array<int, int> $articleIds
     */
    public function syncCaserne(int $caserneId, array $articleIds = []): int
    {
        if ($caserneId <= 0 || !$this->isAvailable()) {
            return 0;
        }

        try {
            $articles = $this->findArticles($caserneId, $articleIds);
            $notifications = 0;
            foreach ($articles as $article) {
                $level = self::resolveLevel($article);
                if (!$this->claimNotification($article, $level)) {
                    continue;
                }

                if (!$this->notify($caserneId, $article, $level)) {
                    error_log(sprintf(
                        '[VerifApp pharmacy alerts] notification non remise article=%d niveau=%s',
                        (int) $article['id'],
                        $level
                    ));
                } else {
                    $notifications++;
                }
            }

            return $notifications;
        } catch (Throwable $throwable) {
            error_log('[VerifApp pharmacy alerts] ' . $throwable->getMessage());
            return 0;
        }
    }

    /**
     * @param array<string, mixed> $article
     */
    public static function resolveLevel(array $article): string
    {
        if ((int) ($article['actif'] ?? 0) !== 1) {
            return 'normal';
        }

        $threshold = (float) ($article['seuil_alerte'] ?? 0);
        if ($threshold <= 0) {
            return 'normal';
        }

        $stock = (float) ($article['stock_actuel'] ?? 0);
        if ($stock < $threshold) {
            return 'critical';
        }
        if ((int) ($article['surveiller_seuil'] ?? 0) === 1 && abs($stock - $threshold) < 0.00001) {
            return 'warning';
        }

        return 'normal';
    }

    private function isAvailable(): bool
    {
        try {
            $statement = Database::getConnection()->query("SHOW TABLES LIKE 'pharmacy_stock_alert_states'");
            return $statement !== false && $statement->fetchColumn() !== false;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * @param array<int, int> $articleIds
     * @return array<int, array<string, mixed>>
     */
    private function findArticles(int $caserneId, array $articleIds): array
    {
        $articleIds = array_values(array_unique(array_filter(array_map('intval', $articleIds), static fn (int $id): bool => $id > 0)));
        $params = ['caserne_id' => $caserneId];
        $idCondition = '';
        if ($articleIds !== []) {
            $placeholders = [];
            foreach ($articleIds as $index => $articleId) {
                $key = 'article_' . $index;
                $placeholders[] = ':' . $key;
                $params[$key] = $articleId;
            }
            $idCondition = ' AND id IN (' . implode(',', $placeholders) . ')';
        }

        $statement = Database::getConnection()->prepare('
            SELECT id, caserne_id, nom, unite, stock_actuel, seuil_alerte, surveiller_seuil, actif
            FROM pharmacie_articles
            WHERE caserne_id = :caserne_id' . $idCondition . '
        ');
        foreach ($params as $key => $value) {
            $statement->bindValue(':' . $key, $value, PDO::PARAM_INT);
        }
        $statement->execute();

        return $statement->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string, mixed> $article
     */
    private function claimNotification(array $article, string $level): bool
    {
        $connection = Database::getConnection();
        $upsert = $connection->prepare('
            INSERT INTO pharmacy_stock_alert_states (
                article_id, caserne_id, alert_level, last_notified_level, last_stock, threshold_value
            ) VALUES (
                :article_id, :caserne_id, :alert_level, NULL, :last_stock, :threshold_value
            )
            ON DUPLICATE KEY UPDATE
                last_notified_level = IF(alert_level <> VALUES(alert_level), NULL, last_notified_level),
                alert_level = VALUES(alert_level),
                last_stock = VALUES(last_stock),
                threshold_value = VALUES(threshold_value)
        ');
        $upsert->execute([
            'article_id' => (int) $article['id'],
            'caserne_id' => (int) $article['caserne_id'],
            'alert_level' => $level,
            'last_stock' => (float) $article['stock_actuel'],
            'threshold_value' => $article['seuil_alerte'] !== null ? (float) $article['seuil_alerte'] : null,
        ]);

        if (!in_array($level, ['warning', 'critical'], true)) {
            return false;
        }

        $claim = $connection->prepare('
            UPDATE pharmacy_stock_alert_states
            SET last_notified_level = alert_level, last_notified_at = NOW()
            WHERE article_id = :article_id
              AND alert_level = :alert_level
              AND (last_notified_level IS NULL OR last_notified_level <> alert_level)
        ');
        $claim->execute([
            'article_id' => (int) $article['id'],
            'alert_level' => $level,
        ]);

        return $claim->rowCount() === 1;
    }

    /**
     * @param array<string, mixed> $article
     */
    private function notify(int $caserneId, array $article, string $level): bool
    {
        $stock = $this->formatQuantity((float) $article['stock_actuel'], (string) $article['unite']);
        $threshold = $this->formatQuantity((float) $article['seuil_alerte'], (string) $article['unite']);
        $isCritical = $level === 'critical';
        $eventCode = $isCritical ? 'pharmacy.stock.critical' : 'pharmacy.stock.warning';
        $title = ($isCritical ? 'Stock critique : ' : 'Stock au seuil warning : ') . (string) $article['nom'];
        $message = $isCritical
            ? sprintf('Le stock de %s est passe sous le seuil critique : %s disponible(s), seuil configure a %s.', $article['nom'], $stock, $threshold)
            : sprintf('Le stock de %s a atteint le seuil warning : %s disponible(s), seuil configure a %s.', $article['nom'], $stock, $threshold);

        return (new NotificationRepository())->createForCaserneEvent(
            $caserneId,
            $eventCode,
            $title,
            $message,
            '/index.php?controller=manager_pharmacy&action=index',
            null,
            'Suivi automatique des stocks',
            [
                'alert_level' => $level,
                'article' => (string) $article['nom'],
                'stock_label' => $stock,
                'threshold_label' => $threshold,
            ]
        );
    }

    private function formatQuantity(float $quantity, string $unit): string
    {
        $formatted = abs($quantity - round($quantity)) < 0.00001
            ? (string) (int) round($quantity)
            : number_format($quantity, 2, ',', ' ');

        return trim($formatted . ' ' . $unit);
    }
}
