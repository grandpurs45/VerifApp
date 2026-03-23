<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anomalies - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-5xl mx-auto p-4 md:p-8">
        <header class="mb-6">
            <a href="/index.php?controller=manager&action=dashboard" class="text-sm text-slate-500 hover:text-slate-700">
                <- Retour dashboard
            </a>
            <h1 class="text-3xl font-bold mt-2">Gestion des anomalies</h1>
            <p class="text-slate-600 mt-1">Suivi des anomalies ouvertes, en cours, resolues et cloturees.</p>
        </header>

        <?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
            <section class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700">
                Anomalie mise a jour.
            </section>
        <?php endif; ?>

        <?php if (!$anomaliesAvailable): ?>
            <section class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-700">
                La table <strong>anomalies</strong> est absente. Applique la migration <code>007_create_anomalies.sql</code> pour activer cette page.
            </section>
        <?php endif; ?>

        <section class="bg-white rounded-2xl shadow p-4 md:p-6 mb-6">
            <form method="get" action="/index.php" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                <input type="hidden" name="controller" value="anomalies">
                <input type="hidden" name="action" value="index">

                <select name="statut" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Tous statuts</option>
                    <option value="ouverte" <?= $filters['statut'] === 'ouverte' ? 'selected' : '' ?>>Ouverte</option>
                    <option value="en_cours" <?= $filters['statut'] === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="resolue" <?= $filters['statut'] === 'resolue' ? 'selected' : '' ?>>Resolue</option>
                    <option value="cloturee" <?= $filters['statut'] === 'cloturee' ? 'selected' : '' ?>>Cloturee</option>
                </select>

                <select name="priorite" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Toutes priorites</option>
                    <option value="basse" <?= $filters['priorite'] === 'basse' ? 'selected' : '' ?>>Basse</option>
                    <option value="moyenne" <?= $filters['priorite'] === 'moyenne' ? 'selected' : '' ?>>Moyenne</option>
                    <option value="haute" <?= $filters['priorite'] === 'haute' ? 'selected' : '' ?>>Haute</option>
                    <option value="critique" <?= $filters['priorite'] === 'critique' ? 'selected' : '' ?>>Critique</option>
                </select>

                <select name="vehicule_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Tous vehicules</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                        <option value="<?= (int) $vehicle['id'] ?>" <?= $filters['vehicule_id'] === (string) $vehicle['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="poste_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <option value="">Tous postes</option>
                    <?php foreach ($postes as $poste): ?>
                        <option value="<?= (int) $poste['id'] ?>" <?= $filters['poste_id'] === (string) $poste['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <input
                    type="date"
                    name="date_from"
                    value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>"
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm"
                >

                <input
                    type="date"
                    name="date_to"
                    value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>"
                    class="rounded-xl border border-slate-300 px-4 py-3 text-sm"
                >

                <button type="submit" class="md:col-span-3 rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-semibold">
                    Filtrer
                </button>
            </form>
        </section>

        <section class="space-y-4">
            <?php if ($anomalies === []): ?>
                <div class="bg-white rounded-2xl shadow p-6 text-slate-500">
                    Aucune anomalie pour ces filtres.
                </div>
            <?php else: ?>
                <?php foreach ($anomalies as $anomaly): ?>
                    <article class="bg-white rounded-2xl shadow p-4 md:p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold">
                                    Anomalie #<?= (int) $anomaly['id'] ?> - <?= htmlspecialchars($anomaly['controle_libelle'], ENT_QUOTES, 'UTF-8') ?>
                                </h2>
                                <p class="text-sm text-slate-600 mt-1">
                                    <?= htmlspecialchars($anomaly['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars($anomaly['poste_nom'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p class="text-sm text-slate-500">
                                    Creee le <?= htmlspecialchars((string) $anomaly['date_creation'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php if (($anomaly['date_resolution'] ?? null) !== null): ?>
                                        - Resolue le <?= htmlspecialchars((string) $anomaly['date_resolution'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </p>
                                <p class="text-sm text-slate-600 mt-2">
                                    Zone : <?= htmlspecialchars($anomaly['controle_zone'], ENT_QUOTES, 'UTF-8') ?>
                                </p>
                                <p class="text-sm mt-2">
                                    <a class="underline text-slate-700" href="/index.php?controller=verifications&action=show&id=<?= (int) $anomaly['verification_id'] ?>">
                                        Ouvrir verification #<?= (int) $anomaly['verification_id'] ?>
                                    </a>
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Statut</p>
                                <p class="font-semibold"><?= htmlspecialchars($anomaly['statut'], ENT_QUOTES, 'UTF-8') ?></p>
                                <p class="text-xs uppercase tracking-wide text-slate-500 mt-2">Priorite</p>
                                <p class="font-semibold"><?= htmlspecialchars($anomaly['priorite'], ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                        </div>

                        <?php if (($anomaly['commentaire'] ?? null) !== null && trim((string) $anomaly['commentaire']) !== ''): ?>
                            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                                <?= nl2br(htmlspecialchars((string) $anomaly['commentaire'], ENT_QUOTES, 'UTF-8')) ?>
                            </div>
                        <?php endif; ?>

                        <form method="post" action="/index.php?controller=anomalies&action=update" class="mt-4 grid grid-cols-1 md:grid-cols-4 gap-3">
                            <input type="hidden" name="anomaly_id" value="<?= (int) $anomaly['id'] ?>">
                            <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery, ENT_QUOTES, 'UTF-8') ?>">

                            <select name="statut" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                                <?php
                                $statusOptions = ['ouverte', 'en_cours', 'resolue', 'cloturee'];
                                foreach ($statusOptions as $statusOption):
                                ?>
                                    <option value="<?= $statusOption ?>" <?= $anomaly['statut'] === $statusOption ? 'selected' : '' ?>>
                                        <?= ucfirst(str_replace('_', ' ', $statusOption)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <select name="priorite" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                                <?php
                                $priorityOptions = ['basse', 'moyenne', 'haute', 'critique'];
                                foreach ($priorityOptions as $priorityOption):
                                ?>
                                    <option value="<?= $priorityOption ?>" <?= $anomaly['priorite'] === $priorityOption ? 'selected' : '' ?>>
                                        <?= ucfirst($priorityOption) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <input
                                type="text"
                                name="commentaire"
                                value="<?= htmlspecialchars((string) ($anomaly['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                placeholder="Commentaire de suivi"
                                class="rounded-xl border border-slate-300 px-4 py-3 text-sm md:col-span-1"
                            >

                            <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold">
                                Mettre a jour
                            </button>
                        </form>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
