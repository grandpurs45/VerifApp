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
if (!isset($isPlatformAdmin)) {
    $isPlatformAdmin = ((int) ($managerUser['is_platform_admin'] ?? 0) === 1)
        || strtolower((string) ($managerUser['global_role'] ?? $managerUser['role'] ?? '')) === 'admin';
}

$pageTitle = 'Utilisateurs - VerifApp';
$pageHeading = 'Utilisateurs';
$pageSubtitle = 'CRUD comptes gestionnaires et verification des acces.';
$pageBackUrl = '/index.php?controller=manager_admin&action=menu';
$pageBackLabel = 'Retour administration';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (isset($_GET['success'])): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
        <?php if ((string) $_GET['success'] === 'attached_existing'): ?>
            Compte existant detecte: droits ajoutes sur cette caserne.
        <?php elseif ((string) $_GET['success'] === 'detached'): ?>
            Compte retire de cette caserne (conserve sur ses autres casernes).
        <?php else: ?>
            Operation effectuee avec succes.
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php if (isset($_GET['error'])): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        <?php if ((string) $_GET['error'] === 'forbidden_admin'): ?>
            Seul un administrateur plateforme peut attribuer ou modifier le role admin.
        <?php elseif ((string) $_GET['error'] === 'email_exists'): ?>
            Cet email est deja utilise par un autre compte.
        <?php elseif ((string) $_GET['error'] === 'password_policy'): ?>
            Le mot de passe doit contenir au moins 12 caracteres, avec minuscule, majuscule, chiffre et caractere special.
        <?php elseif ((string) $_GET['error'] === 'bulk_password_mismatch'): ?>
            Confirmation de mot de passe differente: reessaie l action "Changer mot de passe".
        <?php elseif ((string) $_GET['error'] === 'bulk_no_selection'): ?>
            Aucune selection detectee: coche au moins un compte avant l action.
        <?php elseif ((string) $_GET['error'] === 'bulk_no_target'): ?>
            Aucun compte modifiable dans la selection (droits ou comptes proteges).
        <?php else: ?>
            Operation refusee. Verifie les donnees (ou droits proteges admin).
        <?php endif; ?>
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <h2 class="text-xl font-bold">Creer un utilisateur</h2>
    <form id="create-user-form" method="post" action="/index.php?controller=manager_users&action=save" class="mt-3 grid grid-cols-1 md:grid-cols-12 gap-2">
        <input type="hidden" name="id" value="0">
        <input type="text" name="nom" required placeholder="Nom complet" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
        <input type="email" name="email" required placeholder="email@exemple.fr" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
        <input type="hidden" name="role" value="<?= htmlspecialchars($defaultRoleCode, ENT_QUOTES, 'UTF-8') ?>">
        <input type="password" name="password" required minlength="12" placeholder="Mot de passe initial (min 12 + complexe)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-5">
        <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-1">
            <option value="1">Actif</option>
            <option value="0">Inactif</option>
        </select>
        <div class="md:col-span-12 rounded-xl border border-slate-200 p-3">
            <?php if (!$isPlatformAdmin && count($casernes) === 1): ?>
                <?php $singleCaserne = $casernes[0]; $singleCaserneId = (int) ($singleCaserne['id'] ?? 0); ?>
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-2">Affectation caserne</p>
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center">
                    <div class="md:col-span-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="hidden" name="caserne_enabled[<?= $singleCaserneId ?>]" value="1">
                        <?= htmlspecialchars((string) ($singleCaserne['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <select name="caserne_roles[<?= $singleCaserneId ?>]" class="md:col-span-7 rounded-xl border border-slate-300 px-3 py-2 text-sm create-caserne-role">
                        <?php foreach ($roleOptions as $code => $label): ?>
                            <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $code === $defaultRoleCode ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php else: ?>
                <div class="flex items-center justify-between gap-2 mb-2">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Affectations casernes (role par caserne)</p>
                    <input id="create-caserne-filter" type="text" placeholder="Filtrer casernes..." class="w-52 rounded-lg border border-slate-300 px-2 py-1.5 text-xs">
                </div>
                <div class="space-y-2 max-h-56 overflow-y-auto pr-1">
                    <?php foreach ($casernes as $caserne): ?>
                        <?php $caserneId = (int) ($caserne['id'] ?? 0); ?>
                        <div class="create-caserne-row grid grid-cols-1 md:grid-cols-12 gap-2 items-center" data-caserne-name="<?= htmlspecialchars(mb_strtolower((string) ($caserne['nom'] ?? ''), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>">
                            <label class="md:col-span-5 inline-flex items-center gap-2 text-sm font-semibold text-slate-700">
                                <input type="checkbox" name="caserne_enabled[<?= $caserneId ?>]" value="1" class="h-4 w-4 rounded border-slate-300 create-caserne-check">
                                <?= htmlspecialchars((string) ($caserne['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </label>
                            <select name="caserne_roles[<?= $caserneId ?>]" class="md:col-span-7 rounded-xl border border-slate-300 px-3 py-2 text-sm create-caserne-role">
                                <?php foreach ($roleOptions as $code => $label): ?>
                                    <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= $code === $defaultRoleCode ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php if (!$isPlatformAdmin): ?>
            <p class="md:col-span-12 text-xs text-slate-500">Role admin plateforme masque pour ce compte.</p>
        <?php endif; ?>
        <button type="submit" class="md:col-span-12 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Creer utilisateur</button>
    </form>
</section>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <div class="mb-3 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <h2 class="text-xl font-bold">Comptes existants</h2>
        <input id="existing-users-filter" type="text" placeholder="Filtrer nom / email..." class="w-full md:w-72 rounded-xl border border-slate-300 px-3 py-2 text-sm">
    </div>
    <div class="mb-3 rounded-xl border border-slate-200 p-3">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-600">
                Selection: <span class="bulk-selected-count font-semibold">0</span> compte(s)
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="bulk-activate-btn rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white" disabled>Activer</button>
                <button type="button" class="bulk-deactivate-btn rounded-xl bg-amber-600 px-3 py-2 text-xs font-semibold text-white" disabled>Desactiver</button>
                <button type="button" class="bulk-password-btn rounded-xl bg-slate-800 px-3 py-2 text-xs font-semibold text-white" disabled>Changer mot de passe</button>
            </div>
        </div>
        <p class="mt-2 text-xs text-slate-500">Astuce: 1) coche un ou plusieurs comptes 2) clique l action voulue.</p>
    </div>
    <div class="grid grid-cols-12 gap-2 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-500 border-b border-slate-200">
        <div class="col-span-1">
            <label class="inline-flex items-center gap-2">
                <input id="users-select-all" type="checkbox" class="h-4 w-4 rounded border-slate-300">
                <span>Sel.</span>
            </label>
        </div>
        <div class="col-span-2">Nom</div>
        <div class="col-span-3">Email</div>
        <div class="col-span-2">Role plateforme</div>
        <div class="col-span-1">Etat</div>
        <div class="col-span-1">Casernes</div>
        <div class="col-span-2 text-right">Actions</div>
    </div>
    <div class="mt-2 space-y-2">
        <?php foreach ($users as $user): ?>
            <?php
            $selectedCount = count((array) ($user['caserne_ids'] ?? []));
            ?>
            <div class="existing-user-row grid grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 px-3 py-2 cursor-pointer" data-open-user-href="/index.php?controller=manager_users&action=show&id=<?= (int) ($user['id'] ?? 0) ?>" data-user-search="<?= htmlspecialchars(mb_strtolower(((string) ($user['nom'] ?? '') . ' ' . (string) ($user['email'] ?? '')), 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>" onclick="if (event.target.closest('input,button,a,label,select,textarea,summary,details')) { return; } window.location.href='/index.php?controller=manager_users&action=show&id=<?= (int) ($user['id'] ?? 0) ?>'">
                <div class="col-span-1" onclick="event.stopPropagation();">
                    <input type="checkbox" class="user-select-item h-4 w-4 rounded border-slate-300" value="<?= (int) ($user['id'] ?? 0) ?>">
                </div>
                <div class="col-span-2 truncate font-semibold text-slate-800"><?= htmlspecialchars((string) ($user['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-span-3 truncate text-slate-700"><?= htmlspecialchars((string) ($user['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="col-span-2 truncate text-slate-700">
                    <?= htmlspecialchars((string) ($roleOptions[(string) ($user['role'] ?? '')] ?? (string) ($user['role'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div class="col-span-1">
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold <?= ((int) ($user['actif'] ?? 0) === 1) ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' ?>">
                        <?= ((int) ($user['actif'] ?? 0) === 1) ? 'Actif' : 'Inactif' ?>
                    </span>
                </div>
                <div class="col-span-1 text-sm text-slate-600"><?= $selectedCount ?></div>
                <div class="col-span-2 flex justify-end" onclick="event.stopPropagation();">
                    <a href="/index.php?controller=manager_users&action=show&id=<?= (int) ($user['id'] ?? 0) ?>" class="rounded-xl border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 existing-user-open-link">
                        Ouvrir fiche
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 p-3">
        <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
            <p class="text-sm text-slate-600">
                Selection: <span class="bulk-selected-count font-semibold">0</span> compte(s)
            </p>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" class="bulk-activate-btn rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white" disabled>Activer</button>
                <button type="button" class="bulk-deactivate-btn rounded-xl bg-amber-600 px-3 py-2 text-xs font-semibold text-white" disabled>Desactiver</button>
                <button type="button" class="bulk-password-btn rounded-xl bg-slate-800 px-3 py-2 text-xs font-semibold text-white" disabled>Changer mot de passe</button>
            </div>
        </div>
    </div>
</section>

<form id="bulk-status-form" method="post" action="/index.php?controller=manager_users&action=bulk_status" class="hidden">
    <input id="bulk-status-ids" type="hidden" name="ids_csv" value="">
    <input id="bulk-status-target" type="hidden" name="target_state" value="">
</form>

<div id="bulk-password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4">
    <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl">
        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Action groupee</p>
        <h3 class="mt-1 text-2xl font-bold text-slate-900">Changer mot de passe</h3>
        <p class="mt-2 text-sm text-slate-700">Le nouveau mot de passe sera applique a tous les comptes selectionnes.</p>
        <form id="bulk-password-form" method="post" action="/index.php?controller=manager_users&action=bulk_password" class="mt-4 space-y-3">
            <input id="bulk-password-ids" type="hidden" name="ids_csv" value="">
            <input id="bulk-password-input" type="password" name="password" required minlength="12" placeholder="Nouveau mot de passe (min 12 + complexe)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <input id="bulk-password-confirm" type="password" name="password_confirm" required minlength="12" placeholder="Confirmer le mot de passe" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <div class="flex items-center justify-end gap-2">
                <button type="button" id="bulk-password-cancel" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Annuler</button>
                <button type="submit" id="bulk-password-submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Appliquer</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const filterInput = document.getElementById('create-caserne-filter');
    const rows = Array.from(document.querySelectorAll('.create-caserne-row'));
    if (filterInput) {
        filterInput.addEventListener('input', function () {
            const query = (filterInput.value || '').toLowerCase().trim();
            rows.forEach(function (row) {
                const label = (row.getAttribute('data-caserne-name') || '').toLowerCase();
                row.style.display = query === '' || label.includes(query) ? '' : 'none';
            });
        });
    }

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

    bindAdminAutoSelect('.create-caserne-role', '.create-caserne-check');
    bindAdminAutoSelect('.edit-caserne-role', '.edit-caserne-check');

    const createForm = document.getElementById('create-user-form');
    if (createForm) {
        createForm.addEventListener('submit', function (event) {
            const checks = Array.from(createForm.querySelectorAll('.create-caserne-check'));
            const hiddenEnabledInputs = Array.from(createForm.querySelectorAll('input[name^="caserne_enabled["][type="hidden"]'));
            const roles = Array.from(createForm.querySelectorAll('.create-caserne-role'));
            const hasAdminRole = roles.some(function (role) {
                return (role.value || '').toLowerCase() === 'admin';
            });

            if (hasAdminRole) {
                checks.forEach(function (check) {
                    if (!check.disabled) {
                        check.checked = true;
                    }
                });
            }

            const selected = checks.some(function (check) {
                return check.checked;
            }) || hiddenEnabledInputs.length > 0;
            if (!selected) {
                event.preventDefault();
                window.alert('Selectionne au moins une caserne avant de creer le compte.');
                return;
            }

            const confirmCreate = window.confirm(
                'Confirmer la creation/rattachement du compte ?\n\nSi cet email existe deja, le compte sera rattache a cette caserne.'
            );
            if (!confirmCreate) {
                event.preventDefault();
            }
        });
    }

    const usersFilter = document.getElementById('existing-users-filter');
    if (usersFilter) {
        const userRows = Array.from(document.querySelectorAll('.existing-user-row'));
        usersFilter.addEventListener('input', function () {
            const query = (usersFilter.value || '').toLowerCase().trim();
            userRows.forEach(function (row) {
                const haystack = (row.getAttribute('data-user-search') || '').toLowerCase();
                row.style.display = query === '' || haystack.includes(query) ? '' : 'none';
            });
        });
    }

    const selectAll = document.getElementById('users-select-all');
    const selectItems = Array.from(document.querySelectorAll('.user-select-item'));
    const selectedCountEls = Array.from(document.querySelectorAll('.bulk-selected-count'));
    const bulkActivateBtns = Array.from(document.querySelectorAll('.bulk-activate-btn'));
    const bulkDeactivateBtns = Array.from(document.querySelectorAll('.bulk-deactivate-btn'));
    const bulkPasswordBtns = Array.from(document.querySelectorAll('.bulk-password-btn'));
    const bulkStatusForm = document.getElementById('bulk-status-form');
    const bulkStatusIds = document.getElementById('bulk-status-ids');
    const bulkStatusTarget = document.getElementById('bulk-status-target');

    const bulkPasswordModal = document.getElementById('bulk-password-modal');
    const bulkPasswordForm = document.getElementById('bulk-password-form');
    const bulkPasswordIds = document.getElementById('bulk-password-ids');
    const bulkPasswordCancel = document.getElementById('bulk-password-cancel');
    const bulkPasswordInput = document.getElementById('bulk-password-input');
    const bulkPasswordConfirm = document.getElementById('bulk-password-confirm');

    const getSelectedIds = function () {
        return selectItems.filter(function (item) { return item.checked; }).map(function (item) { return item.value; });
    };
    const refreshBulkUi = function () {
        const selectedIds = getSelectedIds();
        selectedCountEls.forEach(function (el) { el.textContent = String(selectedIds.length); });
        const disabled = selectedIds.length === 0;
        bulkActivateBtns.forEach(function (btn) { btn.disabled = disabled; });
        bulkDeactivateBtns.forEach(function (btn) { btn.disabled = disabled; });
        bulkPasswordBtns.forEach(function (btn) { btn.disabled = disabled; });
        if (selectAll) {
            selectAll.checked = selectItems.length > 0 && selectedIds.length === selectItems.length;
        }
    };

    if (selectAll) {
        selectAll.addEventListener('change', function () {
            selectItems.forEach(function (item) { item.checked = selectAll.checked; });
            refreshBulkUi();
        });
    }
    selectItems.forEach(function (item) {
        item.addEventListener('change', refreshBulkUi);
    });
    refreshBulkUi();

    const submitBulkStatus = function (targetState) {
        const selectedIds = getSelectedIds();
        if (selectedIds.length === 0 || !bulkStatusForm || !bulkStatusIds || !bulkStatusTarget) {
            return;
        }
        bulkStatusIds.value = selectedIds.join(',');
        bulkStatusTarget.value = targetState;
        bulkStatusForm.submit();
    };
    bulkActivateBtns.forEach(function (bulkActivateBtn) {
        bulkActivateBtn.addEventListener('click', function () {
            submitBulkStatus('1');
        });
    });
    bulkDeactivateBtns.forEach(function (bulkDeactivateBtn) {
        bulkDeactivateBtn.addEventListener('click', function () {
            submitBulkStatus('0');
        });
    });

    bulkPasswordBtns.forEach(function (bulkPasswordBtn) {
        if (!bulkPasswordModal || !bulkPasswordIds) {
            return;
        }
        bulkPasswordBtn.addEventListener('click', function () {
            const selectedIds = getSelectedIds();
            if (selectedIds.length === 0) {
                return;
            }
            bulkPasswordIds.value = selectedIds.join(',');
            bulkPasswordModal.classList.remove('hidden');
            bulkPasswordModal.classList.add('flex');
            if (bulkPasswordInput) {
                bulkPasswordInput.value = '';
                bulkPasswordInput.focus();
            }
            if (bulkPasswordConfirm) {
                bulkPasswordConfirm.value = '';
            }
        });
    });

    if (bulkPasswordCancel && bulkPasswordModal) {
        bulkPasswordCancel.addEventListener('click', function () {
            bulkPasswordModal.classList.add('hidden');
            bulkPasswordModal.classList.remove('flex');
        });
    }
    if (bulkPasswordForm) {
        bulkPasswordForm.addEventListener('submit', function (event) {
            if (!bulkPasswordInput || !bulkPasswordConfirm) {
                return;
            }
            if (bulkPasswordInput.value !== bulkPasswordConfirm.value) {
                event.preventDefault();
                window.alert('Les mots de passe ne correspondent pas.');
            }
        });
    }

});
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
