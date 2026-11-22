<?php
declare(strict_types=1);
$pageTitle='Lien de suivi - VerifApp'; $pageHeading='Lien public de suivi';
$pageSubtitle=(string)$caserne['nom'];
$pageBackUrl='/index.php?controller=verifications&action=yearly';$pageBackLabel='Retour aux statistiques';
require __DIR__.'/partials/backoffice_shell_top.php';
?>
<section class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
    Toute personne disposant de ce lien peut consulter les statistiques de cette caserne sans compte. Partage-le uniquement avec les destinataires souhaites.
    La page est en lecture seule, sans noms d agents, commentaires ni details individuels d anomalies. Regenerer ou desactiver le lien retire l acces a l ancien lien.
</section>
<?php if ($error !== ''): ?><p role="alert" class="rounded-lg bg-red-50 p-3 text-red-800">Impossible de modifier le lien. Reessaie ou verifie la configuration.</p><?php endif; ?>
<?php if ($success !== ''): ?><p role="status" class="rounded-lg bg-emerald-50 p-3 text-emerald-800">Le lien de suivi a ete mis a jour.</p><?php endif; ?>
<section class="bg-white p-4 sm:p-6">
    <h2 class="text-lg font-bold">Acces partage</h2>
    <?php if ($shareUrl === ''): ?>
        <p class="mt-2 text-sm text-slate-500">Le suivi public est desactive pour cette caserne.</p>
        <form method="post" action="/index.php?controller=statistics_share&action=save" class="mt-4">
            <button name="share_action" value="generate" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Generer le lien</button>
        </form>
    <?php else: ?>
        <label class="mt-3 block text-sm font-semibold text-slate-600" for="statistics-share-url">Lien de la caserne</label>
        <textarea id="statistics-share-url" readonly rows="3" class="mt-1 w-full resize-none rounded-lg border border-slate-300 p-3 text-sm"><?= htmlspecialchars($shareUrl,ENT_QUOTES,'UTF-8') ?></textarea>
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="button" data-copy-statistics-link class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Copier le lien</button>
            <a href="<?= htmlspecialchars($shareUrl,ENT_QUOTES,'UTF-8') ?>" target="_blank" rel="noopener noreferrer" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Ouvrir le suivi</a>
        </div>
        <form method="post" action="/index.php?controller=statistics_share&action=save" class="mt-6 flex flex-wrap gap-2 border-t border-slate-200 pt-4" data-statistics-share-actions>
            <button name="share_action" value="regenerate" data-confirm="Regenerer le lien ? L ancien ne fonctionnera plus." class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold">Regenerer le lien</button>
            <button name="share_action" value="revoke" data-confirm="Desactiver le suivi public de cette caserne ?" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-semibold text-red-800">Desactiver</button>
        </form>
    <?php endif; ?>
</section>
<script src="/assets/statistics-share.js?v=<?= hash_file('sha256',__DIR__.'/../assets/statistics-share.js') ?>" defer></script>
<?php require __DIR__.'/partials/backoffice_shell_bottom.php'; ?>
