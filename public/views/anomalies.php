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
            ?>
            <article class="bg-white rounded-2xl shadow p-4 md:p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold">
                            Anomalie #<?= (int) $anomaly['id'] ?> - <?= htmlspecialchars((string) $anomaly['controle_libelle'], ENT_QUOTES, 'UTF-8') ?>
                        </h2>
                        <p class="text-sm text-slate-600 mt-1">
                            <?= htmlspecialchars((string) $anomaly['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?> / <?= htmlspecialchars((string) $anomaly['poste_nom'], ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <p class="text-sm text-slate-500 mt-1">Zone: <?= htmlspecialchars((string) $anomaly['controle_zone'], ENT_QUOTES, 'UTF-8') ?></p>
                        <p class="text-sm text-slate-500 mt-1">Creee le <?= htmlspecialchars((string) $anomaly['date_creation'], ENT_QUOTES, 'UTF-8') ?></p>
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
            </article>
        <?php endforeach; ?>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
