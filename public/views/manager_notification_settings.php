<?php

declare(strict_types=1);

$pageTitle = 'Parametres notifications - VerifApp';
$pageHeading = 'Parametres notifications';
$pageSubtitle = 'Canaux, groupes et personnes a notifier par evenement.';
$pageBackUrl = '/index.php?controller=manager_admin&action=menu';
$pageBackLabel = 'Retour administration';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if (in_array($success, ['saved', 'group_saved', 'group_deleted'], true)): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
        <?= $success === 'group_saved' ? 'Groupe de notification enregistre.' : ($success === 'group_deleted' ? 'Groupe de notification supprime.' : 'Parametres notifications enregistres.') ?>
    </section>
<?php elseif ($error !== ''): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
        Impossible d enregistrer les parametres notifications.
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-4 md:p-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="text-lg font-bold">Groupes de notification</h2>
            <p class="mt-1 text-sm text-slate-600">Les groupes sont independants des roles et servent uniquement au ciblage des alertes.</p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-4 xl:grid-cols-2">
        <form method="post" action="/index.php?controller=manager_notifications&action=group_save" class="rounded-xl border border-slate-200 p-4">
            <h3 class="font-semibold text-slate-900">Nouveau groupe</h3>
            <label class="mt-3 block">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Nom</span>
                <input type="text" name="group_name" required maxlength="120" placeholder="Ex: Responsables VSAV" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            </label>
            <div class="mt-3 max-h-52 space-y-2 overflow-y-auto">
                <?php foreach ($targetUsers as $targetUser): ?>
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                        <input type="checkbox" name="member_ids[]" value="<?= (int) ($targetUser['id'] ?? 0) ?>">
                        <span><?= htmlspecialchars(trim((string) ($targetUser['prenom'] ?? '') . ' ' . (string) ($targetUser['nom'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <button type="submit" class="mt-3 rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Creer le groupe</button>
        </form>

        <div class="space-y-3">
            <?php if ($notificationGroups === []): ?>
                <p class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Aucun groupe configure.</p>
            <?php endif; ?>
            <?php foreach ($notificationGroups as $notificationGroup): ?>
                <?php $groupMemberIds = array_map('intval', (array) ($notificationGroup['member_ids'] ?? [])); ?>
                <details class="rounded-xl border border-slate-200 p-4">
                    <summary class="cursor-pointer list-none font-semibold text-slate-900">
                        <?= htmlspecialchars((string) ($notificationGroup['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <span class="ml-2 text-xs font-normal text-slate-500"><?= (int) ($notificationGroup['member_count'] ?? 0) ?> membre(s)</span>
                    </summary>
                    <form method="post" action="/index.php?controller=manager_notifications&action=group_save" class="mt-3">
                        <input type="hidden" name="group_id" value="<?= (int) ($notificationGroup['id'] ?? 0) ?>">
                        <input type="text" name="group_name" required maxlength="120" value="<?= htmlspecialchars((string) ($notificationGroup['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                        <div class="mt-3 max-h-52 space-y-2 overflow-y-auto">
                            <?php foreach ($targetUsers as $targetUser): ?>
                                <?php $targetId = (int) ($targetUser['id'] ?? 0); ?>
                                <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                    <input type="checkbox" name="member_ids[]" value="<?= $targetId ?>" <?= in_array($targetId, $groupMemberIds, true) ? 'checked' : '' ?>>
                                    <span><?= htmlspecialchars(trim((string) ($targetUser['prenom'] ?? '') . ' ' . (string) ($targetUser['nom'] ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 flex gap-2">
                            <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Enregistrer</button>
                            <button type="submit" formaction="/index.php?controller=manager_notifications&action=group_delete" data-confirm="Supprimer ce groupe ?" class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white">Supprimer</button>
                        </div>
                    </form>
                </details>
            <?php endforeach; ?>
        </div>
    </div>
</section>

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
                    <span class="block text-sm font-semibold">Email</span>
                    <span class="block text-xs text-slate-500">Envoi aux destinataires cibles ayant une adresse valide. Le transport doit aussi etre actif dans Administration.</span>
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
                    'users' => [],
                ];
                $selectedRoles = is_array($eventSettings['roles'] ?? null) ? $eventSettings['roles'] : [];
                $selectedUsers = is_array($eventSettings['users'] ?? null) ? array_map('intval', $eventSettings['users']) : [];
                $selectedGroups = is_array($eventSettings['groups'] ?? null) ? array_map('intval', $eventSettings['groups']) : [];
                ?>
                <article class="rounded-xl border border-slate-200 p-3">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <p class="font-semibold text-slate-900"><?= htmlspecialchars((string) ($meta['label'] ?? $eventCode), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="text-xs text-slate-500 mt-1"><?= htmlspecialchars((string) ($meta['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <?php if ($eventCode === 'anomaly.created'): ?>
                                <p class="mt-1 text-xs font-medium text-slate-600">Cette regle sert aussi de valeur par defaut aux vehicules sans ciblage personnalise.</p>
                            <?php endif; ?>
                        </div>
                        <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                            <input type="hidden" name="event_enabled[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>]" value="0">
                            <input type="checkbox" name="event_enabled[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>]" value="1" <?= !empty($eventSettings['enabled']) ? 'checked' : '' ?>>
                            Actif
                        </label>
                    </div>
                    <div class="mt-4 grid grid-cols-1 gap-5 lg:grid-cols-3">
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Roles</h4>
                            <div class="mt-2 grid grid-cols-1 gap-2">
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
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Groupes de notification</h4>
                            <div class="mt-2 grid grid-cols-1 gap-2">
                                <?php if ($notificationGroups === []): ?>
                                    <p class="text-sm text-slate-500">Aucun groupe configure.</p>
                                <?php endif; ?>
                                <?php foreach ($notificationGroups as $notificationGroup): ?>
                                    <?php $groupId = (int) ($notificationGroup['id'] ?? 0); ?>
                                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                        <input type="checkbox" name="event_groups[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>][]" value="<?= $groupId ?>" <?= in_array($groupId, $selectedGroups, true) ? 'checked' : '' ?>>
                                        <span>
                                            <span class="block font-semibold"><?= htmlspecialchars((string) ($notificationGroup['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            <span class="block text-xs text-slate-500"><?= (int) ($notificationGroup['member_count'] ?? 0) ?> membre(s)</span>
                                        </span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-800">Personnes specifiques</h4>
                            <div class="mt-2 max-h-64 space-y-2 overflow-y-auto pr-1">
                                <?php if ($targetUsers === []): ?>
                                    <p class="text-sm text-slate-500">Aucun utilisateur actif dans cette caserne.</p>
                                <?php else: ?>
                                    <?php foreach ($targetUsers as $targetUser): ?>
                                        <?php
                                        $targetUserId = (int) ($targetUser['id'] ?? 0);
                                        $targetUserName = trim(
                                            (string) ($targetUser['prenom'] ?? '') . ' ' . (string) ($targetUser['nom'] ?? '')
                                        );
                                        if ($targetUserName === '') {
                                            $targetUserName = (string) ($targetUser['email'] ?? ('Utilisateur #' . $targetUserId));
                                        }
                                        ?>
                                        <label class="flex items-start gap-2 rounded-lg border border-slate-200 px-3 py-2 text-sm">
                                            <input
                                                type="checkbox"
                                                name="event_users[<?= htmlspecialchars($eventKey, ENT_QUOTES, 'UTF-8') ?>][]"
                                                value="<?= $targetUserId ?>"
                                                <?= in_array($targetUserId, $selectedUsers, true) ? 'checked' : '' ?>
                                            >
                                            <span>
                                                <span class="block font-semibold text-slate-800"><?= htmlspecialchars($targetUserName, ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="block text-xs text-slate-500"><?= htmlspecialchars((string) ($targetUser['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
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
