<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard gestionnaire - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-6xl mx-auto p-4 md:p-8 space-y-6">
        <header class="rounded-3xl bg-gradient-to-r from-slate-900 to-slate-700 text-white p-5 md:p-6 shadow">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.18em] text-slate-300">VerifApp gestionnaire</p>
                    <h1 class="text-3xl font-extrabold mt-1">Tableau de bord</h1>
                    <p class="text-slate-200 mt-2">
                        Bonjour <?= htmlspecialchars((string) ($managerUser['nom'] ?? 'Gestionnaire'), ENT_QUOTES, 'UTF-8') ?>.
                        Voici ce qui demande de l'action aujourd'hui.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span>
                    <a href="/index.php?controller=manager_auth&action=logout" class="rounded-xl bg-white text-slate-900 px-4 py-2 text-sm font-semibold">Deconnexion</a>
                </div>
            </div>
        </header>

        <?php if (isset($_GET['password_changed']) && $_GET['password_changed'] === '1'): ?>
            <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
                Mot de passe modifie avec succes.
            </section>
        <?php endif; ?>

        <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <a href="/index.php?controller=anomalies&action=index&statut=ouverte" class="rounded-2xl bg-red-50 border border-red-200 p-5 shadow-sm hover:shadow">
                <p class="text-sm text-red-700 font-semibold">A traiter maintenant</p>
                <p class="text-4xl font-extrabold text-red-700 mt-2"><?= (int) ($anomalyStats['ouverte'] ?? 0) ?></p>
                <p class="text-sm text-red-700 mt-2">Anomalies ouvertes</p>
            </a>
            <a href="/index.php?controller=anomalies&action=index&statut=en_cours&assigne_a=<?= isset($managerUser['id']) ? (int) $managerUser['id'] : 0 ?>" class="rounded-2xl bg-amber-50 border border-amber-200 p-5 shadow-sm hover:shadow">
                <p class="text-sm text-amber-700 font-semibold">Mon suivi</p>
                <p class="text-4xl font-extrabold text-amber-700 mt-2"><?= (int) ($assignmentStats['mes_anomalies'] ?? 0) ?></p>
                <p class="text-sm text-amber-700 mt-2">Anomalies qui me sont assignees</p>
            </a>
            <a href="/index.php?controller=anomalies&action=index&assigne_a=none" class="rounded-2xl bg-sky-50 border border-sky-200 p-5 shadow-sm hover:shadow">
                <p class="text-sm text-sky-700 font-semibold">A distribuer</p>
                <p class="text-4xl font-extrabold text-sky-700 mt-2"><?= (int) ($assignmentStats['non_assignees'] ?? 0) ?></p>
                <p class="text-sm text-sky-700 mt-2">Anomalies non assignees</p>
            </a>
        </section>

        <section class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Verifs aujourd'hui</p>
                <p class="text-2xl font-bold mt-1"><?= (int) ($stats['total_today'] ?? 0) ?></p>
            </article>
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Conformes</p>
                <p class="text-2xl font-bold mt-1 text-emerald-700"><?= (int) ($stats['conformes_today'] ?? 0) ?></p>
            </article>
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Non conformes</p>
                <p class="text-2xl font-bold mt-1 text-red-700"><?= (int) ($stats['non_conformes_today'] ?? 0) ?></p>
            </article>
            <article class="bg-white rounded-2xl shadow p-4">
                <p class="text-xs uppercase tracking-wide text-slate-500">Total verifs</p>
                <p class="text-2xl font-bold mt-1"><?= (int) ($stats['total_all'] ?? 0) ?></p>
            </article>
        </section>

        <section class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <a href="/index.php?controller=anomalies&action=index" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
                <p class="font-semibold">Traiter les anomalies</p>
                <p class="text-sm text-slate-600 mt-1">Voir, assigner et suivre l'avancement.</p>
            </a>
            <a href="/index.php?controller=verifications&action=history" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
                <p class="font-semibold">Historique des verifications</p>
                <p class="text-sm text-slate-600 mt-1">Retrouver une verification en quelques clics.</p>
            </a>
            <a href="/index.php?controller=manager_assets&action=types" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
                <p class="font-semibold">Configurer les types</p>
                <p class="text-sm text-slate-600 mt-1">Postes standards par type d'engin.</p>
            </a>
            <a href="/index.php?controller=manager_assets&action=vehicles" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
                <p class="font-semibold">Configurer les vehicules</p>
                <p class="text-sm text-slate-600 mt-1">Zones, sous-zones et materiel.</p>
            </a>
            <a href="/index.php?controller=manager_pharmacy&action=index" class="rounded-2xl bg-white shadow p-4 hover:bg-slate-50 block">
                <p class="font-semibold">Module pharmacie</p>
                <p class="text-sm text-slate-600 mt-1">Stock et sorties via QR code.</p>
            </a>
        </section>

        <section class="rounded-2xl bg-white shadow p-4 md:p-5">
            <h2 class="text-xl font-bold">Acces invites (QR)</h2>
            <p class="text-sm text-slate-600 mt-1">Liens directs a transformer en QR code pour les modules terrain.</p>

            <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <article class="rounded-xl border border-slate-200 p-3">
                    <p class="text-sm font-semibold text-slate-800">Verification terrain</p>
                    <p class="text-xs text-slate-500 mt-1 break-all"><?= htmlspecialchars($fieldGuestUrl, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="mt-2 flex gap-2">
                        <a href="<?= htmlspecialchars($fieldGuestUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rounded-lg bg-slate-900 text-white px-3 py-2 text-xs font-semibold">Ouvrir</a>
                        <button type="button" data-copy="<?= htmlspecialchars($fieldGuestUrl, ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Copier lien</button>
                    </div>
                </article>

                <article class="rounded-xl border border-slate-200 p-3">
                    <p class="text-sm font-semibold text-slate-800">Sortie pharmacie</p>
                    <p class="text-xs text-slate-500 mt-1 break-all"><?= htmlspecialchars($pharmacyGuestUrl, ENT_QUOTES, 'UTF-8') ?></p>
                    <div class="mt-2 flex gap-2">
                        <a href="<?= htmlspecialchars($pharmacyGuestUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rounded-lg bg-slate-900 text-white px-3 py-2 text-xs font-semibold">Ouvrir</a>
                        <button type="button" data-copy="<?= htmlspecialchars($pharmacyGuestUrl, ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Copier lien</button>
                    </div>
                </article>
            </div>
        </section>
    </main>
    <script>
        (function () {
            const buttons = document.querySelectorAll('button[data-copy]');
            buttons.forEach((button) => {
                button.addEventListener('click', async function () {
                    const value = button.getAttribute('data-copy') || '';
                    if (!value) return;
                    try {
                        await navigator.clipboard.writeText(value);
                        const previous = button.textContent;
                        button.textContent = 'Copie';
                        setTimeout(() => {
                            button.textContent = previous;
                        }, 1200);
                    } catch (error) {
                        window.prompt('Copiez ce lien:', value);
                    }
                });
            });
        })();
    </script>
</body>
</html>
