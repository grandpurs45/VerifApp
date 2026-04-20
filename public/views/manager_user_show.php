<?php

declare(strict_types=1);

$roleOptions = [];
foreach ($roles as $role) {
    $code = (string) ($role['code'] ?? '');
    $name = (string) ($role['nom'] ?? $code);
    if ($code !== '') {
        $roleOptions[$code] = $name;
    }
}
$defaultRoleCode = 'verificateur';
if (!isset($roleOptions[$defaultRoleCode])) {
    $firstRoleCode = array_key_first($roleOptions);
    $defaultRoleCode = is_string($firstRoleCode) ? $firstRoleCode : '';
}
$passwordPolicy = \App\Core\PasswordPolicy::policy();
$passwordMinLength = (int) ($passwordPolicy['min_length'] ?? 12);

$managerUser = $_SESSION['manager_user'] ?? [];
$selectedIsCurrent = isset($managerUser['id']) && (int) $managerUser['id'] === (int) ($user['id'] ?? 0);
$selectedIsAdmin = (string) ($user['role'] ?? '') === 'admin';
$selectedLockedAdmin = $selectedIsAdmin && !$isPlatformAdmin;
$isPlatformRoleReadOnly = $selectedIsAdmin;

$pageTitle = 'Fiche utilisateur - VerifApp';
$pageHeading = 'Fiche utilisateur';
$pageSubtitle = 'Edition complete du compte et de ses affectations.';
$pageBackUrl = '/index.php?controller=manager_users&action=index';
$pageBackLabel = 'Retour utilisateurs';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (isset($_GET['success'])): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
        Modification enregistree avec succes.
    </section>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <?php if ((string) $_GET['error'] === 'email_exists'): ?>
            Cet email est deja utilise par un autre compte.
        <?php else: ?>
            Operation refusee. Verifie les donnees saisies.
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-3">
    <a href="/index.php?controller=manager_users&action=index" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800">
        Retour a la liste des utilisateurs
    </a>
</section>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <h2 class="text-xl font-bold">Utilisateur: <?= htmlspecialchars((string) ($user['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h2>
    <form method="post" action="/index.php?controller=manager_users&action=save" class="mt-3 grid grid-cols-1 md:grid-cols-12 gap-2">
        <input type="hidden" name="id" value="<?= (int) ($user['id'] ?? 0) ?>">
        <div class="md:col-span-3">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Nom</label>
            <input type="text" name="nom" required value="<?= htmlspecialchars((string) ($user['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div class="md:col-span-3">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Email</label>
            <input type="email" name="email" required value="<?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Role plateforme</label>
            <?php if ($isPlatformRoleReadOnly): ?>
                <input type="text" value="Administrateur plateforme" disabled class="w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-700">
                <p class="mt-1 text-xs text-slate-500">Role systeme verrouille.</p>
                <input type="hidden" name="role" value="admin">
            <?php else: ?>
                <input type="text" value="Utilisateur standard (pilote par role caserne)" disabled class="w-full rounded-xl border border-slate-300 bg-slate-100 px-3 py-2 text-sm text-slate-700">
                <input type="hidden" name="role" value="<?= htmlspecialchars((string) ($user['role'] ?? 'verificateur'), ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Etat</label>
            <select name="actif" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" <?= ($selectedIsAdmin || $selectedLockedAdmin) ? 'disabled' : '' ?>>
                <option value="1" <?= (int) ($user['actif'] ?? 0) === 1 ? 'selected' : '' ?>>Actif</option>
                <option value="0" <?= (int) ($user['actif'] ?? 0) !== 1 ? 'selected' : '' ?>>Inactif</option>
            </select>
            <?php if ($selectedIsAdmin || $selectedLockedAdmin): ?><input type="hidden" name="actif" value="1"><?php endif; ?>
        </div>
        <div class="md:col-span-2">
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">Reset MDP</label>
            <input type="password" name="password" minlength="<?= $passwordMinLength ?>" placeholder="Nouveau mot de passe (min <?= $passwordMinLength ?> + complexe)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
        </div>

        <div class="md:col-span-12 rounded-xl border border-slate-200 p-3">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Affectations casernes (role local par caserne)</p>
            <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                <?php foreach ($casernes as $caserne): ?>
                    <?php
                    $caserneId = (int) ($caserne['id'] ?? 0);
                    $selected = in_array($caserneId, (array) ($user['caserne_ids'] ?? []), true);
                    $selectedRole = (string) (($user['caserne_roles'][$caserneId] ?? $user['role'] ?? $defaultRoleCode));
                    ?>
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                        <label class="md:col-span-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                            <input type="checkbox" name="caserne_enabled[<?= $caserneId ?>]" value="1" <?= $selected ? 'checked' : '' ?> class="h-4 w-4 rounded border-slate-300 edit-caserne-check" <?= $isPlatformRoleReadOnly ? 'disabled' : '' ?>>
                            <?php if ($isPlatformRoleReadOnly && $selected): ?><input type="hidden" name="caserne_enabled[<?= $caserneId ?>]" value="1"><?php endif; ?>
                            <?= htmlspecialchars((string) ($caserne['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </label>
                        <select name="caserne_roles[<?= $caserneId ?>]" class="md:col-span-7 rounded-xl border border-slate-300 px-3 py-2 text-sm edit-caserne-role" <?= $isPlatformRoleReadOnly ? 'disabled' : '' ?>>
                            <?php foreach ($roleOptions as $code => $label): ?>
                                <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $selectedRole === $code ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($isPlatformRoleReadOnly && $selected): ?><input type="hidden" name="caserne_roles[<?= $caserneId ?>]" value="<?= htmlspecialchars($selectedRole, ENT_QUOTES, 'UTF-8') ?>"><?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="md:col-span-12 flex items-center justify-end gap-2">
            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
            <?php if (!$selectedIsAdmin && !$selectedIsCurrent): ?>
                <button type="button" id="open-delete-user-modal" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white">Supprimer</button>
            <?php endif; ?>
            <?php if ($selectedIsCurrent): ?>
                <span class="rounded-xl bg-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Compte courant</span>
            <?php elseif ($selectedIsAdmin || $selectedLockedAdmin): ?>
                <span class="rounded-xl bg-slate-200 px-3 py-2 text-xs font-semibold text-slate-600">Protege</span>
            <?php endif; ?>
        </div>
    </form>
</section>

<?php if (!$selectedIsAdmin && !$selectedIsCurrent): ?>
    <div id="delete-user-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4">
        <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl">
            <p class="text-xs font-semibold uppercase tracking-wide text-red-600">Attention</p>
            <h3 class="mt-1 text-2xl font-bold text-slate-900">Suppression definitive</h3>
            <p class="mt-3 text-sm text-slate-700">
                Tu vas supprimer ce compte utilisateur de façon irreversible.
            </p>
            <p class="mt-2 text-sm text-slate-700">
                Cette action retire aussi ses affectations casernes.
            </p>
            <div class="mt-4">
                <label for="delete-user-confirm-text" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                    Tape SUPPRIMER pour confirmer
                </label>
                <input id="delete-user-confirm-text" type="text" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="SUPPRIMER">
            </div>
            <div class="mt-5 flex items-center justify-end gap-2">
                <button type="button" id="cancel-delete-user-modal" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Annuler</button>
                <form method="post" action="/index.php?controller=manager_users&action=delete">
                    <input type="hidden" name="id" value="<?= (int) ($user['id'] ?? 0) ?>">
                    <button id="confirm-delete-user-modal" type="submit" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white" disabled>
                        Oui, supprimer
                    </button>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
    function bindAdminAutoSelect(roleSelector, checkboxSelector) {
        const roleSelects = Array.from(document.querySelectorAll(roleSelector));
        if (roleSelects.length === 0) {
            return;
        }
        roleSelects.forEach(function (select) {
            select.addEventListener('change', function () {
                if ((select.value || '').toLowerCase() !== 'admin') {
                    return;
                }
                const container = select.closest('form') || document;
                const checks = Array.from(container.querySelectorAll(checkboxSelector));
                const roles = Array.from(container.querySelectorAll(roleSelector));
                checks.forEach(function (check) {
                    if (!check.disabled) {
                        check.checked = true;
                    }
                });
                roles.forEach(function (role) {
                    if (!role.disabled) {
                        role.value = 'admin';
                    }
                });
            });
        });
    }

    bindAdminAutoSelect('.edit-caserne-role', '.edit-caserne-check');

    const openDeleteModalBtn = document.getElementById('open-delete-user-modal');
    const deleteModal = document.getElementById('delete-user-modal');
    const cancelDeleteModalBtn = document.getElementById('cancel-delete-user-modal');
    const confirmDeleteBtn = document.getElementById('confirm-delete-user-modal');
    const deleteConfirmInput = document.getElementById('delete-user-confirm-text');

    if (openDeleteModalBtn && deleteModal && cancelDeleteModalBtn && confirmDeleteBtn && deleteConfirmInput) {
        const refreshDeleteConfirmState = function () {
            confirmDeleteBtn.disabled = deleteConfirmInput.value.trim().toUpperCase() !== 'SUPPRIMER';
        };

        openDeleteModalBtn.addEventListener('click', function () {
            deleteModal.classList.remove('hidden');
            deleteModal.classList.add('flex');
            deleteConfirmInput.value = '';
            refreshDeleteConfirmState();
            deleteConfirmInput.focus();
        });

        cancelDeleteModalBtn.addEventListener('click', function () {
            deleteModal.classList.add('hidden');
            deleteModal.classList.remove('flex');
        });

        deleteConfirmInput.addEventListener('input', refreshDeleteConfirmState);
    }
});
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
