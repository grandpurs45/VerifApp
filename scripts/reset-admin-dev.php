<?php

declare(strict_types=1);

use App\Core\Autoloader;
use App\Core\Database;
use App\Core\Env;

require_once dirname(__DIR__) . '/app/Core/Autoloader.php';

Autoloader::register();

$envPath = dirname(__DIR__) . '/.env';
$identifier = 'admin';
$newPassword = 'admin';

if (isset($argv[1]) && $argv[1] !== '') {
    $identifier = (string) $argv[1];
}

if (isset($argv[2]) && $argv[2] !== '') {
    $newPassword = (string) $argv[2];
}

if (isset($argv[3]) && $argv[3] !== '') {
    $envPath = (string) $argv[3];
}

if (!file_exists($envPath)) {
    fwrite(STDERR, "[FAIL] Fichier env introuvable: {$envPath}\n");
    exit(1);
}

Env::load($envPath);

$appEnv = (string) (Env::get('APP_ENV', 'local') ?? 'local');
if ($appEnv !== 'local' && $appEnv !== 'development' && $appEnv !== 'dev') {
    fwrite(STDERR, "[FAIL] Script reserve au dev (APP_ENV actuel: {$appEnv}).\n");
    fwrite(STDERR, "       Utilise APP_ENV=local/development/dev pour l'executer.\n");
    exit(1);
}

try {
    $connection = Database::getConnection();
} catch (\Throwable $throwable) {
    fwrite(STDERR, "[FAIL] Connexion DB impossible: " . $throwable->getMessage() . "\n");
    exit(1);
}

$hasMustChangePassword = false;
try {
    $columnStatement = $connection->query("SHOW COLUMNS FROM utilisateurs LIKE 'must_change_password'");
    $hasMustChangePassword = $columnStatement !== false && $columnStatement->fetchColumn() !== false;
} catch (\Throwable $throwable) {
    $hasMustChangePassword = false;
}

$findStatement = $connection->prepare(
    "
    SELECT id, nom, email, role
    FROM utilisateurs
    WHERE nom = :identifier OR email = :identifier
    ORDER BY id ASC
    LIMIT 1
"
);
$findStatement->execute(['identifier' => $identifier]);
$user = $findStatement->fetch(\PDO::FETCH_ASSOC);

if ($user === false) {
    $fallbackStatement = $connection->query(
        "
        SELECT id, nom, email, role
        FROM utilisateurs
        WHERE role IN ('admin', 'responsable_materiel')
        ORDER BY
            CASE WHEN role = 'admin' THEN 1 ELSE 2 END,
            id ASC
        LIMIT 1
    "
    );
    $fallbackUser = $fallbackStatement !== false ? $fallbackStatement->fetch(\PDO::FETCH_ASSOC) : false;
    if ($fallbackUser !== false) {
        $user = $fallbackUser;
    }
}

if ($user === false) {
    fwrite(STDERR, "[FAIL] Aucun compte cible trouve pour l'identifiant: {$identifier}\n");
    exit(1);
}

$hash = password_hash($newPassword, PASSWORD_BCRYPT);

if ($hash === false) {
    fwrite(STDERR, "[FAIL] Echec hash mot de passe.\n");
    exit(1);
}

if ($hasMustChangePassword) {
    $updateStatement = $connection->prepare(
        '
        UPDATE utilisateurs
        SET mot_de_passe = :mot_de_passe,
            must_change_password = 1,
            actif = 1
        WHERE id = :id
        '
    );
} else {
    $updateStatement = $connection->prepare(
        '
        UPDATE utilisateurs
        SET mot_de_passe = :mot_de_passe,
            actif = 1
        WHERE id = :id
        '
    );
}

$ok = $updateStatement->execute([
    'mot_de_passe' => $hash,
    'id' => (int) $user['id'],
]);

if (!$ok) {
    fwrite(STDERR, "[FAIL] Echec mise a jour du mot de passe admin.\n");
    exit(1);
}

echo "[OK] Compte reinitialise.\n";
echo "     id: " . (int) $user['id'] . "\n";
echo "     nom: " . (string) $user['nom'] . "\n";
echo "     email: " . (string) $user['email'] . "\n";
echo "     role: " . (string) $user['role'] . "\n";
echo "     nouveau mot de passe: {$newPassword}\n";
if ($hasMustChangePassword) {
    echo "     changement obligatoire au prochain login: OUI\n";
}
