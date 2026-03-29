<?php

declare(strict_types=1);

$pageTitle = 'Parametres application - VerifApp';
$pageHeading = 'Parametres application';
$pageSubtitle = 'Reglages globaux du backoffice.';
$pageBackUrl = '/index.php?controller=manager_admin&action=menu';
$pageBackLabel = 'Retour administration';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if ($success === 'token_regenerated'): ?>
    <?php $target = isset($_GET['target']) ? (string) $_GET['target'] : ''; ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        <?= $target === 'pharmacy' ? 'Lien/QR pharmacie regeneres.' : 'Lien/QR verification terrain regeneres.' ?>
    </section>
<?php elseif ($error === 'env_write_failed'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm space-y-2">
        <p>Impossible d ecrire le token dans le fichier <code>.env</code> (permissions serveur).</p>
        <p class="text-xs">Correctif Docker compose (si volume en lecture seule): retirer <code>:ro</code> sur le montage <code>.env.docker:/var/www/html/.env</code> puis redemarrer.</p>
        <p class="text-xs">Correctif permissions Linux (exemple): <code>sudo chgrp www-data /var/www/html/.env && sudo chmod 664 /var/www/html/.env</code></p>
    </section>
<?php elseif ($error === 'invalid_target'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Cible de regeneration invalide.
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-5">
    <h2 class="text-lg font-bold">Securite session</h2>
    <p class="text-sm text-slate-600 mt-2">
        Expiration de session gestionnaire (minutes): <strong><?= htmlspecialchars($sessionTimeout, ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
    <p class="text-xs text-slate-500 mt-2">
        Parametre technique actuel: <code>MANAGER_SESSION_TTL_MINUTES</code> dans le fichier d environnement.
    </p>
</section>

<section class="rounded-2xl bg-white shadow p-5">
    <h2 class="text-lg font-bold">Acces invites (QR)</h2>
    <p class="text-sm text-slate-600 mt-2">
        Liens et QR codes generes depuis l administration pour partage terrain.
    </p>

    <div class="mt-4 grid grid-cols-1 xl:grid-cols-2 gap-4">
        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-sm font-semibold text-slate-800">Verification terrain</p>
            <p class="text-xs text-slate-500 mt-1 break-all" id="fieldGuestUrl"><?= htmlspecialchars($fieldGuestUrl, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($fieldGuestUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rounded-lg bg-slate-900 text-white px-3 py-2 text-xs font-semibold">Ouvrir lien</a>
                <button type="button" data-copy-target="fieldGuestUrl" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Copier lien</button>
                <form method="post" action="/index.php?controller=manager_admin&action=regenerate_qr_token">
                    <input type="hidden" name="target" value="field">
                    <button
                        type="submit"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
                        onclick="return window.confirm('Regenerer ce lien QR ? Les anciens liens et anciens QR ne fonctionneront plus.');"
                    >
                        <?= $fieldToken === '' ? 'Generer lien + QR' : 'Regenerer lien + QR' ?>
                    </button>
                </form>
            </div>
            <div class="mt-4">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= rawurlencode($fieldGuestUrl) ?>"
                    alt="QR verification terrain"
                    class="h-44 w-44 rounded-lg border border-slate-200 bg-white p-2"
                >
            </div>
        </article>

        <article class="rounded-xl border border-slate-200 p-4">
            <p class="text-sm font-semibold text-slate-800">Sortie pharmacie</p>
            <p class="text-xs text-slate-500 mt-1 break-all" id="pharmacyGuestUrl"><?= htmlspecialchars($pharmacyGuestUrl, ENT_QUOTES, 'UTF-8') ?></p>
            <div class="mt-3 flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($pharmacyGuestUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rounded-lg bg-slate-900 text-white px-3 py-2 text-xs font-semibold">Ouvrir lien</a>
                <button type="button" data-copy-target="pharmacyGuestUrl" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Copier lien</button>
                <form method="post" action="/index.php?controller=manager_admin&action=regenerate_qr_token">
                    <input type="hidden" name="target" value="pharmacy">
                    <button
                        type="submit"
                        class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
                        onclick="return window.confirm('Regenerer ce lien QR ? Les anciens liens et anciens QR ne fonctionneront plus.');"
                    >
                        <?= $pharmacyToken === '' ? 'Generer lien + QR' : 'Regenerer lien + QR' ?>
                    </button>
                </form>
            </div>
            <div class="mt-4">
                <img
                    src="https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=<?= rawurlencode($pharmacyGuestUrl) ?>"
                    alt="QR sortie pharmacie"
                    class="h-44 w-44 rounded-lg border border-slate-200 bg-white p-2"
                >
            </div>
        </article>
    </div>
</section>

<script>
    (function () {
        const copyButtons = document.querySelectorAll('button[data-copy-target]');
        copyButtons.forEach((button) => {
            button.addEventListener('click', async () => {
                const targetId = button.getAttribute('data-copy-target');
                const target = targetId ? document.getElementById(targetId) : null;
                const value = target ? (target.textContent || '').trim() : '';
                if (!value) return;

                try {
                    await navigator.clipboard.writeText(value);
                    const previous = button.textContent;
                    button.textContent = 'Copie';
                    setTimeout(() => {
                        button.textContent = previous;
                    }, 1200);
                } catch (error) {
                    window.prompt('Copiez ce lien:', value);
                }
            });
        });
    })();
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
