<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Repositories\PharmacyRepository;

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require dirname(__DIR__) . '/app/Core/Autoloader.php';
Autoloader::register();

// An in-memory database exercises the UPDATE without touching a real stock.
$connection = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$connection->exec('CREATE TABLE pharmacie_articles (
    id INTEGER PRIMARY KEY, caserne_id INTEGER, nom TEXT, stock_actuel INTEGER,
    seuil_alerte INTEGER, surveiller_seuil INTEGER, motif_sortie_obligatoire INTEGER, actif INTEGER
)');
$connection->exec("INSERT INTO pharmacie_articles VALUES (1, 10, 'Article test', 58, 15, 1, 1, 1)");
(new ReflectionProperty(Database::class, 'connection'))->setValue(null, $connection);
$repository = new PharmacyRepository();
(new ReflectionProperty($repository, 'articlesTableExists'))->setValue($repository, true);
$check = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$snapshot = static fn (): array => $connection->query('SELECT * FROM pharmacie_articles WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
$before = $snapshot();
$check(!$repository->setArticleActive(20, 1, false), 'An article from another caserne was changed.');
$check($snapshot() === $before, 'Cross-caserne request changed stock.');

$connection->exec("UPDATE pharmacie_articles SET stock_actuel = 42, nom = 'Article modifie ailleurs' WHERE id = 1");
$before = $snapshot();
$check($repository->setArticleActive(10, 1, false), 'Deactivation failed.');
$after = $snapshot();
$check((int) $after['actif'] === 0, 'Article still active.');
unset($before['actif'], $after['actif']);
$check($before === $after, 'Status change overwrote stock or configuration.');
$check($repository->setArticleActive(10, 1, true), 'Reactivation failed.');
$check((int) $snapshot()['stock_actuel'] === 42, 'Reactivation overwrote stock.');
$check(!$repository->setArticleActive(10, 999, false), 'Missing article was reported as updated.');
echo "OK: caserne isolation, activation, deactivation and preservation of concurrent stock changes\n";
