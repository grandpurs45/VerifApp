<?php

declare(strict_types=1);

$pageTitle = 'Parametres notifications - VerifApp';
$pageHeading = 'Parametres notifications';
$pageSubtitle = 'Canaux, ciblage des roles et regles par evenement.';
$pageBackUrl = '/index.php?controller=manager_admin&action=menu';
$pageBackLabel = 'Retour administration';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if ($success === 'saved'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
        Parametres notifications enregistres.
    </section>
<?php elseif ($error !== ''): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        Impossible d enregistrer les parametres notifications.
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <h2 class="text-lg font-bold">Canaux</h2>
    <form method="post" action="/index.php?controller=manager_notifications&action=settings_save" class="mt-3 space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3">
                <input type="hidden" name="channel_in_app_enabled" value="0">
                <input type="checkbox" name="channel_in_app_enabled" value="1" <?= !empty($channels['in_app_enabled']) ? 'checked' : '' ?>>
                <span>
                    <span class="block text-sm font-semibold">Cloche in-app</span>
                    <span class="block text-xs text-slate-500">Canal principal dans l interface backoffice.</span>
                </span>
            </label>
            <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3">
                <input type="hidden" name="channel_email_enabled" value="0">
                <input type="checkbox" name="channel_email_enabled" value="1" <?= !empty($channels['email_enabled']) ? 'checked' : '' ?>>
                <span>
                    <span class="block text-sm font-semibold">Email (preparation)</span>
                    <span class="block text-xs text-slate-500">Canal email (serveur SMTP requis).</span>
                </span>
            </label>
        </div>

        <h3 class="text-lg font-bold">Qui notifier par evenement</h3>
        <div class="space-y-3">
            <?php foreach ($eventCatalog as $eventCode => $meta): ?>
                <?php
                $eventKey = str_replace('.', '_', $eventCode);
                $eventSettings = $notificationSettings[$eventCode] ?? [
                    'enabled' => true,
                    'roles' => [],
                ];
                $selectedRoles = is_array($eventSettings['roles'] ?? null) ? $eventSettings['roles'] : [];
                ?>
                <article class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900"><?= htmlspecialchars((string) ($meta['label'] ?? $eventCode), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string) ($meta['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="hidden" name="event_enabled[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>]" value="0">
                            <input type="checkbox" name="event_enabled[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>]" value="1" <?= !empty($eventSettings['enabled']) ? 'checked' : '' ?>>
                            Actif
                        </label>
                    </div>
                    <div class="mt-3 grid grid-cols-1 md:grid-cols-2 gap-2">
                        <?php foreach ($roles as $role): ?>
                            <?php
                            $roleCode = (string) ($role['code'] ?? '');
                            if ($roleCode === '') {
                                continue;
                            }
                            ?>
                            <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                <input
                                    type="checkbox"
                                    name="event_roles[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>][]"
                                    value="<?= htmlspecialchars($roleCode, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= in_array($roleCode, $selectedRoles, true) ? 'checked' : '' ?>
                                >
                                <?= htmlspecialchars((string) ($role['nom'] ?? $roleCode), ENT_QUOTES, 'UTF-8') ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>

        <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">
            Enregistrer parametres notifications
        </button>
    </form>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
