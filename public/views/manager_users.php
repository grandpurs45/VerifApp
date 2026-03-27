<?php

declare(strict_types=1);

$appVersion = \App\Core\AppVersion::current();
$roleOptions = [];
foreach ($roles as $role) {
    $code = (string) ($role['code'] ?? '');
    $name = (string) ($role['nom'] ?? $code);
    if ($code !== '') {
        $roleOptions[$code] = $name;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-7xl mx-auto p-4 md:p-8 space-y-6">
        <header class="rounded-3xl bg-gradient-to-r from-slate-900 to-slate-700 text-white p-5 md:p-6 shadow">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <a href="/index.php?controller=manager_admin&action=menu" class="text-xs text-slate-300 hover:text-white"><- Retour administration</a>
                    <h1 class="text-3xl font-extrabold mt-2">Utilisateurs</h1>
                    <p class="text-slate-200 mt-1">CRUD comptes gestionnaires et verification des acces.</p>
                </div>
                <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold">v<?= htmlspecialchars($appVersion, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                Operation effectuee avec succes.
            </section>
        <?php endif; ?>
        <?php if (isset($_GET['error'])): ?>
            <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                Operation refusee. Verifie les donnees (ou droits proteges admin).
            </section>
        <?php endif; ?>

        <section class="rounded-2xl bg-white shadow p-4 md:p-5">
            <h2 class="text-xl font-bold">Creer un utilisateur</h2>
            <form method="post" action="/index.php?controller=manager_users&action=save" class="mt-3 grid grid-cols-1 md:grid-cols-12 gap-2">
                <input type="hidden" name="id" value="0">
                <input type="text" name="nom" required placeholder="Nom complet" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
                <input type="email" name="email" required placeholder="email@exemple.fr" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
                <select name="role" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                    <?php foreach ($roleOptions as $code => $label): ?>
                        <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="password" name="password" required placeholder="Mot de passe initial (min 8)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                    <option value="1">Actif</option>
                    <option value="0">Inactif</option>
                </select>
                <button type="submit" class="md:col-span-12 rounded-xl bg-slate-900 px-4 py-3 text-sm font-semibold text-white">Creer utilisateur</button>
            </form>
        </section>

        <section class="rounded-2xl bg-white shadow p-4 md:p-5">
            <h2 class="text-xl font-bold mb-3">Comptes existants</h2>
            <div class="overflow-x-auto">
                <table class="min-w-[1100px] w-full text-sm">
                    <thead>
                        <tr class="text-left text-slate-500 border-b border-slate-200">
                            <th class="py-2 pr-3">Nom</th>
                            <th class="py-2 pr-3">Email</th>
                            <th class="py-2 pr-3">Role</th>
                            <th class="py-2 pr-3">Etat</th>
                            <th class="py-2 pr-3">Reset MDP</th>
                            <th class="py-2 pr-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $isCurrent = isset($managerUser['id']) && (int) $managerUser['id'] === (int) $user['id'];
                            $isAdmin = (string) ($user['role'] ?? '') === 'admin';
                            ?>
                            <tr class="border-b border-slate-100">
                                <td class="py-2 pr-3" colspan="6">
                                    <form method="post" action="/index.php?controller=manager_users&action=save" class="grid grid-cols-12 gap-2 items-center">
                                        <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
                                        <input type="text" name="nom" value="<?= htmlspecialchars((string) $user['nom'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-2">
                                        <input type="email" name="email" value="<?= htmlspecialchars((string) $user['email'], ENT_QUOTES, 'UTF-8') ?>" required class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-3">
                                        <select name="role" class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-2">
                                            <?php foreach ($roleOptions as $code => $label): ?>
                                                <option value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>" <?= (string) $user['role'] === $code ? 'selected' : '' ?>><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-1" <?= $isAdmin ? 'disabled' : '' ?>>
                                            <option value="1" <?= (int) ($user['actif'] ?? 0) === 1 ? 'selected' : '' ?>>Actif</option>
                                            <option value="0" <?= (int) ($user['actif'] ?? 0) !== 1 ? 'selected' : '' ?>>Inactif</option>
                                        </select>
                                        <?php if ($isAdmin): ?><input type="hidden" name="actif" value="1"><?php endif; ?>
                                        <input type="password" name="password" placeholder="Nouveau mot de passe" class="rounded-xl border border-slate-300 px-3 py-2 text-sm col-span-2">
                                        <div class="col-span-2 flex justify-end gap-2">
                                            <button type="submit" class="rounded-xl bg-slate-800 px-3 py-2 text-xs font-semibold text-white">Enregistrer</button>
                                            <?php if (!$isAdmin && !$isCurrent): ?>
                                                <button formaction="/index.php?controller=manager_users&action=delete" onclick="return confirm('Desactiver cet utilisateur ?');" class="rounded-xl bg-red-600 px-3 py-2 text-xs font-semibold text-white">Desactiver</button>
                                            <?php else: ?>
                                                <span class="rounded-xl bg-slate-200 px-3 py-2 text-xs font-semibold text-slate-600"><?= $isCurrent ? 'Compte courant' : 'Protege' ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
