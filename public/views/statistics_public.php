<?php
declare(strict_types=1);
$escape=static fn(string $value): string => htmlspecialchars($value,ENT_QUOTES,'UTF-8');
?>
<!doctype html>
<html lang="fr"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="robots" content="noindex,nofollow,noarchive"><meta name="referrer" content="no-referrer">
    <title><?= $escape((string)$caserne['nom']) ?> - Suivi des verifications</title>
    <link rel="stylesheet" href="/assets/public-statistics.css?v=<?= hash_file('sha256',__DIR__.'/../assets/public-statistics.css') ?>">
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
<div class="mx-auto max-w-[1280px] px-3 py-4 sm:px-6">
    <header class="border-b-4 border-sky-500 bg-slate-900 px-5 py-5 text-white sm:px-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div><p class="text-xs font-semibold uppercase text-sky-200">VerifApp - Suivi des verifications</p><h1 class="mt-1 break-words text-2xl font-bold"><?= $escape((string)$caserne['nom']) ?></h1></div>
            <p class="text-xs text-slate-300">Actualise le <?= $escape(date('d/m/Y H:i')) ?></p>
        </div>
        <nav aria-label="Sections du suivi" class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-sm font-semibold">
            <a href="#planning" class="text-white underline decoration-sky-400 underline-offset-4">Planning journalier</a>
            <a href="#annual" class="text-white underline decoration-sky-400 underline-offset-4">Vue annuelle</a>
            <a href="#anomalies" class="text-white underline decoration-sky-400 underline-offset-4">Anomalies</a>
        </nav>
    </header>
    <main class="mt-4 space-y-4">
        <section class="bg-white p-4 sm:p-5">
            <form method="get" action="/index.php" class="grid grid-cols-1 items-end gap-3 sm:grid-cols-[180px_minmax(180px,1fr)_auto]">
                <?php foreach ($baseParams as $name=>$value): ?><input type="hidden" name="<?= $escape($name) ?>" value="<?= $escape((string)$value) ?>"><?php endforeach; ?>
                <input type="hidden" name="year" value="<?= $selectedYear ?>"><input type="hidden" name="compare_year" value="<?= $comparisonYear ?>">
                <label class="text-sm font-semibold text-slate-600">Mois<input type="month" name="month" value="<?= $monthValue ?>" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"></label>
                <label class="text-sm font-semibold text-slate-600">Vehicule<select name="vehicule_id" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5">
                    <option value="0">Tous les vehicules</option>
                    <?php foreach ($vehicles as $vehicle): ?><option value="<?= (int)$vehicle['id'] ?>" <?= (int)$vehicle['id'] === $selectedVehicleId ? 'selected' : '' ?>><?= $escape((string)$vehicle['nom']) ?></option><?php endforeach; ?>
                </select></label>
                <button class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Afficher</button>
            </form>
        </section>
        <section aria-label="Indicateurs du mois" class="grid grid-cols-2 gap-3 lg:grid-cols-3 xl:grid-cols-6">
            <article class="rounded-lg border border-sky-200 bg-sky-50 p-4"><h2 class="text-xs font-semibold text-sky-800">Couverture du mois</h2><p class="mt-2 text-3xl font-bold"><?= $expectedPostesPerDay > 0 ? $conformityRate.'%' : '-' ?></p></article>
            <article class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"><h2 class="text-xs font-semibold text-emerald-800">Journees a 100 %</h2><p class="mt-2 text-2xl font-bold"><?= $totals['jours_complets'] ?> / <?= $eligibleDays ?></p></article>
            <article class="rounded-lg border border-slate-200 bg-white p-4"><h2 class="text-xs font-semibold text-slate-600">Creneaux couverts / attendus</h2><p class="mt-2 break-words text-xl font-bold"><?= $totals['postes_couverts'] ?> / <?= $coverage['expectedChecks'] ?></p></article>
            <article class="rounded-lg border border-slate-200 bg-white p-4"><h2 class="text-xs font-semibold text-slate-600">Verifications enregistrees</h2><p class="mt-2 text-3xl font-bold"><?= $totals['total_verifs'] ?></p></article>
            <article class="rounded-lg border border-rose-200 bg-rose-50 p-4"><h2 class="text-xs font-semibold text-rose-800">Verifications non conformes</h2><p class="mt-2 text-3xl font-bold"><?= $totals['non_conformes'] ?></p></article>
            <article class="rounded-lg border border-slate-200 bg-white p-4"><h2 class="text-xs font-semibold text-slate-600">Objectif journalier</h2><p class="mt-2 text-2xl font-bold"><?= $expectedPostesPerDay ?></p><p class="text-xs text-slate-500">creneaux / jour</p></article>
        </section>
        <section id="planning" class="scroll-mt-4">
            <div class="mb-3"><h2 class="text-xl font-bold"><?= $monthLabel ?></h2><p class="mt-1 text-xs leading-relaxed text-slate-500">Une garde de 24 h couvre matin et soir en mode deux creneaux, tout en comptant pour un seul formulaire. La couverture est independante de la conformite du materiel.</p></div>
            <?php if ($expectedPostes === []): ?><p class="mb-3 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Aucun poste verifiable dans ce perimetre.</p><?php endif; ?>
            <?php require __DIR__.'/partials/verification_calendar.php'; ?>
        </section>
        <section id="anomalies" class="scroll-mt-4 bg-white p-4 sm:p-5">
            <h2 class="text-lg font-bold">Anomalies</h2>
            <h3 class="mt-4 text-sm font-semibold text-slate-600"><?= $monthLabel ?></h3>
            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <?php foreach (['reports'=>'Signalements terrain','new'=>'Nouvelles anomalies','resolved'=>'Resolues dans le mois'] as $key=>$label): ?><article class="border-l-2 border-sky-200 px-3 py-2"><h4 class="text-xs text-slate-500"><?= $label ?></h4><p class="mt-1 text-2xl font-bold"><?= $anomalyStats[$key] ?></p></article><?php endforeach; ?>
            </div>
            <h3 class="mt-5 text-sm font-semibold text-slate-600">Etat actuel</h3>
            <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                <?php foreach (['waiting'=>'A traiter','in_progress'=>'En cours de traitement','urgent'=>'Actives de priorite haute / critique'] as $key=>$label): ?><article class="border-l-2 border-amber-300 px-3 py-2"><h4 class="text-xs text-slate-500"><?= $label ?></h4><p class="mt-1 text-2xl font-bold"><?= $anomalyStats[$key] ?></p></article><?php endforeach; ?>
            </div>
            <p class="mt-4 text-xs text-slate-500">Plusieurs signalements peuvent concerner une meme anomalie. Les compteurs d etat actuel ne sont pas limites au mois selectionne.</p>
        </section>
        <section id="annual" class="scroll-mt-4">
            <h2 class="text-xl font-bold">Vue annuelle <?= $selectedYear ?></h2>
            <form method="get" action="/index.php" class="my-3 grid grid-cols-2 items-end gap-3 sm:grid-cols-[150px_180px_auto]">
                <?php foreach ($baseParams as $name=>$value): ?><input type="hidden" name="<?= $escape($name) ?>" value="<?= $escape((string)$value) ?>"><?php endforeach; ?>
                <input type="hidden" name="month" value="<?= $monthValue ?>"><input type="hidden" name="vehicule_id" value="<?= $selectedVehicleId ?>">
                <label class="text-sm font-semibold text-slate-600">Annee<select name="year" class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5"><?php foreach ($yearOptions as $year): ?><option value="<?= $year ?>" <?= $year === $selectedYear ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?></select></label>
                <label class="text-sm font-semibold text-slate-600">Comparer avec<select name="compare_year" <?= $comparisonOptions === [] ? 'disabled' : '' ?> class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 disabled:text-slate-400"><option value="0"><?= $comparisonOptions === [] ? 'Aucune autre annee' : 'Sans comparaison' ?></option><?php foreach ($comparisonOptions as $year): ?><option value="<?= $year ?>" <?= $year === $comparisonYear ? 'selected' : '' ?>><?= $year ?></option><?php endforeach; ?></select></label>
                <button class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white">Afficher</button>
            </form>
            <?php require __DIR__.'/partials/verification_annual_chart.php'; ?>
        </section>
    </main>
    <footer class="mt-5 border-t border-slate-300 py-3 text-xs text-slate-500">VerifApp - Statistiques en lecture seule - <?= $escape((string)$caserne['nom']) ?></footer>
</div>
</body></html>
