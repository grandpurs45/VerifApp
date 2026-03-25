<?php

declare(strict_types=1);

$errorMessage = null;
$successMessage = null;

if ($error === 'missing_fields') {
    $errorMessage = 'Tous les champs sont obligatoires.';
} elseif ($error === 'password_too_short') {
    $errorMessage = 'Le nouveau mot de passe doit contenir au moins 8 caracteres.';
} elseif ($error === 'password_mismatch') {
    $errorMessage = 'La confirmation ne correspond pas au nouveau mot de passe.';
} elseif ($error === 'invalid_current_password') {
    $errorMessage = 'Mot de passe actuel invalide.';
}

if ($success === 'updated') {
    $successMessage = 'Mot de passe modifie.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-md mx-auto p-4 md:p-8">
        <header class="mb-6">
            <h1 class="text-3xl font-bold">Securisation du compte</h1>
            <p class="text-sm text-slate-600 mt-2">
                Premiere connexion detectee. Modifiez le mot de passe avant d acceder a l espace gestionnaire.
            </p>
        </header>

        <?php if ($errorMessage !== null): ?>
            <section class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </section>
        <?php endif; ?>

        <?php if ($successMessage !== null): ?>
            <section class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
                <?= htmlspecialchars($successMessage, ENT_QUOTES, 'UTF-8') ?>
            </section>
        <?php endif; ?>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <form method="post" action="/index.php?controller=manager_auth&action=change_password" class="space-y-4">
                <div>
                    <label for="current_password" class="text-sm font-medium text-slate-700">Mot de passe actuel</label>
                    <input id="current_password" name="current_password" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>

                <div>
                    <label for="new_password" class="text-sm font-medium text-slate-700">Nouveau mot de passe</label>
                    <input id="new_password" name="new_password" type="password" minlength="8" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>

                <div>
                    <label for="confirm_password" class="text-sm font-medium text-slate-700">Confirmation</label>
                    <input id="confirm_password" name="confirm_password" type="password" minlength="8" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>

                <button type="submit" class="w-full rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-semibold">
                    Enregistrer et continuer
                </button>
            </form>
        </section>
    </main>
</body>
</html>
