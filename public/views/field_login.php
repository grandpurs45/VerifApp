<?php

declare(strict_types=1);

$errorMessage = null;

if ($error === 'missing_fields') {
    $errorMessage = 'Email et mot de passe obligatoires.';
} elseif ($error === 'invalid_credentials') {
    $errorMessage = 'Identifiants invalides ou role non autorise.';
} elseif ($error === 'caserne_forbidden') {
    $errorMessage = 'Compte non autorise sur cette caserne.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion verificateur - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-md mx-auto p-4 md:p-8">
        <header class="mb-6">
            <h1 class="text-3xl font-bold">Connexion verificateur</h1>
            <p class="text-slate-600 mt-2">Authentification requise pour lancer une verification.</p>
        </header>

        <?php if ($errorMessage !== null): ?>
            <section class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </section>
        <?php endif; ?>

        <?php if (isset($_GET['logged_out']) && $_GET['logged_out'] === '1'): ?>
            <section class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
                Deconnexion effectuee.
            </section>
        <?php endif; ?>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <form method="post" action="/index.php?controller=field_auth&action=login" class="space-y-4">
                <div>
                    <label for="email" class="text-sm font-medium text-slate-700">Email</label>
                    <input id="email" name="email" type="email" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>
                <div>
                    <label for="password" class="text-sm font-medium text-slate-700">Mot de passe</label>
                    <input id="password" name="password" type="password" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                </div>
                <button type="submit" class="w-full rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-semibold">
                    Se connecter
                </button>
            </form>
        </section>
    </main>
</body>
</html>
