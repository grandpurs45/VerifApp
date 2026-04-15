<?php

declare(strict_types=1);

$pageTitle = 'Notifications - VerifApp';
$pageHeading = 'Notifications';
$pageSubtitle = 'Historique et suivi des alertes recues.';
$pageBackUrl = '/index.php?controller=manager&action=dashboard';
$pageBackLabel = 'Retour dashboard';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if ($success === 'read' || $success === 'read_all'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Notification(s) marquee(s) comme lue(s).
    </section>
<?php elseif ($error !== ''): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Operation impossible sur les notifications.
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <p class="text-sm text-slate-600">Notifications non lues: <strong><?= (int) $unreadCount ?></strong></p>
        </div>
        <form method="post" action="/index.php?controller=manager_notifications&action=mark_all_read">
            <button type="submit" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">
                Tout marquer lu
            </button>
        </form>
    </div>

    <form method="get" action="/index.php" class="mt-3 grid grid-cols-1 md:grid-cols-4 gap-2">
        <input type="hidden" name="controller" value="manager_notifications">
        <input type="hidden" name="action" value="index">
        <select name="lu" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <option value="all" <?= ($filters['lu'] ?? 'all') === 'all' ? 'selected' : '' ?>>Tous</option>
            <option value="unread" <?= ($filters['lu'] ?? '') === 'unread' ? 'selected' : '' ?>>Non lues</option>
            <option value="read" <?= ($filters['lu'] ?? '') === 'read' ? 'selected' : '' ?>>Lues</option>
        </select>
        <select name="event_code" class="rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <option value="">Tous les evenements</option>
            <?php foreach ($eventCatalog as $eventCode => $meta): ?>
                <option value="<?= htmlspecialchars($eventCode, ENT_QUOTES, 'UTF-8') ?>" <?= ($filters['event_code'] ?? '') === $eventCode ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) ($meta['label'] ?? $eventCode), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Filtrer</button>
        <a href="/index.php?controller=manager_notifications&action=index" class="rounded-xl border border-slate-300 px-4 py-2 text-center text-sm font-semibold text-slate-700">Reset</a>
    </form>
</section>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <?php if ($notificationsAvailable !== true): ?>
        <p class="text-sm text-slate-500">Module notifications non disponible (migration manquante).</p>
    <?php elseif ($history === []): ?>
        <p class="text-sm text-slate-500">Aucune notification pour le moment.</p>
    <?php else: ?>
        <div class="space-y-3">
            <?php foreach ($history as $item): ?>
                <?php $isRead = (int) ($item['lu'] ?? 0) === 1; ?>
                <article class="rounded-xl border p-4 <?= $isRead ? 'border-slate-200 bg-slate-50' : 'border-sky-200 bg-sky-50' ?>">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold text-slate-900"><?= htmlspecialchars((string) ($item['titre'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        <span class="text-xs rounded-full px-2 py-1 <?= $isRead ? 'bg-slate-200 text-slate-700' : 'bg-sky-200 text-sky-800' ?>">
                            <?= $isRead ? 'Lue' : 'Non lue' ?>
                        </span>
                    </div>
                    <p class="text-sm text-slate-700 mt-1"><?= htmlspecialchars((string) ($item['message'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                    <p class="text-xs text-slate-500 mt-2">
                        <?= htmlspecialchars((string) ($item['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <?php if ((string) ($item['acteur_nom'] ?? '') !== ''): ?>
                            • par <?= htmlspecialchars((string) ($item['acteur_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <?php if ((string) ($item['lien'] ?? '') !== ''): ?>
                            <a href="<?= htmlspecialchars((string) $item['lien'], ENT_QUOTES, 'UTF-8') ?>" class="rounded-lg bg-slate-900 text-white px-3 py-2 text-xs font-semibold">Ouvrir</a>
                        <?php endif; ?>
                        <?php if (!$isRead): ?>
                            <form method="post" action="/index.php?controller=manager_notifications&action=mark_read">
                                <input type="hidden" name="notification_id" value="<?= (int) ($item['id'] ?? 0) ?>">
                                <button type="submit" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">
                                    Marquer lu
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
