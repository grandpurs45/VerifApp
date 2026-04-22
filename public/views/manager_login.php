<?php

declare(strict_types=1);

$errorMessage = null;

if ($error === 'missing_fields') {
    $errorMessage = 'Identifiant et mot de passe obligatoires.';
} elseif ($error === 'invalid_credentials') {
    $errorMessage = 'Identifiants invalides ou acces non autorise.';
} elseif ($error === 'password_change_required') {
    $errorMessage = 'Compte admin initial detecte: changement de mot de passe requis.';
} elseif ($error === 'session_expired') {
    $errorMessage = 'Session expiree. Merci de vous reconnecter.';
} elseif ($error === 'no_caserne') {
    $errorMessage = 'Aucune caserne associee a ce compte. Contacte un administrateur.';
} elseif ($error === 'too_many_attempts') {
    $retryIn = isset($_GET['retry_in']) ? (int) $_GET['retry_in'] : 0;
    $retryIn = $retryIn > 0 ? $retryIn : 60;
    $retryMinutes = (int) ceil($retryIn / 60);
    $errorMessage = 'Trop de tentatives de connexion. Reessaie dans ' . $retryMinutes . ' min.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion gestionnaire - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-md mx-auto p-4 md:p-8">
        <header class="mb-6">
            <h1 class="text-3xl font-bold">Espace gestionnaire</h1>
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

        <?php if (isset($_GET['password_changed']) && $_GET['password_changed'] === '1'): ?>
            <section class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
                Mot de passe modifie avec succes.
            </section>
        <?php endif; ?>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <form method="post" action="/index.php?controller=manager_auth&action=login" class="space-y-4">
                <div>
                    <label for="identifier" class="text-sm font-medium text-slate-700">Identifiant (email ou nom)</label>
                    <input id="identifier" name="identifier" type="text" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
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
