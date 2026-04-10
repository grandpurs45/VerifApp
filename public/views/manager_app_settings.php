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
<?php elseif ($error === 'settings_store_unavailable'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm space-y-2">
        <p>Le stockage des parametres en base n est pas disponible.</p>
        <p class="text-xs">Action requise: appliquer les migrations (incluant <code>018_create_app_settings.sql</code>).</p>
    </section>
<?php elseif ($error === 'settings_store_failed'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm space-y-2">
        <p>Impossible d enregistrer le token en base de donnees.</p>
        <p class="text-xs">Verifier la connexion base de donnees et les droits SQL sur la table <code>app_settings</code>.</p>
    </section>
<?php elseif ($error === 'invalid_target'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Cible de regeneration invalide.
    </section>
<?php elseif ($success === 'caserne_created'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Caserne creee.
    </section>
<?php elseif ($success === 'caserne_updated'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Caserne mise a jour.
    </section>
<?php elseif ($success === 'timing_saved'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Reglage matin/soir enregistre pour cette caserne.
    </section>
<?php elseif ($success === 'terrain_ux_saved'): ?>
    <section class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-emerald-700 text-sm">
        Reglages UX mobile terrain enregistres pour cette caserne.
    </section>
<?php elseif ($error === 'caserne_invalid'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Nom et code caserne obligatoires.
    </section>
<?php elseif ($error === 'caserne_duplicate'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Code caserne deja utilise.
    </section>
<?php elseif ($error === 'caserne_last_active'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Impossible de desactiver la derniere caserne active.
    </section>
<?php elseif ($error === 'caserne_save_failed'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Enregistrement caserne impossible.
    </section>
<?php elseif ($error === 'timing_invalid'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Heure invalide. Saisir une valeur entre 0 et 23.
    </section>
<?php elseif ($error === 'timing_save_failed'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Enregistrement du reglage matin/soir impossible.
    </section>
<?php elseif ($error === 'terrain_ux_invalid'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Parametres UX invalides (densite ou duree brouillon).
    </section>
<?php elseif ($error === 'terrain_ux_save_failed'): ?>
    <section class="rounded-xl border border-red-200 bg-red-50 p-4 text-red-700 text-sm">
        Enregistrement des parametres UX mobile impossible.
    </section>
<?php endif; ?>

<section class="rounded-2xl bg-white shadow p-5">
    <h2 class="text-lg font-bold">Securite session</h2>
    <p class="text-sm text-slate-600 mt-2">
        Expiration de session gestionnaire (minutes): <strong><?= htmlspecialchars($sessionTimeout, ENT_QUOTES, 'UTF-8') ?></strong>
    </p>
    <p class="text-xs text-slate-500 mt-2">
        Source actuelle: <?= $settingsStorage === 'database' ? 'base de donnees (app_settings)' : 'fichier d environnement (.env)' ?>.
    </p>
</section>

<section class="rounded-2xl bg-white shadow p-5">
    <h2 class="text-lg font-bold">Decoupage des verifications</h2>
    <p class="text-sm text-slate-600 mt-2">
        Regle de la vue mensuelle pour cette caserne.
    </p>

    <form method="post" action="/index.php?controller=manager_admin&action=verification_timing_save" class="mt-4 grid grid-cols-1 md:grid-cols-3 gap-2 items-end">
        <div>
            <label for="verification_evening_hour" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Heure debut "soir"</label>
            <input
                id="verification_evening_hour"
                type="number"
                min="0"
                max="23"
                name="verification_evening_hour"
                value="<?= htmlspecialchars((string) $verificationEveningHour, ENT_QUOTES, 'UTF-8') ?>"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
            >
            <p class="mt-1 text-xs text-slate-500">
                Matin: avant cette heure. Soir: a partir de cette heure.
            </p>
        </div>
        <div class="md:col-span-2">
            <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Enregistrer</button>
        </div>
    </form>
</section>

<section class="rounded-2xl bg-white shadow p-5">
    <h2 class="text-lg font-bold">UX mobile terrain V2</h2>
    <p class="text-sm text-slate-600 mt-2">
        Reglages de saisie mobile rapides, lisibles et adaptes a cette caserne.
    </p>

    <form method="post" action="/index.php?controller=manager_admin&action=terrain_ux_save" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
        <div>
            <label for="terrain_mobile_density" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Densite d affichage mobile</label>
            <select id="terrain_mobile_density" name="terrain_mobile_density" class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
                <option value="normal" <?= $terrainMobileDensity === 'normal' ? 'selected' : '' ?>>Normal (lisible)</option>
                <option value="compact" <?= $terrainMobileDensity === 'compact' ? 'selected' : '' ?>>Compact (plus dense)</option>
            </select>
        </div>

        <div>
            <label for="terrain_draft_ttl_hours" class="text-xs font-semibold uppercase tracking-wide text-slate-500">Conservation brouillon (heures)</label>
            <input
                id="terrain_draft_ttl_hours"
                type="number"
                min="1"
                max="48"
                name="terrain_draft_ttl_hours"
                value="<?= htmlspecialchars((string) $terrainDraftTtlHours, ENT_QUOTES, 'UTF-8') ?>"
                class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
            >
            <p class="mt-1 text-xs text-slate-500">Reprise autorisee uniquement sur le meme creneau (matin/soir) de la journee.</p>
        </div>

        <label class="rounded-xl border border-slate-200 p-3 flex items-start gap-3">
            <input type="checkbox" name="terrain_sticky_progress_enabled" value="1" <?= $terrainStickyProgressEnabled ? 'checked' : '' ?> class="mt-1 h-4 w-4">
            <span>
                <span class="block text-sm font-semibold text-slate-800">Barre de progression fixe</span>
                <span class="block text-xs text-slate-500">Affiche la progression en permanence pendant le scroll.</span>
            </span>
        </label>

        <label class="rounded-xl border border-slate-200 p-3 flex items-start gap-3">
            <input type="checkbox" name="terrain_draft_enabled" value="1" <?= $terrainDraftEnabled ? 'checked' : '' ?> class="mt-1 h-4 w-4">
            <span>
                <span class="block text-sm font-semibold text-slate-800">Brouillon automatique</span>
                <span class="block text-xs text-slate-500">Sauvegarde locale auto et reprise apres interruption.</span>
            </span>
        </label>

        <label class="rounded-xl border border-slate-200 p-3 flex items-start gap-3 md:col-span-2">
            <input type="checkbox" name="terrain_scroll_missing_enabled" value="1" <?= $terrainScrollMissingEnabled ? 'checked' : '' ?> class="mt-1 h-4 w-4">
            <span>
                <span class="block text-sm font-semibold text-slate-800">Aide champs manquants</span>
                <span class="block text-xs text-slate-500">Au submit, scroll automatique vers le premier controle incomplet + surlignage clair.</span>
            </span>
        </label>

        <div class="md:col-span-2">
            <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Enregistrer UX terrain</button>
        </div>
    </form>
</section>

<section class="rounded-2xl bg-white shadow p-5">
    <h2 class="text-lg font-bold">Casernes</h2>
    <p class="text-sm text-slate-600 mt-2">
        Configuration multi-caserne de la plateforme.
    </p>

    <form method="post" action="/index.php?controller=manager_admin&action=caserne_save" class="mt-4 grid grid-cols-1 md:grid-cols-12 gap-2">
        <input type="hidden" name="id" value="0">
        <input type="text" name="nom" required placeholder="Nom caserne (ex: Caserne Nord)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-5">
        <input type="text" name="code" required placeholder="Code (ex: caserne_nord)" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
        <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
            <option value="1">Active</option>
            <option value="0">Inactive</option>
        </select>
        <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold md:col-span-2">Ajouter</button>
    </form>

    <div class="mt-4 space-y-2">
        <?php foreach ($casernes as $caserne): ?>
            <form method="post" action="/index.php?controller=manager_admin&action=caserne_save" class="grid grid-cols-1 md:grid-cols-12 gap-2 items-center rounded-xl border border-slate-200 p-3">
                <input type="hidden" name="id" value="<?= (int) ($caserne['id'] ?? 0) ?>">
                <input type="text" name="nom" required value="<?= htmlspecialchars((string) ($caserne['nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-5">
                <input type="text" name="code" required value="<?= htmlspecialchars((string) ($caserne['code'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-3">
                <select name="actif" class="rounded-xl border border-slate-300 px-3 py-2 text-sm md:col-span-2">
                    <option value="1" <?= (int) ($caserne['actif'] ?? 0) === 1 ? 'selected' : '' ?>>Active</option>
                    <option value="0" <?= (int) ($caserne['actif'] ?? 0) !== 1 ? 'selected' : '' ?>>Inactive</option>
                </select>
                <button type="submit" class="rounded-xl bg-slate-800 text-white px-4 py-2 text-sm font-semibold md:col-span-2">Enregistrer</button>
            </form>
        <?php endforeach; ?>
    </div>

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
                <button
                    type="button"
                    data-print-qr="1"
                    data-print-title="Verification terrain"
                    data-print-url="<?= htmlspecialchars($fieldGuestUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
                >
                    Imprimer A4
                </button>
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
                <button
                    type="button"
                    data-print-qr="1"
                    data-print-title="Sortie pharmacie"
                    data-print-url="<?= htmlspecialchars($pharmacyGuestUrl, ENT_QUOTES, 'UTF-8') ?>"
                    class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700"
                >
                    Imprimer A4
                </button>
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

        const printButtons = document.querySelectorAll('button[data-print-qr]');
        printButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const qrTitle = button.getAttribute('data-print-title') || 'QR Code';
                const qrTargetUrl = button.getAttribute('data-print-url') || '';
                if (!qrTargetUrl) {
                    return;
                }

                const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=700x700&data=' + encodeURIComponent(qrTargetUrl);
                const printWindow = window.open('', '_blank', 'width=900,height=700');
                if (!printWindow) {
                    window.alert('Autorisez les popups pour imprimer le QR code.');
                    return;
                }

                const safeTitle = qrTitle
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
                const safeUrl = qrTargetUrl
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');

                const html = `
<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<title>Affiche A4 - ${safeTitle}</title>
<style>
@page { size: A4 portrait; margin: 12mm; }
html, body { margin:0; padding:0; font-family: Arial, sans-serif; color:#0f172a; background:#fff; }
.page {
    width: 100%;
    min-height: calc(297mm - 24mm);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    text-align: center;
}
.card {
    width: 100%;
    max-width: 170mm;
    border: 2px solid #0f172a;
    border-radius: 16px;
    padding: 16mm 12mm;
    box-sizing: border-box;
}
.title { font-size: 14px; text-transform: uppercase; letter-spacing: 1px; color:#475569; margin:0 0 6px; }
.module { font-size: 34px; font-weight: 800; margin:0 0 14px; line-height: 1.2; }
.qr-wrap { display:flex; justify-content:center; margin: 8px 0 14px; }
.qr { width: 125mm; max-width: 100%; height: auto; border:1px solid #cbd5e1; border-radius:10px; padding:8px; box-sizing:border-box; background:white; }
.hint { margin:0 0 6px; font-size: 14px; font-weight: 700; color:#1e293b; }
.url { margin:0; font-size: 11px; color:#334155; word-break: break-all; }
</style>
</head>
<body>
    <main class="page">
        <div class="card">
            <p class="title">VerifApp</p>
            <p class="module">${safeTitle}</p>
            <div class="qr-wrap"><img class="qr" src="${qrUrl}" alt="QR ${safeTitle}"></div>
            <p class="hint">Scanner pour ouvrir le formulaire</p>
            <p class="url">${safeUrl}</p>
        </div>
    </main>
</body>
</html>`;

                printWindow.document.open();
                printWindow.document.write(html);
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => {
                    printWindow.print();
                }, 300);
            });
        });
    })();
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>
