<?php

declare(strict_types=1);

// Isolated repositories exercise the real controller without touching a database.
namespace App\Repositories {
    final class UserRepository
    {
        public static array $user;

        public function findById(int $id): ?array
        {
            return self::$user;
        }

        public function updatePasswordAndClearFlag(int $id, string $hash): bool
        {
            if ($GLOBALS['scenario'] === 'save_failure') {
                return false;
            }
            self::$user['mot_de_passe'] = $hash;
            self::$user['must_change_password'] = 0;
            return true;
        }
    }

    final class CaserneRepository
    {
        public function findByUserId(int $id): array
        {
            $role = match ($GLOBALS['scenario']) {
                'blank_role' => '   ',
                'denied' => 'restricted',
                default => 'verificateur',
            };
            $casernes = [['id' => 10, 'nom' => 'Test', 'role_code' => $role]];
            if ($GLOBALS['scenario'] === 'multiple') {
                $casernes[] = ['id' => 11, 'nom' => 'Test 2', 'role_code' => 'verificateur'];
            }
            return $casernes;
        }
    }

    final class RoleRepository
    {
        public function isAvailable(): bool
        {
            return true;
        }

        public function findPermissionCodesByRoleCode(string $role): array
        {
            return $role === 'verificateur' ? ['dashboard.view'] : ['pharmacy.manage'];
        }
    }

    final class AppSettingRepository
    {
        public function isAvailable(): bool
        {
            return false;
        }
    }

    final class LoginEventRepository
    {
        public static array $events = [];

        public function logEvent(...$args): void
        {
            self::$events[] = $args;
        }
    }
}

namespace {
    use App\Controllers\AuthController;
    use App\Repositories\LoginEventRepository;
    use App\Repositories\UserRepository;

    if (PHP_SAPI !== 'cli') {
        http_response_code(404);
        exit;
    }

    require dirname(__DIR__) . '/app/Core/Autoloader.php';
    \App\Core\Autoloader::register();

    $scenarios = ['allowed', 'blank_role', 'denied', 'multiple', 'inactive', 'save_failure', 'wrong_password'];
    $scenario = $argv[1] ?? '';
    if ($scenario === '') {
        foreach ($scenarios as $case) {
            $process = proc_open([PHP_BINARY, __FILE__, $case], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
            if (!is_resource($process)) {
                throw new \RuntimeException('Unable to start test: ' . $case);
            }
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);
            if ($exitCode !== 0 || $errors !== '' || trim($output) !== 'OK ' . $case) {
                fwrite(STDERR, 'FAIL ' . $case . ': ' . $output . $errors . PHP_EOL);
                exit(1);
            }
            echo $output;
        }
        exit;
    }
    if (!in_array($scenario, $scenarios, true)) {
        exit(1);
    }

    $oldPassword = 'Temporary123!';
    $newPassword = 'Replacement456!';
    UserRepository::$user = [
        'id' => 42, 'nom' => 'Test', 'prenom' => 'User', 'login' => 'test.user',
        'email' => 'test@example.invalid', 'role' => 'verificateur',
        'actif' => $scenario === 'inactive' ? 0 : 1,
        'must_change_password' => 1,
        'mot_de_passe' => password_hash($oldPassword, PASSWORD_BCRYPT),
    ];
    $_SESSION = ['manager_password_reset_user' => ['id' => 42]];
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    $_SERVER['HTTP_USER_AGENT'] = 'Auth regression test';
    $_POST = [
        'current_password' => $scenario === 'wrong_password' ? 'incorrect' : $oldPassword,
        'new_password' => $newPassword,
        'confirm_password' => $newPassword,
    ];

    register_shutdown_function(static function () use ($scenario, $oldPassword, $newPassword): void {
        $check = static function (bool $condition, string $message): void {
            if (!$condition) {
                fwrite(STDERR, $message . PHP_EOL);
                exit(1);
            }
        };
        $unchanged = in_array($scenario, ['inactive', 'save_failure', 'wrong_password'], true);
        $check(password_verify($unchanged ? $oldPassword : $newPassword, UserRepository::$user['mot_de_passe']), 'Unexpected saved password');
        $check(UserRepository::$user['must_change_password'] === ($unchanged ? 1 : 0), 'Unexpected reset flag');
        $authenticated = in_array($scenario, ['allowed', 'blank_role'], true);
        $check(isset($_SESSION['manager_user']) === $authenticated, 'Unexpected authenticated session');
        if ($authenticated) {
            $check($_SESSION['manager_user']['role'] === 'verificateur', 'Unexpected resolved role');
        }
        if ($scenario === 'multiple') {
            $check(count($_SESSION['manager_caserne_pending']['casernes'] ?? []) === 2, 'Missing caserne selection');
            $check($_SESSION['manager_caserne_pending']['after_password_change'] === 1, 'Missing password change confirmation');
        }
        if ($scenario === 'denied' || $authenticated) {
            $event = LoginEventRepository::$events[0] ?? [];
            $check(($event[6] ?? '') === ($scenario === 'denied' ? 'no_manager_access' : 'login_ok'), 'Unexpected audit reason');
            $check(($event[3] ?? '') === '127.0.0.1', 'Client IP lost after password change');
        }
        if (in_array($scenario, ['save_failure', 'wrong_password'], true)) {
            $check(isset($_SESSION['manager_password_reset_user']), 'Pending password session lost');
        }
        echo 'OK ' . $scenario . PHP_EOL;
    });

    (new AuthController())->changePassword();
}
