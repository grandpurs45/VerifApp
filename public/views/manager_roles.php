<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Roles et acces - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-7xl mx-auto p-4 md:p-8 space-y-6">
        <header class="rounded-3xl bg-gradient-to-r from-slate-900 to-slate-700 text-white p-5 md:p-6 shadow">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <a href="/index.php?controller=manager_admin&action=menu" class="text-xs text-slate-300 hover:text-white"><- Retour administration</a>
                    <h1 class="text-3xl font-extrabold mt-2">Roles et acces</h1>
                    <p class="text-slate-200 mt-1">Creer des roles et definir les fonctionnalites autorisees.</p>
                </div>
                <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                Modification enregistree.
            </section>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                Action impossible. Verifie les valeurs et permissions.
            </section>
        <?php endif; ?>

        <section class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <article class="rounded-2xl bg-white shadow p-4 lg:col-span-1">
                <h2 class="text-lg font-bold">Nouveau role</h2>
                <form method="post" action="/index.php?controller=manager_roles&action=role_save" class="mt-3 space-y-2">
                    <input type="text" name="nom" required placeholder="Nom role (ex: Chef de garde)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <input type="text" name="code" placeholder="Code optionnel (ex: chef_garde)" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                    <button type="submit" class="w-full rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Creer role</button>
                </form>
            </article>

            <article class="rounded-2xl bg-white shadow p-4 lg:col-span-2">
                <h2 class="text-lg font-bold">Roles existants</h2>
                <div class="mt-3 overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead>
                            <tr class="text-left text-slate-500 border-b border-slate-200">
                                <th class="py-2 pr-3">Role</th>
                                <th class="py-2 pr-3">Code</th>
                                <th class="py-2 pr-3">Permissions</th>
                                <th class="py-2 pr-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): ?>
                                <tr class="border-b border-slate-100">
                                    <td class="py-2 pr-3 font-semibold"><?= htmlspecialchars((string) $role['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="py-2 pr-3 text-slate-600"><?= htmlspecialchars((string) $role['code'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="py-2 pr-3"><?= (int) ($role['total_permissions'] ?? 0) ?></td>
                                    <td class="py-2 pr-3">
                                        <div class="flex justify-end gap-2">
                                            <a href="/index.php?controller=manager_roles&action=index&role_id=<?= (int) $role['id'] ?>" class="rounded-lg bg-slate-800 px-3 py-1.5 text-xs font-semibold text-white">Configurer</a>
                                            <?php if ((int) ($role['is_system'] ?? 0) !== 1 && (string) ($role['code'] ?? '') !== 'admin'): ?>
                                                <form method="post" action="/index.php?controller=manager_roles&action=role_delete" onsubmit="return confirm('Supprimer ce role ?');">
                                                    <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>">
                                                    <button type="submit" class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">Supprimer</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-600">Systeme</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </article>
        </section>

        <section class="rounded-2xl bg-white shadow p-4 md:p-5">
            <h2 class="text-lg font-bold">Permissions du role</h2>
            <?php if ($selectedRole === null): ?>
                <p class="text-sm text-slate-500 mt-2">Aucun role selectionne.</p>
            <?php else: ?>
                <p class="text-sm text-slate-600 mt-2">
                    Role: <strong><?= htmlspecialchars((string) $selectedRole['nom'], ENT_QUOTES, 'UTF-8') ?></strong>
                    (<?= htmlspecialchars((string) $selectedRole['code'], ENT_QUOTES, 'UTF-8') ?>)
                </p>
                <form method="post" action="/index.php?controller=manager_roles&action=permissions_save" class="mt-3 space-y-2">
                    <input type="hidden" name="role_id" value="<?= (int) $selectedRole['id'] ?>">
                    <?php foreach ($catalog as $permissionCode => $permissionLabel): ?>
                        <label class="flex items-start gap-2 rounded-xl border border-slate-200 p-3">
                            <input type="checkbox" name="permissions[]" value="<?= htmlspecialchars($permissionCode, ENT_QUOTES, 'UTF-8') ?>" <?= in_array($permissionCode, $selectedPermissions, true) ? 'checked' : '' ?>>
                            <span>
                                <span class="block text-sm font-semibold"><?= htmlspecialchars($permissionLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="block text-xs text-slate-500"><?= htmlspecialchars($permissionCode, ENT_QUOTES, 'UTF-8') ?></span>
                            </span>
                        </label>
                    <?php endforeach; ?>
                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Enregistrer permissions</button>
                </form>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>
