<?php

declare(strict_types=1);

$pageTitle = 'Audit securite - VerifApp';
$pageHeading = 'Audit securite';
$pageSubtitle = 'Connexions gestionnaire: succes, echecs et verrouillages.';
$pageBackUrl = '/index.php?controller=manager_admin&action=menu';
$pageBackLabel = 'Retour administration';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<section class="rounded-2xl bg-white shadow p-5">
    <form method="get" action="/index.php" class="grid grid-cols-1 md:grid-cols-6 gap-2">
        <input type="hidden" name="controller" value="manager_admin">
        <input type="hidden" name="action" value="security_audit">
        <?php if (($isPlatformAdmin ?? false) === true): ?>
            <div>
                <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Caserne</label>
                <select name="caserne_id" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Toutes</option>
                    <?php foreach (($casernes ?? []) as $caserne): ?>
                        <?php $cid = (int) ($caserne['id'] ?? 0); ?>
                        <option value="<?= $cid ?>" <?= ($selectedCaserneId ?? null) === $cid ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string) ($caserne['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Du</label>
            <input type="date" name="date_from" value="<?= htmlspecialchars((string) ($filters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Au</label>
            <input type="date" name="date_to" value="<?= htmlspecialchars((string) ($filters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Type</label>
            <select name="event_type" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <option value="">Tous</option>
                <option value="success" <?= ($filters['event_type'] ?? '') === 'success' ? 'selected' : '' ?>>Succes</option>
                <option value="failure" <?= ($filters['event_type'] ?? '') === 'failure' ? 'selected' : '' ?>>Echec</option>
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">Identifiant</label>
            <input type="text" name="identifier" value="<?= htmlspecialchars((string) ($filters['identifier'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="email ou nom">
        </div>
        <div>
            <label class="text-xs font-semibold uppercase tracking-wide text-slate-500">IP</label>
            <input type="text" name="ip_address" value="<?= htmlspecialchars((string) ($filters['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" placeholder="192.168.x.x">
        </div>
        <div class="md:col-span-6 flex flex-wrap gap-2">
            <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Filtrer</button>
            <a href="/index.php?controller=manager_admin&action=security_audit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reset</a>
            <?php
            $exportQuery = [
                'controller' => 'manager_admin',
                'action' => 'security_audit_export_csv',
                'date_from' => (string) ($filters['date_from'] ?? ''),
                'date_to' => (string) ($filters['date_to'] ?? ''),
                'event_type' => (string) ($filters['event_type'] ?? ''),
                'identifier' => (string) ($filters['identifier'] ?? ''),
                'ip_address' => (string) ($filters['ip_address'] ?? ''),
            ];
            if (($isPlatformAdmin ?? false) === true && ($selectedCaserneId ?? null) !== null) {
                $exportQuery['caserne_id'] = (string) $selectedCaserneId;
            }
            ?>
            <a href="/index.php?<?= htmlspecialchars(http_build_query($exportQuery), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Export CSV</a>
        </div>
    </form>
</section>

<section class="rounded-2xl bg-white shadow p-5">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-xs uppercase tracking-wide text-slate-500">Total</p>
            <p class="text-3xl font-extrabold text-slate-900 mt-1"><?= (int) ($summary['total'] ?? 0) ?></p>
        </article>
        <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">
            <p class="text-xs uppercase tracking-wide text-emerald-700">Succes</p>
            <p class="text-3xl font-extrabold text-emerald-800 mt-1"><?= (int) ($summary['success'] ?? 0) ?></p>
        </article>
        <article class="rounded-xl border border-red-200 bg-red-50 p-4">
            <p class="text-xs uppercase tracking-wide text-red-700">Echecs</p>
            <p class="text-3xl font-extrabold text-red-800 mt-1"><?= (int) ($summary['failure'] ?? 0) ?></p>
        </article>
    </div>
</section>

<section class="rounded-2xl bg-white shadow p-5 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="text-left text-xs uppercase tracking-wide text-slate-500">
            <tr>
                <th class="px-2 py-2">Date</th>
                <th class="px-2 py-2">Type</th>
                <?php if (($isPlatformAdmin ?? false) === true): ?>
                    <th class="px-2 py-2">Caserne</th>
                <?php endif; ?>
                <th class="px-2 py-2">Identifiant saisi</th>
                <th class="px-2 py-2">Utilisateur resolu</th>
                <th class="px-2 py-2">IP</th>
                <th class="px-2 py-2">Raison</th>
            </tr>
        </thead>
        <tbody>
            <?php if (($events ?? []) === []): ?>
                <tr><td colspan="<?= ($isPlatformAdmin ?? false) === true ? 7 : 6 ?>" class="px-2 py-3 text-slate-500">Aucun evenement.</td></tr>
            <?php else: ?>
                <?php foreach ($events as $event): ?>
                    <?php $isSuccess = (string) ($event['event_type'] ?? '') === 'success'; ?>
                    <tr class="border-t border-slate-100">
                        <td class="px-2 py-2 whitespace-nowrap"><?= htmlspecialchars((string) ($event['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-2 py-2">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold <?= $isSuccess ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' ?>">
                                <?= $isSuccess ? 'Succes' : 'Echec' ?>
                            </span>
                        </td>
                        <?php if (($isPlatformAdmin ?? false) === true): ?>
                            <?php
                            $caserneLabel = trim((string) ($event['caserne_nom'] ?? ''));
                            if ($caserneLabel === '') {
                                $caserneLabel = 'undefined';
                            }
                            ?>
                            <td class="px-2 py-2"><?= htmlspecialchars($caserneLabel, ENT_QUOTES, 'UTF-8') ?></td>
                        <?php endif; ?>
                        <td class="px-2 py-2"><?= htmlspecialchars((string) ($event['identifier'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-2 py-2">
                            <?php
                            $userLabel = trim((string) ($event['user_nom'] ?? ''));
                            if ($userLabel === '') {
                                $userLabel = '-';
                            }
                            ?>
                            <?= htmlspecialchars($userLabel, ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td class="px-2 py-2"><?= htmlspecialchars((string) ($event['ip_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td class="px-2 py-2"><?= htmlspecialchars((string) ($event['reason'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
