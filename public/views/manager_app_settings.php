<?php

declare(strict_types=1);

$pageTitle = 'Parametres application - VerifApp';
$pageHeading = 'Parametres application';
$pageSubtitle = 'Reglages globaux du backoffice.';
$pageBackUrl = '/index.php?controller=manager_admin&action=menu';
$pageBackLabel = 'Retour administration';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<section class="rounded-2xl bg-white shadow p-5">
    <h2 class="text-lg font-bold">Securite session</h2>
    <p class="text-sm text-slate-600 mt-2">
        Expiration de session gestionnaire (minutes): <strong><?= htmlspecialchars($sessionTimeout, ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
    <p class="text-xs text-slate-500 mt-2">
        Parametre technique actuel: <code>MANAGER_SESSION_TTL_MINUTES</code> dans le fichier d environnement.
    </p>
</section>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
