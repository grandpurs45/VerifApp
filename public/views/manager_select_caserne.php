<?php

declare(strict_types=1);

$errorMessage = null;
if ($error === 'missing_caserne') {
    $errorMessage = 'Selectionne une caserne pour continuer.';
} elseif ($error === 'invalid_caserne') {
    $errorMessage = 'Caserne invalide.';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Selection caserne - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-md mx-auto p-4 md:p-8">
        <header class="mb-6">
            <h1 class="text-3xl font-bold mt-2">Choisir une caserne</h1>
            <p class="text-sm text-slate-600 mt-2">
                Connecte en tant que
                <span class="font-semibold"><?= htmlspecialchars($userName, ENT_QUOTES, 'UTF-8') ?></span>
            </p>
        </header>

        <?php if ($errorMessage !== null): ?>
            <section class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
                <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
            </section>
        <?php endif; ?>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6">
            <form method="post" action="/index.php?controller=manager_auth&action=select_caserne" class="space-y-4">
                <div>
                    <label for="caserne_id" class="text-sm font-medium text-slate-700">Caserne active</label>
                    <select id="caserne_id" name="caserne_id" required class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3">
                        <option value="">Selectionner...</option>
                        <?php foreach ($casernes as $caserne): ?>
                            <option value="<?= (int) ($caserne['id'] ?? 0) ?>" <?= (int) ($caserne['is_default'] ?? 0) === 1 ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) ($caserne['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="w-full rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-semibold">
                    Continuer
                </button>
            </form>
        </section>
    </main>
</body>
</html>
