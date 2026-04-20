<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\PasswordPolicy;
use App\Repositories\CaserneRepository;
use App\Repositories\RoleRepository;
use App\Repositories\UserRepository;

final class ManagerUserController
{
    public function index(): void
    {
        $userRepository = new UserRepository();
        $roleRepository = new RoleRepository();
        $caserneRepository = new CaserneRepository();
        $currentUserId = isset($_SESSION['manager_user']['id']) ? (int) $_SESSION['manager_user']['id'] : 0;
        $isPlatformAdmin = $this->isPlatformAdmin($userRepository, $currentUserId);
        $currentCaserneId = $this->currentCaserneId();
        if (!$isPlatformAdmin && $currentCaserneId <= 0) {
            $this->redirect('/index.php?controller=manager_auth&action=select_caserne_form');
        }

        $users = $userRepository->findAll();
        $filteredUsers = [];
        foreach ($users as $user) {
            $isUserPlatformAdmin = strtolower((string) ($user['role'] ?? '')) === 'admin';
            if (!$isPlatformAdmin && $isUserPlatformAdmin) {
                continue;
            }
            $memberships = $userRepository->findMembershipsByUserId((int) ($user['id'] ?? 0));
            $user['caserne_ids'] = array_values(array_map(
                static fn (array $membership): int => (int) ($membership['caserne_id'] ?? 0),
                $memberships
            ));
            $user['caserne_roles'] = [];
            foreach ($memberships as $membership) {
                $caserneId = (int) ($membership['caserne_id'] ?? 0);
                if ($caserneId <= 0) {
                    continue;
                }
                $roleCode = trim((string) ($membership['role_code'] ?? ''));
                $user['caserne_roles'][$caserneId] = $roleCode !== '' ? $roleCode : (string) ($user['role'] ?? 'verificateur');
            }
            if ($isPlatformAdmin) {
                $filteredUsers[] = $user;
                continue;
            }

            $hasCurrentCaserne = in_array($currentCaserneId, (array) $user['caserne_ids'], true);
            if ($hasCurrentCaserne) {
                $filteredUsers[] = $user;
            }
        }
        $users = $filteredUsers;
        $roles = $roleRepository->isAvailable() ? $roleRepository->findAll() : [];
        $casernes = $caserneRepository->findAllActive();
        if (!$isPlatformAdmin && $currentCaserneId > 0) {
            $casernes = array_values(array_filter(
                $casernes,
                static fn (array $caserne): bool => (int) ($caserne['id'] ?? 0) === $currentCaserneId
            ));
        }

        if ($roles === []) {
            $roles = [
                ['code' => 'admin', 'nom' => 'Administrateur'],
                ['code' => 'responsable_materiel', 'nom' => 'Responsable materiel'],
                ['code' => 'verificateur', 'nom' => 'Verificateur'],
            ];
        }

        if (!$isPlatformAdmin) {
            $roles = array_values(array_filter(
                $roles,
                static fn (array $role): bool => strtolower((string) ($role['code'] ?? '')) !== 'admin'
            ));
        }

        require dirname(__DIR__, 2) . '/public/views/manager_users.php';
    }

    public function show(int $id): void
    {
        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $userRepository = new UserRepository();
        $roleRepository = new RoleRepository();
        $caserneRepository = new CaserneRepository();

        $currentUserId = isset($_SESSION['manager_user']['id']) ? (int) $_SESSION['manager_user']['id'] : 0;
        $isPlatformAdmin = $this->isPlatformAdmin($userRepository, $currentUserId);
        $currentCaserneId = $this->currentCaserneId();

        $user = $userRepository->findById($id);
        if ($user === null) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }
        if (!$isPlatformAdmin && strtolower((string) ($user['role'] ?? '')) === 'admin') {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if (!$this->canManageUser($userRepository, $id, $isPlatformAdmin, $currentCaserneId)) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        $memberships = $userRepository->findMembershipsByUserId((int) ($user['id'] ?? 0));
        $user['caserne_ids'] = array_values(array_map(
            static fn (array $membership): int => (int) ($membership['caserne_id'] ?? 0),
            $memberships
        ));
        $user['caserne_roles'] = [];
        foreach ($memberships as $membership) {
            $caserneId = (int) ($membership['caserne_id'] ?? 0);
            if ($caserneId <= 0) {
                continue;
            }
            $roleCode = trim((string) ($membership['role_code'] ?? ''));
            $user['caserne_roles'][$caserneId] = $roleCode !== '' ? $roleCode : (string) ($user['role'] ?? 'verificateur');
        }

        $roles = $roleRepository->isAvailable() ? $roleRepository->findAll() : [];
        $casernes = $caserneRepository->findAllActive();
        if (!$isPlatformAdmin && $currentCaserneId > 0) {
            $casernes = array_values(array_filter(
                $casernes,
                static fn (array $caserne): bool => (int) ($caserne['id'] ?? 0) === $currentCaserneId
            ));
        }
        if ($roles === []) {
            $roles = [
                ['code' => 'admin', 'nom' => 'Administrateur'],
                ['code' => 'responsable_materiel', 'nom' => 'Responsable materiel'],
                ['code' => 'verificateur', 'nom' => 'Verificateur'],
            ];
        }
        if (!$isPlatformAdmin) {
            $roles = array_values(array_filter(
                $roles,
                static fn (array $role): bool => strtolower((string) ($role['code'] ?? '')) !== 'admin'
            ));
        }

        require dirname(__DIR__, 2) . '/public/views/manager_user_show.php';
    }

    public function save(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_users&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $name = trim((string) ($_POST['nom'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $role = trim((string) ($_POST['role'] ?? ''));
        $active = isset($_POST['actif']) && (string) $_POST['actif'] === '1';
        $password = (string) ($_POST['password'] ?? '');
        $caserneEnabled = is_array($_POST['caserne_enabled'] ?? null) ? $_POST['caserne_enabled'] : [];
        $caserneRoles = is_array($_POST['caserne_roles'] ?? null) ? $_POST['caserne_roles'] : [];
        $currentUserId = isset($_SESSION['manager_user']['id']) ? (int) $_SESSION['manager_user']['id'] : 0;
        $currentCaserneId = $this->currentCaserneId();
        $isPlatformAdmin = $this->isPlatformAdmin(new UserRepository(), $currentUserId);
        $normalizedRole = strtolower(trim($role));

        $caserneRepository = new CaserneRepository();
        $allActiveCaserneIds = array_values(array_map(
            static fn (array $caserne): int => (int) ($caserne['id'] ?? 0),
            $caserneRepository->findAllActive()
        ));
        $allActiveCaserneIds = array_values(array_filter($allActiveCaserneIds, static fn (int $id): bool => $id > 0));

        $roleRepository = new RoleRepository();
        $validRoleCodes = [];
        if ($roleRepository->isAvailable()) {
            foreach ($roleRepository->findAll() as $roleRow) {
                $code = trim((string) ($roleRow['code'] ?? ''));
                if ($code !== '') {
                    $validRoleCodes[$code] = true;
                }
            }
        } else {
            foreach (['admin', 'responsable_materiel', 'verificateur'] as $fallbackRole) {
                $validRoleCodes[$fallbackRole] = true;
            }
        }
        if (!$isPlatformAdmin) {
            unset($validRoleCodes['admin']);
        }

        $caserneAssignments = [];
        foreach ($caserneEnabled as $caserneIdRaw => $enabledRaw) {
            $enabled = (string) $enabledRaw === '1';
            $caserneId = (int) $caserneIdRaw;
            if (!$enabled || $caserneId <= 0) {
                continue;
            }

            $membershipRole = trim((string) ($caserneRoles[$caserneIdRaw] ?? $role ?? ''));
            if ($membershipRole === '' || !isset($validRoleCodes[$membershipRole])) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
            }
            $caserneAssignments[$caserneId] = $membershipRole;
        }

        if (!$isPlatformAdmin) {
            if ($currentCaserneId <= 0) {
                $this->redirect('/index.php?controller=manager_auth&action=select_caserne_form');
            }
            if (!isset($caserneAssignments[$currentCaserneId])) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
            }
            $caserneAssignments = [
                $currentCaserneId => (string) $caserneAssignments[$currentCaserneId],
            ];
        }

        $requestedPlatformAdmin = false;
        foreach ($caserneAssignments as $assignedRole) {
            if (strtolower(trim((string) $assignedRole)) === 'admin') {
                $requestedPlatformAdmin = true;
                break;
            }
        }

        if (($normalizedRole === 'admin' || $normalizedRole === 'administrateur') && !$isPlatformAdmin) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=forbidden_admin');
        }
        if ($requestedPlatformAdmin && !$isPlatformAdmin) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=forbidden_admin');
        }
        if (($normalizedRole === 'admin' || $normalizedRole === 'administrateur') || $requestedPlatformAdmin) {
            $caserneAssignments = [];
            foreach ($allActiveCaserneIds as $caserneId) {
                $caserneAssignments[$caserneId] = 'admin';
            }
        }

        if (!$isPlatformAdmin) {
            foreach ($caserneAssignments as $assignedRole) {
                if (strtolower(trim((string) $assignedRole)) === 'admin') {
                    $this->redirect('/index.php?controller=manager_users&action=index&error=forbidden_admin');
                }
            }
        }

        if ($id > 0 && $id === $currentUserId && $currentCaserneId > 0 && !isset($caserneAssignments[$currentCaserneId])) {
            $caserneAssignments[$currentCaserneId] = (string) ($_SESSION['manager_user']['role'] ?? 'verificateur');
        }

        if ($name === '' || $email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $caserneAssignments === []) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $primaryRole = (string) reset($caserneAssignments);
        if ($primaryRole === '' || !isset($validRoleCodes[$primaryRole])) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $userRepository = new UserRepository();

        if ($id > 0) {
            $existing = $userRepository->findById($id);
            if ($existing === null) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
            }
            if (!$this->canManageUser($userRepository, $id, $isPlatformAdmin, $currentCaserneId)) {
                $this->redirect('/index.php?controller=manager&action=forbidden');
            }
            if ((string) ($existing['role'] ?? '') === 'admin' && !$isPlatformAdmin) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=forbidden_admin');
            }

            if ((string) ($existing['role'] ?? '') === 'admin' && !$active) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=admin_lock');
            }

            if ((string) ($existing['role'] ?? '') === 'admin') {
                $active = true;
                $primaryRole = 'admin';
                $caserneAssignments = [];
                foreach ($allActiveCaserneIds as $caserneId) {
                    $caserneAssignments[$caserneId] = 'admin';
                }
            }

            $existingByEmail = $userRepository->findByEmail($email);
            if ($existingByEmail !== null && (int) ($existingByEmail['id'] ?? 0) !== $id) {
                $this->redirect('/index.php?controller=manager_users&action=show&id=' . $id . '&error=email_exists');
            }

            $ok = $userRepository->updateProfile($id, $name, $email, $primaryRole, $active);
            if (!$ok) {
                $this->redirect('/index.php?controller=manager_users&action=show&id=' . $id . '&error=save_failed');
            }

            if (!$userRepository->syncCasernes($id, $caserneAssignments, $primaryRole)) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
            }

            if ($password !== '') {
                $passwordPolicy = PasswordPolicy::validate($password);
                if (($passwordPolicy['ok'] ?? false) !== true) {
                    $this->redirect('/index.php?controller=manager_users&action=index&error=password_policy');
                }

                $userRepository->resetPasswordWithFlag($id, password_hash($password, PASSWORD_BCRYPT));
            }

            $this->redirect('/index.php?controller=manager_users&action=show&id=' . $id . '&success=updated');
        }

        $passwordPolicy = PasswordPolicy::validate($password);
        if (($passwordPolicy['ok'] ?? false) !== true) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=password_policy');
        }

        $existingByEmail = $userRepository->findByEmail($email);
        if ($existingByEmail !== null) {
            $existingUserId = (int) ($existingByEmail['id'] ?? 0);
            if ($existingUserId <= 0) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=create_failed');
            }

            if (strtolower((string) ($existingByEmail['role'] ?? '')) === 'admin' && !$isPlatformAdmin) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=forbidden_admin');
            }

            $existingMemberships = $userRepository->findMembershipsByUserId($existingUserId);
            $mergedAssignments = [];
            foreach ($existingMemberships as $membership) {
                $membershipCaserneId = (int) ($membership['caserne_id'] ?? 0);
                if ($membershipCaserneId <= 0) {
                    continue;
                }
                $membershipRole = trim((string) ($membership['role_code'] ?? ''));
                if ($membershipRole === '') {
                    $membershipRole = (string) ($existingByEmail['role'] ?? 'verificateur');
                }
                $mergedAssignments[$membershipCaserneId] = $membershipRole;
            }
            foreach ($caserneAssignments as $membershipCaserneId => $membershipRole) {
                $mergedAssignments[(int) $membershipCaserneId] = (string) $membershipRole;
            }

            if ($mergedAssignments === [] || !$userRepository->syncCasernes($existingUserId, $mergedAssignments, (string) ($existingByEmail['role'] ?? 'verificateur'))) {
                $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
            }

            $this->redirect('/index.php?controller=manager_users&action=index&success=attached_existing');
        }

        $ok = $userRepository->create(
            $name,
            $email,
            password_hash($password, PASSWORD_BCRYPT),
            $primaryRole,
            $active
        );

        if (!$ok) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=create_failed');
        }

        $newUserId = $userRepository->findLastInsertId();
        if ($newUserId <= 0 || !$userRepository->syncCasernes($newUserId, $caserneAssignments, $primaryRole)) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $this->redirect('/index.php?controller=manager_users&action=index&success=created');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_users&action=index');
        }

        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        if ($id <= 0) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $currentUserId = isset($_SESSION['manager_user']['id']) ? (int) $_SESSION['manager_user']['id'] : 0;
        if ($currentUserId > 0 && $currentUserId === $id) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=self_delete');
        }

        $userRepository = new UserRepository();
        $existing = $userRepository->findById($id);
        if ($existing === null) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $currentCaserneId = $this->currentCaserneId();
        $isPlatformAdmin = $this->isPlatformAdmin($userRepository, $currentUserId);
        if (!$this->canManageUser($userRepository, $id, $isPlatformAdmin, $currentCaserneId)) {
            $this->redirect('/index.php?controller=manager&action=forbidden');
        }

        if ((string) ($existing['role'] ?? '') === 'admin') {
            $this->redirect('/index.php?controller=manager_users&action=index&error=admin_lock');
        }

        if (!$isPlatformAdmin) {
            $targetCaserneIds = $userRepository->findCaserneIdsByUserId($id);
            if (count($targetCaserneIds) > 1) {
                $detached = $userRepository->detachCaserneFromUser($id, $currentCaserneId);
                if (!$detached) {
                    $this->redirect('/index.php?controller=manager_users&action=index&error=delete_failed');
                }

                $this->redirect('/index.php?controller=manager_users&action=index&success=detached');
            }
        }

        $ok = $userRepository->deletePermanently($id);
        if (!$ok) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=delete_failed');
        }

        $this->redirect('/index.php?controller=manager_users&action=index&success=deleted');
    }

    public function bulkStatus(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_users&action=index');
        }

        $targetState = isset($_POST['target_state']) && (string) $_POST['target_state'] === '1';
        $ids = $this->parseSelectedUserIds($_POST['ids_csv'] ?? '');
        if ($ids === []) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=bulk_no_selection');
        }

        $userRepository = new UserRepository();
        $currentUserId = isset($_SESSION['manager_user']['id']) ? (int) $_SESSION['manager_user']['id'] : 0;
        $isPlatformAdmin = $this->isPlatformAdmin($userRepository, $currentUserId);
        $currentCaserneId = $this->currentCaserneId();

        $updated = 0;
        foreach ($ids as $id) {
            if ($id <= 0 || $id === $currentUserId) {
                continue;
            }
            $existing = $userRepository->findById($id);
            if ($existing === null) {
                continue;
            }
            if (!$this->canManageUser($userRepository, $id, $isPlatformAdmin, $currentCaserneId)) {
                continue;
            }
            $isAdmin = strtolower((string) ($existing['role'] ?? '')) === 'admin';
            if ($isAdmin && !$isPlatformAdmin) {
                continue;
            }
            if ($isAdmin && !$targetState) {
                continue;
            }
            if ($userRepository->updateActive($id, $targetState)) {
                $updated++;
            }
        }

        if ($updated <= 0) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=bulk_no_target');
        }

        $this->redirect('/index.php?controller=manager_users&action=index&success=updated');
    }

    public function bulkPassword(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/index.php?controller=manager_users&action=index');
        }

        $ids = $this->parseSelectedUserIds($_POST['ids_csv'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        if ($ids === []) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=bulk_no_selection');
        }
        if ($password !== $passwordConfirm) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=bulk_password_mismatch');
        }
        $passwordPolicy = PasswordPolicy::validate($password);
        if (($passwordPolicy['ok'] ?? false) !== true) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=password_policy');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        if (!is_string($hash) || $hash === '') {
            $this->redirect('/index.php?controller=manager_users&action=index&error=invalid');
        }

        $userRepository = new UserRepository();
        $currentUserId = isset($_SESSION['manager_user']['id']) ? (int) $_SESSION['manager_user']['id'] : 0;
        $isPlatformAdmin = $this->isPlatformAdmin($userRepository, $currentUserId);
        $currentCaserneId = $this->currentCaserneId();

        $updated = 0;
        foreach ($ids as $id) {
            $existing = $userRepository->findById($id);
            if ($existing === null) {
                continue;
            }
            if (!$this->canManageUser($userRepository, $id, $isPlatformAdmin, $currentCaserneId)) {
                continue;
            }
            $isAdmin = strtolower((string) ($existing['role'] ?? '')) === 'admin';
            if ($isAdmin && !$isPlatformAdmin) {
                continue;
            }
            if ($userRepository->resetPasswordWithFlag($id, $hash)) {
                $updated++;
            }
        }

        if ($updated <= 0) {
            $this->redirect('/index.php?controller=manager_users&action=index&error=bulk_no_target');
        }

        $this->redirect('/index.php?controller=manager_users&action=index&success=updated');
    }

    private function parseSelectedUserIds(mixed $raw): array
    {
        $csv = trim((string) $raw);
        if ($csv === '') {
            return [];
        }

        $ids = [];
        foreach (explode(',', $csv) as $token) {
            $token = trim($token);
            if ($token === '' || !ctype_digit($token)) {
                continue;
            }
            $id = (int) $token;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        return array_values($ids);
    }

    private function redirect(string $location): void
    {
        header('Location: ' . $location);
        exit;
    }

    private function currentCaserneId(): int
    {
        return isset($_SESSION['manager_user']['caserne_id']) ? (int) $_SESSION['manager_user']['caserne_id'] : 0;
    }

    private function isPlatformAdmin(UserRepository $userRepository, int $currentUserId): bool
    {
        $currentManager = $currentUserId > 0 ? $userRepository->findById($currentUserId) : null;
        $isPlatformAdmin = $currentManager !== null
            && (
                strtolower((string) ($currentManager['role'] ?? '')) === 'admin'
                || (int) ($currentManager['id'] ?? 0) === 1
            );

        $_SESSION['manager_user']['is_platform_admin'] = $isPlatformAdmin ? 1 : 0;
        if ($currentManager !== null) {
            $_SESSION['manager_user']['global_role'] = (string) ($currentManager['role'] ?? '');
        }

        return $isPlatformAdmin;
    }

    private function canManageUser(UserRepository $userRepository, int $targetUserId, bool $isPlatformAdmin, int $currentCaserneId): bool
    {
        if ($isPlatformAdmin) {
            return true;
        }

        if ($currentCaserneId <= 0) {
            return false;
        }

        $targetCaserneIds = $userRepository->findCaserneIdsByUserId($targetUserId);
        if (!in_array($currentCaserneId, $targetCaserneIds, true)) {
            return false;
        }

        return true;
    }
}
