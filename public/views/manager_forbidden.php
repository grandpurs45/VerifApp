<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acces refuse - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-xl mx-auto p-6">
        <section class="rounded-2xl border border-red-200 bg-red-50 p-6 shadow-sm">
            <h1 class="text-2xl font-extrabold text-red-800">Acces refuse</h1>
            <p class="mt-2 text-sm text-red-700">Ton role ne permet pas d'ouvrir cette fonctionnalite.</p>
            <a href="/index.php?controller=manager&action=dashboard" class="mt-4 inline-flex rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Retour dashboard</a>
        </section>
    </main>
</body>
</html>
