<?php

declare(strict_types=1);

$pageTitle = 'Carburant - VerifApp';
$pageHeading = 'Carburant';
$pageSubtitle = 'Module en preparation pour suivre la distribution de carburant.';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<section class="rounded-2xl bg-white p-4 shadow md:p-6">
    <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Objectif</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Suivi de cuve</h2>
            <p class="mt-1 text-sm text-slate-600">
                Preparer la saisie des pleins, le suivi du compteur cuve et les controles d incoherence.
            </p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Terrain</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Formulaire QR</h2>
            <p class="mt-1 text-sm text-slate-600">
                Date, agent, vehicule, quantite, compteur cuve apres plein et kilometrage vehicule.
            </p>
        </article>
        <article class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Analyse</p>
            <h2 class="mt-2 text-lg font-bold text-slate-900">Consommation</h2>
            <p class="mt-1 text-sm text-slate-600">
                Suivi des vehicules de la caserne, pleins exterieurs et alertes d ecart compteur.
            </p>
        </article>
    </div>
</section>

<section class="rounded-2xl bg-white p-4 shadow md:p-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Fonctionnalites prevues</h2>
            <p class="mt-1 text-sm text-slate-600">
                Ce menu reserve l emplacement du futur module sans modifier les donnees existantes.
            </p>
        </div>
        <span class="inline-flex w-fit rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
            A developper
        </span>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-2">
        <div class="rounded-xl border border-slate-200 p-4">
            <h3 class="font-bold text-slate-900">Distribution carburant</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                <li>Formulaire terrain accessible par QR code.</li>
                <li>Vehicule de la caserne ou vehicule exterieur.</li>
                <li>Quantite distribuee, compteur cuve et kilometrage.</li>
            </ul>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <h3 class="font-bold text-slate-900">Controle des ecarts</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                <li>Alerte si compteur cuve incoherent avec la quantite saisie.</li>
                <li>Detection possible des pleins non declares.</li>
                <li>Historique exploitable par caserne.</li>
            </ul>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <h3 class="font-bold text-slate-900">Consommation vehicules</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                <li>Suivi par vehicule et par periode.</li>
                <li>Rapprochement litres, kilometres et consommation moyenne.</li>
                <li>Indicateurs pour vehicules internes.</li>
            </ul>
        </div>
        <div class="rounded-xl border border-slate-200 p-4">
            <h3 class="font-bold text-slate-900">Cuve</h3>
            <ul class="mt-2 space-y-1 text-sm text-slate-600">
                <li>Niveau courant de la cuve.</li>
                <li>Historique des receptions carburant.</li>
                <li>Visualisation du niveau a prevoir.</li>
            </ul>
        </div>
    </div>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
