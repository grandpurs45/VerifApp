<?php

declare(strict_types=1);

$pageTitle = 'Mon compte - VerifApp';
$pageHeading = 'Mon compte';
$pageSubtitle = 'Informations de profil et securite du compte.';
$pageBackUrl = '';
$pageBackLabel = '';

$userName = is_array($managerUser) ? (string) ($managerUser['nom'] ?? '') : '';
$userEmail = is_array($managerUser) ? (string) ($managerUser['email'] ?? '') : '';
$userRole = is_array($managerUser) ? (string) ($managerUser['role'] ?? '') : '';
$currentCaserneId = is_array($managerUser) ? (int) ($managerUser['caserne_id'] ?? 0) : 0;
$startEditing = $error !== '';
$passwordError = isset($passwordError) ? (string) $passwordError : '';
$passwordChanged = isset($passwordChanged) ? (string) $passwordChanged : '';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if ($updated === '1'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Profil mis a jour.
    </section>
<?php endif; ?>
<?php if ($passwordChanged === '1'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Mot de passe modifie.
    </section>
<?php endif; ?>
<?php if (($updatedNotif ?? '') === '1'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Preferences notifications enregistrees.
    </section>
<?php endif; ?>

<?php if ($error === 'invalid_profile'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Nom ou email invalide.
    </section>
<?php elseif ($error === 'email_taken'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Cet email est deja utilise par un autre compte.
    </section>
<?php elseif ($error === 'save_failed'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Impossible de mettre a jour le profil.
    </section>
<?php elseif ($error === 'default_caserne_invalid'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Caserne par defaut invalide.
    </section>
<?php elseif ($error === 'password'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Impossible de modifier le mot de passe.
    </section>
<?php elseif ($error === 'notif_save_failed'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Impossible d enregistrer les preferences notifications.
    </section>
<?php endif; ?>

<?php if ($passwordError === 'missing_fields'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Tous les champs mot de passe sont obligatoires.
    </section>
<?php elseif ($passwordError === 'password_policy'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Le mot de passe doit contenir au moins 12 caracteres, avec minuscule, majuscule, chiffre et caractere special.
    </section>
<?php elseif ($passwordError === 'password_mismatch'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        La confirmation ne correspond pas au nouveau mot de passe.
    </section>
<?php elseif ($passwordError === 'invalid_current_password'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Mot de passe actuel invalide.
    </section>
<?php endif; ?>

<section class="grid grid-cols-1 md:grid-cols-2 gap-4">
    <article class="rounded-2xl bg-white shadow p-5">
        <div class="flex items-center justify-between gap-2">
            <h2 class="text-lg font-bold">Profil</h2>
            <button type="button" id="account-edit-btn" class="rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                Editer
            </button>
        </div>
        <form method="post" action="/index.php?controller=manager&action=account_save" class="mt-3 space-y-3" id="account-form" data-start-editing="<?= $startEditing ? '1' : '0' ?>">
            <div>
                <label for="account_nom" class="text-sm font-medium text-slate-700">Nom</label>
                <input
                    id="account_nom"
                    type="text"
                    name="nom"
                    required
                    value="<?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?>"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-slate-50"
                    readonly
                    data-account-field
                >
            </div>
            <div>
                <label for="account_email" class="text-sm font-medium text-slate-700">Email</label>
                <input
                    id="account_email"
                    type="email"
                    name="email"
                    required
                    value="<?= htmlspecialchars($userEmail, ENT_QUOTES, 'UTF-8') ?>"
                    class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-slate-50"
                    readonly
                    data-account-field
                >
            </div>
            <div>
                <p class="text-sm text-slate-500">Role</p>
                <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars($userRole !== '' ? $userRole : '-', ENT_QUOTES, 'UTF-8') ?></p>
            </div>
            <?php if (isset($caserneOptions) && is_array($caserneOptions) && count($caserneOptions) > 1): ?>
                <div>
                    <label for="default_caserne_id" class="text-sm font-medium text-slate-700">Caserne prioritaire</label>
                    <select
                        id="default_caserne_id"
                        name="default_caserne_id"
                        class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm bg-slate-50"
                        disabled
                        data-account-field-select
                    >
                        <?php foreach ($caserneOptions as $caserne): ?>
                            <?php
                            $caserneId = (int) ($caserne['id'] ?? 0);
                            $isDefault = (int) ($caserne['is_default'] ?? 0) === 1 || $currentCaserneId === $caserneId;
                            ?>
                            <option value="<?= $caserneId ?>" <?= $isDefault ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($caserne['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php elseif (isset($caserneOptions) && is_array($caserneOptions) && count($caserneOptions) === 1): ?>
                <div>
                    <p class="text-sm text-slate-500">Caserne</p>
                    <p class="text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($caserneOptions[0]['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <input type="hidden" name="default_caserne_id" value="<?= (int) ($caserneOptions[0]['id'] ?? 0) ?>">
                </div>
            <?php endif; ?>
            <div class="hidden items-center gap-2" id="account-actions">
                <button type="submit" class="inline-flex rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold">
                    Enregistrer le profil
                </button>
                <button type="button" id="account-cancel-btn" class="inline-flex rounded-xl border border-slate-300 px-4 py-3 text-sm font-semibold text-slate-700">
                    Annuler
                </button>
            </div>
        </form>
    </article>

    <article class="rounded-2xl bg-white shadow p-5">
        <h2 class="text-lg font-bold">Securite</h2>
        <p class="text-sm text-slate-600 mt-2">Mets a jour ton mot de passe regulierement pour securiser ton acces.</p>
        <div class="mt-4">
            <button type="button" id="open-password-modal-btn" class="inline-flex rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold">
                Changer mon mot de passe
            </button>
        </div>
    </article>
</section>

<?php if (($notificationsAvailable ?? false) === true): ?>
    <section class="rounded-2xl bg-white shadow p-5">
        <h2 class="text-lg font-bold">Notifications</h2>
        <p class="text-sm text-slate-600 mt-1">Choisis les notifications a recevoir dans la cloche et/ou par email.</p>
        <form method="post" action="/index.php?controller=manager_notifications&action=preferences_save" class="mt-3 space-y-2">
            <?php foreach (($notificationCatalog ?? []) as $eventCode => $meta): ?>
                <?php
                $eventKey = str_replace('.', '_', (string) $eventCode);
                $isEnabled = (bool) (($notificationSubscriptions[$eventCode]['in_app_enabled'] ?? true) === true);
                $isEmailEnabled = (bool) (($notificationSubscriptions[$eventCode]['email_enabled'] ?? false) === true);
                ?>
                <div class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-start gap-3">
                        <span>
                            <span class="block text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($meta['label'] ?? $eventCode), ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="block text-xs text-slate-500"><?= htmlspecialchars((string) ($meta['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                        </span>
                    </div>
                    <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                name="notif_in_app[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>]"
                                value="1"
                                class="h-4 w-4"
                                <?= $isEnabled ? 'checked' : '' ?>
                            >
                            <span>Cloche (in-app)</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input
                                type="checkbox"
                                name="notif_email[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>]"
                                value="1"
                                class="h-4 w-4"
                                <?= $isEmailEnabled ? 'checked' : '' ?>
                            >
                            <span>Email</span>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">
                Enregistrer preferences notifications
            </button>
        </form>
    </section>
<?php endif; ?>

<div id="account-password-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-950/60 p-4">
    <div class="w-full max-w-xl rounded-2xl bg-white p-5 shadow-2xl">
        <h3 class="text-xl font-bold text-slate-900">Changer le mot de passe</h3>
        <p class="mt-1 text-sm text-slate-600">Saisie securisee du mot de passe.</p>
        <?php if ($passwordError === 'missing_fields'): ?>
            <section class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-red-700 text-sm">
                Tous les champs mot de passe sont obligatoires.
            </section>
        <?php elseif ($passwordError === 'password_policy'): ?>
            <section class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-red-700 text-sm">
                Le mot de passe doit contenir au moins 12 caracteres, avec minuscule, majuscule, chiffre et caractere special.
            </section>
        <?php elseif ($passwordError === 'password_mismatch'): ?>
            <section class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-red-700 text-sm">
                La confirmation ne correspond pas au nouveau mot de passe.
            </section>
        <?php elseif ($passwordError === 'invalid_current_password'): ?>
            <section class="mt-3 rounded-xl border border-red-200 bg-red-50 p-3 text-red-700 text-sm">
                Mot de passe actuel invalide.
            </section>
        <?php endif; ?>
        <form method="post" action="/index.php?controller=manager_auth&action=change_password" class="mt-4 space-y-3">
            <div>
                <label for="modal_current_password" class="text-sm font-medium text-slate-700">Mot de passe actuel</label>
                <input id="modal_current_password" name="current_password" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            </div>
            <div>
                <label for="modal_new_password" class="text-sm font-medium text-slate-700">Nouveau mot de passe</label>
                <input id="modal_new_password" name="new_password" type="password" minlength="12" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            </div>
            <div>
                <label for="modal_confirm_password" class="text-sm font-medium text-slate-700">Confirmation</label>
                <input id="modal_confirm_password" name="confirm_password" type="password" minlength="12" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-sm">
            </div>
            <div class="flex items-center justify-end gap-2">
                <button type="button" id="close-password-modal-btn" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                    Annuler
                </button>
                <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        const form = document.getElementById('account-form');
        const editButton = document.getElementById('account-edit-btn');
        const cancelButton = document.getElementById('account-cancel-btn');
        const actions = document.getElementById('account-actions');
        const fields = Array.from(document.querySelectorAll('[data-account-field]'));
        const selectFields = Array.from(document.querySelectorAll('[data-account-field-select]'));
        if (!form || !editButton || !actions || fields.length === 0) {
            return;
        }

        const initialValues = {};
        fields.forEach((field) => {
            initialValues[field.name] = field.value;
        });

        function setEditing(editing) {
            fields.forEach((field) => {
                field.readOnly = !editing;
                field.classList.toggle('bg-slate-50', !editing);
                field.classList.toggle('bg-white', editing);
            });
            selectFields.forEach((field) => {
                field.disabled = !editing;
                field.classList.toggle('bg-slate-50', !editing);
                field.classList.toggle('bg-white', editing);
            });
            actions.classList.toggle('hidden', !editing);
            actions.classList.toggle('flex', editing);
            editButton.classList.toggle('hidden', editing);
        }

        editButton.addEventListener('click', function () {
            setEditing(true);
        });

        if (cancelButton) {
            cancelButton.addEventListener('click', function () {
                fields.forEach((field) => {
                    field.value = initialValues[field.name] || '';
                });
                setEditing(false);
            });
        }

        setEditing(form.dataset.startEditing === '1');

        const openPasswordModalBtn = document.getElementById('open-password-modal-btn');
        const closePasswordModalBtn = document.getElementById('close-password-modal-btn');
        const passwordModal = document.getElementById('account-password-modal');
        const modalCurrentPassword = document.getElementById('modal_current_password');
        const shouldOpenPasswordModal = <?= ($passwordError !== '' ? 'true' : 'false') ?>;

        if (openPasswordModalBtn && closePasswordModalBtn && passwordModal) {
            const openPasswordModal = function () {
                passwordModal.classList.remove('hidden');
                passwordModal.classList.add('flex');
                if (modalCurrentPassword) {
                    modalCurrentPassword.focus();
                }
            };
            const closePasswordModal = function () {
                passwordModal.classList.add('hidden');
                passwordModal.classList.remove('flex');
            };

            openPasswordModalBtn.addEventListener('click', openPasswordModal);
            closePasswordModalBtn.addEventListener('click', closePasswordModal);
            if (shouldOpenPasswordModal) {
                openPasswordModal();
            }
        }
    })();
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
