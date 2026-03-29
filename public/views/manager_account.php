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
$startEditing = $error !== '';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if ($updated === '1'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Profil mis a jour.
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
<?php elseif ($error === 'password'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Impossible de modifier le mot de passe.
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
            <a href="/index.php?controller=manager_auth&action=change_password_form" class="inline-flex rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold">
                Changer mon mot de passe
            </a>
        </div>
    </article>
</section>

<script>
    (function () {
        const form = document.getElementById('account-form');
        const editButton = document.getElementById('account-edit-btn');
        const cancelButton = document.getElementById('account-cancel-btn');
        const actions = document.getElementById('account-actions');
        const fields = Array.from(document.querySelectorAll('[data-account-field]'));
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
    })();
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
