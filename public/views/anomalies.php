<?php

declare(strict_types=1);

$statusLabels = [
    'actives' => 'Actives (A traiter + En cours)',
    'ouverte' => 'A traiter',
    'en_cours' => 'En cours',
    'resolue' => 'Resolue',
];

$statusClasses = [
    'ouverte' => 'bg-red-100 text-red-700 border-red-200',
    'en_cours' => 'bg-amber-100 text-amber-700 border-amber-200',
    'resolue' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
];

$priorityLabels = [
    'basse' => 'Basse',
    'moyenne' => 'Moyenne',
    'haute' => 'Haute',
    'critique' => 'Critique',
];

$priorityClasses = [
    'basse' => 'bg-slate-100 text-slate-700',
    'moyenne' => 'bg-sky-100 text-sky-700',
    'haute' => 'bg-orange-100 text-orange-700',
    'critique' => 'bg-red-100 text-red-700',
];

$managerUser = $_SESSION['manager_user'] ?? null;
$managerUserId = is_array($managerUser) && isset($managerUser['id']) ? (int) $managerUser['id'] : 0;

$pageTitle = 'Anomalies - VerifApp';
$pageHeading = 'Suivi des anomalies';
$pageSubtitle = 'Vue simple: qui fait quoi, et quoi traiter en priorite.';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (isset($_GET['updated']) && $_GET['updated'] === '1'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Anomalie mise a jour.
    </section>
<?php endif; ?>

<?php if (!$anomaliesAvailable): ?>
    <section class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-700 text-sm">
        La table <strong>anomalies</strong> est absente. Applique la migration <code>007_create_anomalies.sql</code>.
    </section>
<?php endif; ?>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <form method="get" action="/index.php" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <input type="hidden" name="controller" value="anomalies">
        <input type="hidden" name="action" value="index">

        <select name="statut" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <option value="">Tous statuts</option>
            <?php foreach ($statusLabels as $statusKey => $label): ?>
                <option value="<?= $statusKey ?>" <?= $filters['statut'] === $statusKey ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <select name="priorite" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <option value="">Toutes priorites</option>
            <?php foreach ($priorityLabels as $priorityKey => $label): ?>
                <option value="<?= $priorityKey ?>" <?= $filters['priorite'] === $priorityKey ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>

        <select name="assigne_a" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <option value="">Toutes assignations</option>
            <option value="none" <?= $filters['assigne_a'] === 'none' ? 'selected' : '' ?>>Non assignees</option>
            <?php foreach ($assignableUsers as $assignableUser): ?>
                <option value="<?= (int) $assignableUser['id'] ?>" <?= $filters['assigne_a'] === (string) $assignableUser['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $assignableUser['nom'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
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

        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">

        <button type="submit" class="rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-semibold">
            Appliquer les filtres
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
            <?php
            $statusKey = (string) ($anomaly['statut'] ?? '');
            if ($statusKey === 'cloturee') {
                $statusKey = 'resolue';
            }
            $priorityKey = (string) ($anomaly['priorite'] ?? '');
            $statusLabel = $statusLabels[$statusKey] ?? ucfirst($statusKey);
            $statusClass = $statusClasses[$statusKey] ?? 'bg-slate-100 text-slate-700 border-slate-200';
            $priorityLabel = $priorityLabels[$priorityKey] ?? ucfirst($priorityKey);
            $priorityClass = $priorityClasses[$priorityKey] ?? 'bg-slate-100 text-slate-700';
            $assigneeId = isset($anomaly['assigne_a']) ? (int) $anomaly['assigne_a'] : 0;
            $isAssigned = $assigneeId > 0;
            $canAssignToMe = $managerUserId > 0 && !$isAssigned && $statusKey !== 'resolue';
            $occurrenceCount = max(1, (int) ($anomaly['occurrence_count'] ?? 1));
            $occurrences = $occurrencesByAnomaly[(int) $anomaly['id']] ?? [];
            ?>
            <article class="overflow-hidden rounded-2xl border <?= $statusKey === 'ouverte' ? 'border-red-200' : 'border-slate-200' ?> bg-white shadow">
                <div class="h-1 <?= $priorityKey === 'critique' ? 'bg-red-600' : ($priorityKey === 'haute' ? 'bg-orange-500' : ($priorityKey === 'moyenne' ? 'bg-sky-500' : 'bg-slate-400')) ?>"></div>
                <div class="p-4 md:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Anomalie #<?= (int) $anomaly['id'] ?></p>
                        <h2 class="mt-1 text-xl font-bold text-slate-950">
                            <?= htmlspecialchars((string) $anomaly['controle_libelle'], ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <p class="mt-2 text-base font-semibold text-slate-800">
                            <?= htmlspecialchars((string) $anomaly['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?>
                            <span class="font-normal text-slate-400">/</span>
                            <?= htmlspecialchars((string) $anomaly['poste_nom'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p class="mt-1 text-sm text-slate-500">Emplacement : <?= htmlspecialchars((string) $anomaly['controle_zone'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold <?= $statusClass ?>">
                            <?= htmlspecialchars($statusLabel, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold <?= $priorityClass ?>">
                            Priorite <?= htmlspecialchars($priorityLabel, ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                </div>

                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-3">
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <p class="text-xs text-slate-500">Premiere remontee</p>
                        <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars((string) $anomaly['date_creation'], ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="rounded-lg bg-slate-50 px-3 py-2">
                        <p class="text-xs text-slate-500">Derniere remontee</p>
                        <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars((string) ($anomaly['last_report_at'] ?? $anomaly['date_creation']), ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div class="rounded-lg <?= $occurrenceCount > 1 ? 'bg-amber-50 text-amber-900' : 'bg-slate-50 text-slate-800' ?> px-3 py-2">
                        <p class="text-xs opacity-70">Occurrences</p>
                        <p class="text-sm font-bold"><?= $occurrenceCount ?> remontee(s)</p>
                    </div>
                </div>

                <div class="mt-3 text-sm">
                    <div class="inline-flex items-center gap-2 rounded-xl px-3 py-2 <?= $isAssigned ? 'bg-sky-100 border border-sky-200 text-sky-800' : 'bg-red-600 border border-red-700 text-white' ?>">
                        <span class="text-xs font-semibold uppercase tracking-wide">Responsable</span>
                        <strong class="text-sm"><?= htmlspecialchars((string) ($anomaly['assigne_nom'] ?? 'Non assignee'), ENT_QUOTES, 'UTF-8') ?></strong>
                    </div>
                    <p class="mt-1">
                        <a class="underline text-slate-700" href="/index.php?controller=verifications&action=show&id=<?= (int) $anomaly['verification_id'] ?>">
                            Ouvrir verification #<?= (int) $anomaly['verification_id'] ?>
                        </a>
                    </p>
                </div>

                <?php if ($occurrences !== []): ?>
                    <details class="mt-4 rounded-xl border border-slate-200 bg-slate-50">
                        <summary class="cursor-pointer list-none px-4 py-3 text-sm font-semibold text-slate-800">
                            Voir l historique des <?= $occurrenceCount ?> remontee(s)
                        </summary>
                        <div class="border-t border-slate-200 px-4 py-2">
                            <?php foreach ($occurrences as $occurrence): ?>
                                <div class="flex flex-col gap-1 border-b border-slate-200 py-3 last:border-0 sm:flex-row sm:items-start sm:justify-between">
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800"><?= htmlspecialchars((string) ($occurrence['date_remontee'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                                        <p class="text-xs text-slate-500">Par <?= htmlspecialchars((string) ($occurrence['agent'] ?? 'Non renseigne'), ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php if (trim((string) ($occurrence['commentaire'] ?? '')) !== ''): ?>
                                            <p class="mt-1 text-sm text-slate-700"><?= htmlspecialchars((string) $occurrence['commentaire'], ENT_QUOTES, 'UTF-8') ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <a class="text-sm font-semibold text-sky-700 underline" href="/index.php?controller=verifications&action=show&id=<?= (int) ($occurrence['verification_id'] ?? 0) ?>">Verification #<?= (int) ($occurrence['verification_id'] ?? 0) ?></a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </details>
                <?php endif; ?>

                <?php if (($anomaly['commentaire'] ?? null) !== null && trim((string) $anomaly['commentaire']) !== ''): ?>
                    <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm">
                        <?= nl2br(htmlspecialchars((string) $anomaly['commentaire'], ENT_QUOTES, 'UTF-8')) ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/index.php?controller=anomalies&action=update" class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-3">
                    <input type="hidden" name="anomaly_id" value="<?= (int) $anomaly['id'] ?>">
                    <input type="hidden" name="return_query" value="<?= htmlspecialchars($returnQuery, ENT_QUOTES, 'UTF-8') ?>">
                    <select name="statut" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        <?php foreach (['ouverte', 'en_cours', 'resolue'] as $statusOption): ?>
                            <option value="<?= $statusOption ?>" <?= $statusKey === $statusOption ? 'selected' : '' ?>>
                                <?= htmlspecialchars($statusLabels[$statusOption], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="priorite" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        <?php foreach ($priorityLabels as $priorityOption => $label): ?>
                            <option value="<?= $priorityOption ?>" <?= $priorityKey === $priorityOption ? 'selected' : '' ?>>
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="assigne_a" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                        <option value="">Non assignee</option>
                        <?php foreach ($assignableUsers as $assignableUser): ?>
                            <option value="<?= (int) $assignableUser['id'] ?>" <?= (int) ($anomaly['assigne_a'] ?? 0) === (int) $assignableUser['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars((string) $assignableUser['nom'], ENT_QUOTES, 'UTF-8') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="commentaire" value="<?= htmlspecialchars((string) ($anomaly['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Commentaire de suivi" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
                    <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-3 text-sm font-semibold">Enregistrer</button>
                    <?php if ($canAssignToMe): ?>
                        <button type="submit" name="assign_to_me" value="1" class="md:col-span-5 rounded-xl bg-sky-600 text-white px-4 py-3 text-sm font-semibold">
                            M'assigner cette anomalie
                        </button>
                    <?php endif; ?>
                </form>
                </div>
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
