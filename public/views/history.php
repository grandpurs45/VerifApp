<?php

declare(strict_types=1);

$pageTitle = 'Historique - VerifApp';
$pageHeading = 'Historique des verifications';
$pageSubtitle = 'Filtres rapides et acces detail/export.';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="mb-3 flex justify-end">
        <a href="/index.php?controller=verifications&action=monthly" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
            Vue mensuelle matin/soir
        </a>
    </div>

    <form method="get" action="/index.php" class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <input type="hidden" name="controller" value="verifications">
        <input type="hidden" name="action" value="history">

        <select name="vehicule_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <option value="">Tous les vehicules</option>
            <?php foreach ($vehicles as $vehicle): ?>
                <option value="<?= (int) $vehicle['id'] ?>" <?= $filters['vehicule_id'] === (string) $vehicle['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="poste_id" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <option value="">Tous les postes</option>
            <?php foreach ($postes as $poste): ?>
                <option value="<?= (int) $poste['id'] ?>" <?= $filters['poste_id'] === (string) $poste['id'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="statut_global" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <option value="">Tous les statuts</option>
            <option value="conforme" <?= $filters['statut_global'] === 'conforme' ? 'selected' : '' ?>>Conforme</option>
            <option value="non_conforme" <?= $filters['statut_global'] === 'non_conforme' ? 'selected' : '' ?>>Non conforme</option>
        </select>

        <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
        <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-4 py-3 text-sm">
        <label class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-3 text-sm">
            <input type="checkbox" name="with_anomalies" value="1" <?= $filters['with_anomalies'] === '1' ? 'checked' : '' ?>>
            Avec anomalies
        </label>

        <button type="submit" class="md:col-span-3 rounded-xl bg-slate-900 text-white px-5 py-3 text-sm font-semibold">
            Filtrer
        </button>
    </form>
</section>

<section class="bg-white rounded-2xl shadow overflow-hidden">
    <?php if ($history === []): ?>
        <div class="p-6 text-slate-500">Aucune verification trouvee.</div>
    <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left px-4 py-3">Date</th>
                        <th class="text-left px-4 py-3">Vehicule</th>
                        <th class="text-left px-4 py-3">Poste</th>
                        <th class="text-left px-4 py-3">Agent</th>
                        <th class="text-left px-4 py-3">Statut</th>
                        <th class="text-left px-4 py-3">Anomalies</th>
                        <th class="text-left px-4 py-3">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($history as $row): ?>
                        <tr class="border-t border-slate-100">
                            <td class="px-4 py-3"><?= htmlspecialchars((string) $row['date_heure'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['poste_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['agent'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= htmlspecialchars($row['statut_global'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="px-4 py-3"><?= (int) $row['anomalies_ouvertes'] ?></td>
                            <td class="px-4 py-3">
                                <a href="/index.php?controller=verifications&action=show&id=<?= (int) $row['id'] ?>" class="text-slate-900 underline">
                                    Ouvrir
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
