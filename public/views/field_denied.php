<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acces terrain refuse - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-md mx-auto p-4 md:p-8">
        <section class="bg-white rounded-2xl shadow p-6">
            <h1 class="text-2xl font-bold">Acces terrain refuse</h1>
            <p class="text-slate-600 mt-2">
                Scanne le QR code officiel de la caserne pour ouvrir l'espace verification.
            </p>
            <p class="text-slate-500 text-sm mt-2">
                Exemple de lien QR : /index.php?controller=field&action=access&token=...
            </p>
            <a href="/index.php?controller=manager_auth&action=login_form" class="inline-flex mt-5 text-sm text-slate-700 underline">
                Acces gestionnaire
            </a>
        </section>
    </main>
</body>
</html>
