<?php

declare(strict_types=1);

$pageTitle = 'Audit securite - VerifApp';
$pageHeading = 'Audit securite';
$pageSubtitle = ($journal ?? 'connections') === 'qr'
    ? 'Journal des ouvertures de QR Codes.'
    : 'Journal des connexions gestionnaire.';
$pageBackUrl = '/index.php?controller=manager_admin&action=menu';
$pageBackLabel = 'Retour administration';
$isQrJournal = ($journal ?? 'connections') === 'qr';
$escape = static fn (mixed $value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<nav class="flex gap-1 rounded-lg border border-slate-200 bg-white p-1" aria-label="Journaux d audit">
    <a href="/index.php?controller=manager_admin&action=security_audit&journal=connections" class="flex-1 rounded-md px-4 py-2 text-center text-sm font-semibold <?= !$isQrJournal ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
        Connexions
    </a>
    <a href="/index.php?controller=manager_admin&action=security_audit&journal=qr" class="flex-1 rounded-md px-4 py-2 text-center text-sm font-semibold <?= $isQrJournal ? 'bg-slate-900 text-white' : 'text-slate-600 hover:bg-slate-100' ?>">
        Ouvertures QR
    </a>
</nav>

<section class="rounded-lg border border-slate-200 bg-white p-5">
    <form method="get" action="/index.php" class="grid grid-cols-1 gap-3 md:grid-cols-6">
        <input type="hidden" name="controller" value="manager_admin">
        <input type="hidden" name="action" value="security_audit">
        <input type="hidden" name="journal" value="<?= $isQrJournal ? 'qr' : 'connections' ?>">
        <?php if (($isPlatformAdmin ?? false) === true): ?>
            <label class="text-xs font-semibold uppercase text-slate-500">
                Caserne
                <select name="caserne_id" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal normal-case text-slate-900">
                    <option value="">Toutes</option>
                    <?php foreach (($casernes ?? []) as $caserne): ?>
                        <?php $cid = (int) ($caserne['id'] ?? 0); ?>
                        <option value="<?= $cid ?>" <?= ($selectedCaserneId ?? null) === $cid ? 'selected' : '' ?>><?= $escape($caserne['nom'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
        <?php endif; ?>
        <label class="text-xs font-semibold uppercase text-slate-500">
            Du
            <input type="date" name="date_from" value="<?= $escape($filters['date_from'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
        </label>
        <label class="text-xs font-semibold uppercase text-slate-500">
            Au
            <input type="date" name="date_to" value="<?= $escape($filters['date_to'] ?? '') ?>" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal text-slate-900">
        </label>
        <?php if ($isQrJournal): ?>
            <label class="text-xs font-semibold uppercase text-slate-500">
                Module
                <select name="module" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal normal-case text-slate-900">
                    <option value="">Tous</option>
                    <?php foreach (['verifications' => 'Verifications', 'pharmacy' => 'Sortie pharmacie', 'inventory' => 'Inventaire'] as $code => $label): ?>
                        <option value="<?= $code ?>" <?= ($filters['module'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="text-xs font-semibold uppercase text-slate-500">
                Identite
                <input type="text" name="identity" value="<?= $escape($filters['identity'] ?? '') ?>" placeholder="Nom saisi" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal normal-case text-slate-900">
            </label>
        <?php else: ?>
            <label class="text-xs font-semibold uppercase text-slate-500">
                Type
                <select name="event_type" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal normal-case text-slate-900">
                    <option value="">Tous</option>
                    <option value="success" <?= ($filters['event_type'] ?? '') === 'success' ? 'selected' : '' ?>>Succes</option>
                    <option value="failure" <?= ($filters['event_type'] ?? '') === 'failure' ? 'selected' : '' ?>>Echec</option>
                </select>
            </label>
            <label class="text-xs font-semibold uppercase text-slate-500">
                Identifiant
                <input type="text" name="identifier" value="<?= $escape($filters['identifier'] ?? '') ?>" placeholder="Email ou nom" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal normal-case text-slate-900">
            </label>
        <?php endif; ?>
        <label class="text-xs font-semibold uppercase text-slate-500">
            IP
            <input type="text" name="ip_address" value="<?= $escape($filters['ip_address'] ?? '') ?>" placeholder="Adresse IP" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-normal normal-case text-slate-900">
        </label>
        <div class="flex flex-wrap items-end gap-2 md:col-span-6">
            <button type="submit" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Filtrer</button>
            <a href="/index.php?controller=manager_admin&action=security_audit&journal=<?= $isQrJournal ? 'qr' : 'connections' ?>" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Reinitialiser</a>
            <?php
            $exportQuery = array_filter([
                'controller' => 'manager_admin',
                'action' => 'security_audit_export_csv',
                'journal' => $isQrJournal ? 'qr' : 'connections',
                'caserne_id' => ($isPlatformAdmin ?? false) ? ($selectedCaserneId ?? null) : null,
                'date_from' => $filters['date_from'] ?? '',
                'date_to' => $filters['date_to'] ?? '',
                'event_type' => $filters['event_type'] ?? '',
                'identifier' => $filters['identifier'] ?? '',
                'identity' => $filters['identity'] ?? '',
                'module' => $filters['module'] ?? '',
                'ip_address' => $filters['ip_address'] ?? '',
            ], static fn (mixed $value): bool => $value !== null && $value !== '');
            ?>
            <a href="/index.php?<?= $escape(http_build_query($exportQuery)) ?>" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Export CSV</a>
        </div>
    </form>
</section>

<?php if (!$isQrJournal): ?>
    <section class="grid grid-cols-1 gap-3 md:grid-cols-3">
        <article class="rounded-lg border border-slate-200 bg-white p-4"><p class="text-xs uppercase text-slate-500">Total</p><p class="mt-1 text-3xl font-extrabold"><?= (int) ($summary['total'] ?? 0) ?></p></article>
        <article class="rounded-lg border border-emerald-200 bg-emerald-50 p-4"><p class="text-xs uppercase text-emerald-700">Succes</p><p class="mt-1 text-3xl font-extrabold text-emerald-800"><?= (int) ($summary['success'] ?? 0) ?></p></article>
        <article class="rounded-lg border border-red-200 bg-red-50 p-4"><p class="text-xs uppercase text-red-700">Echecs</p><p class="mt-1 text-3xl font-extrabold text-red-800"><?= (int) ($summary['failure'] ?? 0) ?></p></article>
    </section>
    <section class="overflow-x-auto rounded-lg border border-slate-200 bg-white p-5">
        <h2 class="mb-4 text-lg font-bold">Connexions gestionnaire</h2>
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase text-slate-500"><tr><th class="px-2 py-2">Date</th><th class="px-2 py-2">Type</th><?php if (($isPlatformAdmin ?? false) === true): ?><th class="px-2 py-2">Caserne</th><?php endif; ?><th class="px-2 py-2">Identifiant saisi</th><th class="px-2 py-2">Utilisateur</th><th class="px-2 py-2">IP</th><th class="px-2 py-2">Raison</th></tr></thead>
            <tbody>
            <?php if (($events ?? []) === []): ?><tr><td colspan="<?= ($isPlatformAdmin ?? false) ? 7 : 6 ?>" class="px-2 py-4 text-slate-500">Aucun evenement.</td></tr><?php endif; ?>
            <?php foreach (($events ?? []) as $event): ?>
                <?php $success = ($event['event_type'] ?? '') === 'success'; ?>
                <tr class="border-t border-slate-100"><td class="whitespace-nowrap px-2 py-2"><?= $escape($event['created_at'] ?? '') ?></td><td class="px-2 py-2"><span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold <?= $success ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' ?>"><?= $success ? 'Succes' : 'Echec' ?></span></td><?php if (($isPlatformAdmin ?? false) === true): ?><td class="px-2 py-2"><?= $escape($event['caserne_nom'] ?? 'Non definie') ?></td><?php endif; ?><td class="px-2 py-2"><?= $escape($event['identifier'] ?? '') ?></td><td class="px-2 py-2"><?= $escape($event['user_nom'] ?? '-') ?></td><td class="px-2 py-2"><?= $escape($event['ip_address'] ?? '') ?></td><td class="px-2 py-2"><?= $escape($event['reason'] ?? '') ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php else: ?>
    <section class="overflow-x-auto rounded-lg border border-slate-200 bg-white p-5">
        <div class="mb-4"><h2 class="text-lg font-bold">Ouvertures des QR Codes</h2><p class="mt-1 text-sm text-slate-600">L IP client et celle du reverse proxy sont distinguees pour les nouvelles traces.</p></div>
        <?php if (($qrAccessAvailable ?? false) !== true || ($qrAccessError ?? '') !== ''): ?><p class="mb-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm font-semibold text-red-800"><?= $escape(($qrAccessError ?? '') !== '' ? $qrAccessError : 'Journal QR indisponible. Applique la migration 042.') ?></p><?php endif; ?>
        <table class="min-w-full text-sm">
            <thead class="text-left text-xs uppercase text-slate-500"><tr><th class="px-2 py-2">Date</th><?php if (($isPlatformAdmin ?? false) === true): ?><th class="px-2 py-2">Caserne</th><?php endif; ?><th class="px-2 py-2">Module</th><th class="px-2 py-2">Engin</th><th class="px-2 py-2">Identite</th><th class="px-2 py-2">IP client</th><th class="px-2 py-2">Proxy</th><th class="px-2 py-2">Navigateur</th></tr></thead>
            <tbody>
            <?php if (($qrAccesses ?? []) === []): ?><tr><td colspan="<?= ($isPlatformAdmin ?? false) ? 8 : 7 ?>" class="px-2 py-4 text-slate-500">Aucune ouverture tracee.</td></tr><?php endif; ?>
            <?php $moduleLabels = ['verifications' => 'Verifications', 'pharmacy' => 'Sortie pharmacie', 'inventory' => 'Inventaire']; ?>
            <?php foreach (($qrAccesses ?? []) as $access): ?>
                <?php $identity = trim((string) ($access['identite'] ?? '')); ?>
                <tr class="border-t border-slate-100"><td class="whitespace-nowrap px-2 py-2"><?= $escape($access['opened_at'] ?? '') ?></td><?php if (($isPlatformAdmin ?? false) === true): ?><td class="px-2 py-2 font-semibold"><?= $escape($access['caserne_nom'] ?? '-') ?></td><?php endif; ?><td class="px-2 py-2"><?= $escape($moduleLabels[$access['module'] ?? ''] ?? ($access['module'] ?? '')) ?></td><td class="px-2 py-2"><?= $escape($access['vehicule_nom'] ?? '-') ?></td><td class="px-2 py-2 font-semibold"><?= $escape($identity !== '' ? $identity : 'Anonyme') ?></td><td class="px-2 py-2"><?= $escape($access['ip_address'] ?? '') ?></td><td class="px-2 py-2 text-slate-500"><?= $escape($access['proxy_ip_address'] ?? '-') ?></td><td class="max-w-xs truncate px-2 py-2 text-xs text-slate-500" title="<?= $escape($access['user_agent'] ?? '') ?>"><?= $escape($access['user_agent'] ?? '') ?></td></tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </section>
<?php endif; ?>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
