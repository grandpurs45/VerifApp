<?php

declare(strict_types=1);

$statusLabels = [
    'actives' => 'Actives',
    'ouverte' => 'A traiter',
    'en_cours' => 'En cours',
    'resolue' => 'Resolue',
];
$statusClasses = [
    'ouverte' => 'border-red-300 bg-red-50 text-red-800',
    'en_cours' => 'border-amber-300 bg-amber-50 text-amber-900',
    'resolue' => 'border-emerald-300 bg-emerald-50 text-emerald-800',
];
$priorityLabels = [
    'basse' => 'Basse',
    'moyenne' => 'Moyenne',
    'haute' => 'Haute',
    'critique' => 'Critique',
];
$priorityClasses = [
    'basse' => 'bg-slate-100 text-slate-700',
    'moyenne' => 'bg-sky-100 text-sky-800',
    'haute' => 'bg-orange-100 text-orange-800',
    'critique' => 'bg-red-600 text-white',
];
$priorityBorders = [
    'basse' => 'border-l-slate-400',
    'moyenne' => 'border-l-sky-500',
    'haute' => 'border-l-orange-500',
    'critique' => 'border-l-red-600',
];
$managerUser = $_SESSION['manager_user'] ?? null;
$managerUserId = is_array($managerUser) && isset($managerUser['id']) ? (int) $managerUser['id'] : 0;
$summary = ['active' => 0, 'unassigned' => 0, 'critical' => 0, 'reports' => 0];
foreach ($anomalies as $summaryAnomaly) {
    $summaryStatus = (string) ($summaryAnomaly['statut'] ?? '');
    if (in_array($summaryStatus, ['ouverte', 'en_cours'], true)) {
        $summary['active']++;
    }
    if (empty($summaryAnomaly['assigne_a']) && in_array($summaryStatus, ['ouverte', 'en_cours'], true)) {
        $summary['unassigned']++;
    }
    if (($summaryAnomaly['priorite'] ?? '') === 'critique' && in_array($summaryStatus, ['ouverte', 'en_cours'], true)) {
        $summary['critical']++;
    }
    $summary['reports'] += max(1, (int) ($summaryAnomaly['occurrence_count'] ?? 1));
}
$formatDate = static function (?string $value): string {
    $timestamp = $value !== null ? strtotime($value) : false;
    return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : '-';
};

$pageTitle = 'Anomalies - VerifApp';
$pageHeading = 'Anomalies';
$pageSubtitle = 'Prioriser, assigner et suivre les problemes remontes.';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
    <section class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">Anomalie mise a jour.</section>
<?php endif; ?>

<?php if (!$anomaliesAvailable): ?>
    <section class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
        La table <strong>anomalies</strong> est absente. Applique la migration <code>007_create_anomalies.sql</code>.
    </section>
<?php endif; ?>

<section class="grid grid-cols-2 overflow-hidden rounded-lg border border-slate-200 bg-white md:grid-cols-4">
    <?php foreach ([
        ['Actives', $summary['active'], 'text-slate-950'],
        ['Non assignees', $summary['unassigned'], $summary['unassigned'] > 0 ? 'text-red-700' : 'text-slate-950'],
        ['Critiques', $summary['critical'], $summary['critical'] > 0 ? 'text-red-700' : 'text-slate-950'],
        ['Remontees', $summary['reports'], 'text-slate-950'],
    ] as $index => $summaryItem): ?>
        <div class="border-slate-200 p-4 <?= $index < 3 ? 'md:border-r' : '' ?> <?= $index === 0 ? 'border-b border-r md:border-b-0' : ($index === 1 ? 'border-b md:border-b-0' : ($index === 2 ? 'border-r md:border-r' : '')) ?>">
            <p class="text-xs font-semibold uppercase text-slate-500"><?= $summaryItem[0] ?></p>
            <p class="mt-1 text-2xl font-bold <?= $summaryItem[2] ?>"><?= (int) $summaryItem[1] ?></p>
        </div>
    <?php endforeach; ?>
</section>

<section class="rounded-lg border border-slate-200 bg-white p-4">
    <form method="get" action="/index.php" class="grid grid-cols-1 gap-3 md:grid-cols-4">
        <input type="hidden" name="controller" value="anomalies">
        <input type="hidden" name="action" value="index">
        <label class="text-xs font-semibold text-slate-600">Statut
            <select name="statut" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                <option value="">Tous les statuts</option>
                <?php foreach ($statusLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filters['statut'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-xs font-semibold text-slate-600">Priorite
            <select name="priorite" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                <option value="">Toutes les priorites</option>
                <?php foreach ($priorityLabels as $key => $label): ?>
                    <option value="<?= $key ?>" <?= $filters['priorite'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-xs font-semibold text-slate-600">Responsable
            <select name="assigne_a" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                <option value="">Tous</option>
                <option value="none" <?= $filters['assigne_a'] === 'none' ? 'selected' : '' ?>>Non assignees</option>
                <?php foreach ($assignableUsers as $user): ?>
                    <option value="<?= (int) $user['id'] ?>" <?= $filters['assigne_a'] === (string) $user['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $user['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-xs font-semibold text-slate-600">Engin
            <select name="vehicule_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                <option value="">Tous les engins</option>
                <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?= (int) $vehicle['id'] ?>" <?= $filters['vehicule_id'] === (string) $vehicle['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-xs font-semibold text-slate-600">Poste
            <select name="poste_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                <option value="">Tous les postes</option>
                <?php foreach ($postes as $poste): ?>
                    <option value="<?= (int) $poste['id'] ?>" <?= $filters['poste_id'] === (string) $poste['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $poste['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="text-xs font-semibold text-slate-600">Depuis le
            <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
        </label>
        <label class="text-xs font-semibold text-slate-600">Jusqu'au
            <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
        </label>
        <div class="flex items-end gap-2">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filtrer</button>
            <a href="/index.php?controller=anomalies&action=index" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reinitialiser</a>
        </div>
    </form>
</section>

<section class="space-y-3">
    <?php if ($anomalies === []): ?>
        <div class="rounded-lg border border-slate-200 bg-white p-6 text-slate-500">Aucune anomalie pour ces filtres.</div>
    <?php else: ?>
        <?php foreach ($anomalies as $anomaly): ?>
            <?php
            $statusKey = (string) ($anomaly['statut'] ?? '');
            $statusKey = $statusKey === 'cloturee' ? 'resolue' : $statusKey;
            $priorityKey = (string) ($anomaly['priorite'] ?? '');
            $assigneeId = (int) ($anomaly['assigne_a'] ?? 0);
            $occurrenceCount = max(1, (int) ($anomaly['occurrence_count'] ?? 1));
            $occurrences = $occurrencesByAnomaly[(int) $anomaly['id']] ?? [];
            ?>
            <article class="overflow-hidden rounded-lg border border-l-4 border-slate-200 <?= $priorityBorders[$priorityKey] ?? 'border-l-slate-400' ?> bg-white">
                <div class="grid grid-cols-1 gap-4 p-4 md:grid-cols-12 md:items-center">
                    <div class="min-w-0 md:col-span-6">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="text-lg font-bold text-slate-950"><?= htmlspecialchars((string) $anomaly['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?></h2>
                            <span class="text-xs font-semibold text-slate-400">#<?= (int) $anomaly['id'] ?></span>
                        </div>
                        <p class="mt-1 font-semibold text-slate-800"><?= htmlspecialchars((string) $anomaly['controle_libelle'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="mt-1 text-sm text-slate-500">
                            <?= htmlspecialchars((string) $anomaly['poste_nom'], ENT_QUOTES, 'UTF-8') ?> -
                            <?= htmlspecialchars((string) $anomaly['controle_zone'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-semibold uppercase text-slate-500">Derniere remontee</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800"><?= htmlspecialchars($formatDate((string) ($anomaly['last_report_at'] ?? $anomaly['date_creation'])), ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-xs <?= $occurrenceCount > 1 ? 'font-bold text-amber-700' : 'text-slate-500' ?>"><?= $occurrenceCount ?> occurrence<?= $occurrenceCount > 1 ? 's' : '' ?></p>
                    </div>
                    <div class="md:col-span-2">
                        <p class="text-xs font-semibold uppercase text-slate-500">Responsable</p>
                        <p class="mt-1 text-sm font-bold <?= $assigneeId > 0 ? 'text-slate-800' : 'text-red-700' ?>"><?= htmlspecialchars((string) ($anomaly['assigne_nom'] ?? 'Non assignee'), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="flex flex-wrap gap-2 md:col-span-2 md:justify-end">
                        <span class="rounded-md border px-2 py-1 text-xs font-bold <?= $statusClasses[$statusKey] ?? 'border-slate-300 bg-slate-50 text-slate-700' ?>"><?= htmlspecialchars($statusLabels[$statusKey] ?? ucfirst($statusKey), ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="rounded-md px-2 py-1 text-xs font-bold <?= $priorityClasses[$priorityKey] ?? 'bg-slate-100 text-slate-700' ?>"><?= htmlspecialchars($priorityLabels[$priorityKey] ?? ucfirst($priorityKey), ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>

                <details class="border-t border-slate-200">
                    <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-50">Historique et traitement</summary>
                    <div class="grid grid-cols-1 border-t border-slate-200 lg:grid-cols-2">
                        <div class="border-b border-slate-200 p-4 lg:border-b-0 lg:border-r">
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="font-bold text-slate-900">Remontees terrain</h3>
                                <a class="text-sm font-semibold text-sky-700 underline" href="/index.php?controller=verifications&action=show&id=<?= (int) $anomaly['verification_id'] ?>">Verification initiale</a>
                            </div>
                            <?php if ($occurrences === []): ?>
                                <p class="mt-3 text-sm text-slate-500">Aucun detail disponible.</p>
                            <?php else: ?>
                                <ol class="mt-3 divide-y divide-slate-200 border-y border-slate-200">
                                    <?php foreach ($occurrences as $occurrence): ?>
                                        <li class="py-3">
                                            <div class="flex flex-wrap items-baseline justify-between gap-2">
                                                <p class="text-sm font-bold text-slate-800"><?= htmlspecialchars($formatDate((string) ($occurrence['date_remontee'] ?? '')), ENT_QUOTES, 'UTF-8') ?></p>
                                                <a class="text-xs font-semibold text-sky-700 underline" href="/index.php?controller=verifications&action=show&id=<?= (int) ($occurrence['verification_id'] ?? 0) ?>">Verification #<?= (int) ($occurrence['verification_id'] ?? 0) ?></a>
                                            </div>
                                            <p class="mt-1 text-sm text-slate-600">Agent : <?= htmlspecialchars((string) ($occurrence['agent'] ?? 'Non renseigne'), ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php if (trim((string) ($occurrence['commentaire'] ?? '')) !== ''): ?>
                                                <p class="mt-1 border-l-2 border-slate-300 pl-2 text-sm text-slate-700"><?= htmlspecialchars((string) $occurrence['commentaire'], ENT_QUOTES, 'UTF-8') ?></p>
                                            <?php endif; ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ol>
                            <?php endif; ?>
                        </div>
                        <form method="post" action="/index.php?controller=anomalies&action=update" class="p-4">
                            <input type="hidden" name="anomaly_id" value="<?= (int) $anomaly['id'] ?>">
                            <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery, ENT_QUOTES, 'UTF-8') ?>">
                            <h3 class="font-bold text-slate-900">Traitement</h3>
                            <div class="mt-3 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <label class="text-xs font-semibold text-slate-600">Statut
                                    <select name="statut" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                                        <?php foreach (['ouverte', 'en_cours', 'resolue'] as $option): ?>
                                            <option value="<?= $option ?>" <?= $statusKey === $option ? 'selected' : '' ?>><?= htmlspecialchars($statusLabels[$option], ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                                <label class="text-xs font-semibold text-slate-600">Priorite
                                    <select name="priorite" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                                        <?php foreach ($priorityLabels as $option => $label): ?>
                                            <option value="<?= $option ?>" <?= $priorityKey === $option ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </label>
                            </div>
                            <label class="mt-3 block text-xs font-semibold text-slate-600">Responsable
                                <select name="assigne_a" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
                                    <option value="">Non assignee</option>
                                    <?php foreach ($assignableUsers as $user): ?>
                                        <option value="<?= (int) $user['id'] ?>" <?= $assigneeId === (int) $user['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string) $user['nom'], ENT_QUOTES, 'UTF-8') ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <label class="mt-3 block text-xs font-semibold text-slate-600">Commentaire de suivi
                                <textarea name="commentaire" rows="3" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900"><?= htmlspecialchars((string) ($anomaly['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                            </label>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
                                <?php if ($managerUserId > 0 && $assigneeId <= 0 && $statusKey !== 'resolue'): ?>
                                    <button type="submit" name="assign_to_me" value="1" class="rounded-lg bg-sky-700 px-4 py-2 text-sm font-semibold text-white">Me l'assigner</button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </details>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
