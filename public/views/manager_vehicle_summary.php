<?php

declare(strict_types=1);

$successMap = [
    'vehicle_qr_saved' => 'QR vehicule genere / regenere.',
    'vehicle_qr_deleted' => 'QR vehicule supprime.',
];

$errorMap = [
    'invalid_vehicle' => 'Vehicule invalide.',
    'vehicle_qr_store_failed' => 'Impossible de mettre a jour le QR vehicule.',
];

$successMessage = $flash['success'] !== '' ? ($successMap[$flash['success']] ?? 'Operation terminee.') : null;
$errorMessage = $flash['error'] !== '' ? ($errorMap[$flash['error']] ?? 'Une erreur est survenue.') : null;

$vehicleName = (string) ($vehicle['nom'] ?? '');
$vehicleId = (int) ($vehicle['id'] ?? 0);
$vehicleType = (string) ($vehicle['type_vehicule'] ?? '');
$vehicleStatus = ((int) ($vehicle['actif'] ?? 0) === 1) ? 'Actif' : 'Inactif';

$pageTitle = 'Fiche vehicule - VerifApp';
$pageHeading = 'Fiche vehicule';
$pageSubtitle = $vehicleName;
$pageBackUrl = '/index.php?controller=manager_assets&action=vehicles';
$pageBackLabel = 'Retour vehicules';

require __DIR__ . '/partials/backoffice_shell_top.php';
?>

<?php if ($successMessage !== null || $errorMessage !== null): ?>
    <section id="manager-toast" class="fixed inset-0 z-50 flex items-center justify-center p-4 pointer-events-none">
        <div class="pointer-events-auto w-full max-w-xl rounded-xl border p-4 text-sm shadow-lg <?= $errorMessage !== null ? 'border-red-200 bg-red-50 text-red-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' ?>">
            <?= htmlspecialchars((string) ($errorMessage ?? $successMessage), ENT_QUOTES, 'UTF-8') ?>
        </div>
    </section>
<?php endif; ?>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-3">
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Type</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= htmlspecialchars($vehicleType, ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Numero</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= htmlspecialchars((string) ($vehicle['indicatif'] ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Zones</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= (int) $vehicleZonesCount ?></p>
        </article>
        <article class="rounded-xl border border-slate-200 p-3">
            <p class="text-xs uppercase tracking-wide text-slate-500">Materiels</p>
            <p class="mt-1 text-sm font-semibold text-slate-900"><?= (int) $vehicleControlesCount ?></p>
        </article>
    </div>
    <div class="mt-3">
        <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold <?= $vehicleStatus === 'Actif' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-700' ?>">
            <?= htmlspecialchars($vehicleStatus, ENT_QUOTES, 'UTF-8') ?>
        </span>
        <a href="/index.php?controller=manager_assets&action=vehicle_zones&id=<?= $vehicleId ?>" class="ml-2 inline-flex rounded-xl border border-slate-300 bg-slate-100 text-slate-900 px-3 py-1.5 text-xs font-semibold">Gerer les zones</a>
    </div>
</section>

<section class="bg-white rounded-2xl shadow p-4 md:p-6">
    <h2 class="text-lg font-bold text-slate-900">QR verification engin</h2>
    <p class="text-sm text-slate-600 mt-2">
        QR dedie a ce vehicule. Par defaut il n est pas genere a la creation.
    </p>

    <?php if ($fieldVehicleGuestUrl === ''): ?>
        <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-amber-800 text-sm">
            Aucun QR engin genere pour ce vehicule.
        </div>
        <form method="post" action="/index.php?controller=manager_assets&action=vehicle_qr_save" class="mt-3">
            <input type="hidden" name="vehicle_id" value="<?= $vehicleId ?>">
            <input type="hidden" name="qr_action" value="generate">
            <button type="submit" class="rounded-xl bg-slate-900 text-white px-4 py-2 text-sm font-semibold">Generer le QR code</button>
        </form>
    <?php else: ?>
        <p class="text-xs text-slate-500 mt-3 break-all" id="vehicleGuestUrl"><?= htmlspecialchars($fieldVehicleGuestUrl, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="mt-3 flex flex-wrap gap-2">
            <a href="<?= htmlspecialchars($fieldVehicleGuestUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="rounded-lg bg-slate-900 text-white px-3 py-2 text-xs font-semibold">Ouvrir lien</a>
            <button type="button" data-copy-target="vehicleGuestUrl" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Copier lien</button>
            <button type="button" class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700" data-print-vehicle-qr="1" data-print-name="<?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?>" data-print-url="<?= htmlspecialchars($fieldVehicleGuestUrl, ENT_QUOTES, 'UTF-8') ?>">Imprimer QR</button>
            <form method="post" action="/index.php?controller=manager_assets&action=vehicle_qr_save" class="inline">
                <input type="hidden" name="vehicle_id" value="<?= $vehicleId ?>">
                <input type="hidden" name="qr_action" value="generate">
                <button type="submit" data-confirm="Regenerer le QR ? L ancien lien ne fonctionnera plus." class="rounded-lg border border-slate-300 px-3 py-2 text-xs font-semibold text-slate-700">Re-generer</button>
            </form>
            <form method="post" action="/index.php?controller=manager_assets&action=vehicle_qr_save" class="inline">
                <input type="hidden" name="vehicle_id" value="<?= $vehicleId ?>">
                <input type="hidden" name="qr_action" value="delete">
                <button type="submit" data-confirm="Supprimer le QR de ce vehicule ?" class="rounded-lg bg-red-600 text-white px-3 py-2 text-xs font-semibold">Supprimer QR</button>
            </form>
        </div>
        <div class="mt-4">
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=260x260&data=<?= rawurlencode($fieldVehicleGuestUrl) ?>" alt="QR verification vehicule <?= htmlspecialchars($vehicleName, ENT_QUOTES, 'UTF-8') ?>" class="h-48 w-48 rounded-lg border border-slate-200 bg-white p-2">
        </div>
    <?php endif; ?>
</section>

<script>
    (function () {
        const toast = document.getElementById('manager-toast');
        if (toast) {
            setTimeout(function () {
                toast.style.transition = 'opacity 240ms ease';
                toast.style.opacity = '0';
                setTimeout(function () { toast.remove(); }, 260);
            }, 2800);
        }

        document.querySelectorAll('form').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                const submitter = event.submitter;
                if (!submitter) return;
                const confirmMessage = submitter.dataset.confirm || '';
                if (confirmMessage !== '' && !window.confirm(confirmMessage)) {
                    event.preventDefault();
                }
            });
        });

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
                    setTimeout(() => { button.textContent = previous; }, 1200);
                } catch (error) {
                    window.prompt('Copiez ce lien:', value);
                }
            });
        });

        const printButtons = document.querySelectorAll('button[data-print-vehicle-qr]');
        printButtons.forEach((button) => {
            button.addEventListener('click', () => {
                const vehicleName = button.getAttribute('data-print-name') || 'Vehicule';
                const vehicleUrl = button.getAttribute('data-print-url') || '';
                if (!vehicleUrl) return;

                const qrUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=420x420&data=' + encodeURIComponent(vehicleUrl);
                const printWindow = window.open('', '_blank', 'width=900,height=700');
                if (!printWindow) {
                    window.alert('Autorisez les popups pour imprimer le QR code.');
                    return;
                }
                const safeName = vehicleName.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                const safeUrl = vehicleUrl.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                const html = `<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>QR ${safeName}</title><style>@page{size:A6 portrait;margin:8mm;}html,body{margin:0;padding:0;font-family:Arial,sans-serif;color:#0f172a}.card{width:100%;border:2px solid #0f172a;border-radius:12px;padding:12px;box-sizing:border-box}.title{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:#475569;margin:0 0 6px}.vehicle{font-size:22px;font-weight:800;margin:0 0 10px;line-height:1.2}.qr-wrap{display:flex;justify-content:center;margin:6px 0 10px}.qr{width:90mm;max-width:100%;height:auto;border:1px solid #cbd5e1;border-radius:8px;padding:8px;box-sizing:border-box;background:white}.hint{margin:0 0 4px;font-size:11px;font-weight:700;color:#1e293b}.url{margin:0;font-size:9px;color:#334155;word-break:break-all}</style></head><body><main class="card"><p class="title">VerifApp - QR vehicule</p><p class="vehicle">${safeName}</p><div class="qr-wrap"><img class="qr" src="${qrUrl}" alt="QR ${safeName}"></div><p class="hint">Scan direct vers la verification du vehicule</p><p class="url">${safeUrl}</p></main></body></html>`;
                printWindow.document.open();
                printWindow.document.write(html);
                printWindow.document.close();
                printWindow.focus();
                setTimeout(() => printWindow.print(), 300);
            });
        });
    })();
</script>

<?php require __DIR__ . '/partials/backoffice_shell_bottom.php'; ?>

