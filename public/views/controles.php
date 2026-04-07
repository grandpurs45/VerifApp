<?php

declare(strict_types=1);

$controlesParZone = [];

foreach ($controles as $controle) {
    $zone = $controle['zone'];
    $controlesParZone[$zone][] = $controle;
}

$errorCode = isset($_GET['error']) ? (string) $_GET['error'] : '';
$errorMessage = null;

if ($errorCode === 'agent_required') {
    $errorMessage = 'Nom du verificateur obligatoire.';
} elseif ($errorCode === 'incomplete') {
    $errorMessage = 'Tous les controles doivent etre renseignes.';
}

$fieldUser = $_SESSION['field_user'] ?? null;
$totalControles = count($controles);
$terrainMobileDensity = isset($terrainMobileDensity) ? (string) $terrainMobileDensity : 'normal';
$terrainStickyProgressEnabled = isset($terrainStickyProgressEnabled) ? (bool) $terrainStickyProgressEnabled : true;
$terrainDraftEnabled = isset($terrainDraftEnabled) ? (bool) $terrainDraftEnabled : true;
$terrainDraftTtlHours = isset($terrainDraftTtlHours) ? (int) $terrainDraftTtlHours : 12;
$terrainScrollMissingEnabled = isset($terrainScrollMissingEnabled) ? (bool) $terrainScrollMissingEnabled : true;
$verificationEveningHour = isset($verificationEveningHour) ? (int) $verificationEveningHour : 18;
$activeCaserneId = isset($caserneId) && $caserneId !== null ? (int) $caserneId : 0;
$fieldUserId = is_array($fieldUser) && isset($fieldUser['id']) ? (int) $fieldUser['id'] : 0;
$densityClass = $terrainMobileDensity === 'compact' ? 'space-y-3' : 'space-y-4';
$cardPaddingClass = $terrainMobileDensity === 'compact' ? 'p-3' : 'p-4';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controles - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Barlow", sans-serif; }
        .control-card-base {
            border-color: rgba(100, 116, 139, 0.7);
            background: rgba(15, 23, 42, 0.62);
        }
        .control-card-answered {
            border-color: rgba(52, 211, 153, 0.75) !important;
            box-shadow: 0 0 0 1px rgba(52, 211, 153, 0.2) inset;
        }
        .control-card-missing {
            border-color: rgba(248, 113, 113, 0.85) !important;
            background: rgba(127, 29, 29, 0.22) !important;
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-slate-100">
    <?php if ($terrainStickyProgressEnabled): ?>
        <div class="fixed inset-x-0 top-0 z-30 border-b border-slate-700 bg-slate-900/95 px-4 py-2 backdrop-blur">
            <div class="mx-auto w-full max-w-md">
                <div class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-200">
                    <span>Progression</span>
                    <span id="progressTextSticky">0 / <?= $totalControles ?></span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-700">
                    <div id="progressBarSticky" class="h-full w-0 rounded-full bg-amber-300 transition-all"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <main class="mx-auto w-full max-w-[30rem] px-4 pb-32 <?= $terrainStickyProgressEnabled ? 'pt-16' : 'pt-5' ?>">
        <header class="mb-4 rounded-3xl border border-slate-700/70 bg-slate-800/80 p-4 shadow-md">
            <?php if ($vehicle !== null && empty($fromVehicleQr)): ?>
                <?php $postesBackLink = '/index.php?controller=postes&action=list&vehicle_id=' . (int) $vehicle['id']; ?>
                <a href="<?= htmlspecialchars($postesBackLink, ENT_QUOTES, 'UTF-8') ?>" class="inline-flex rounded-lg border border-slate-600 px-3 py-2 text-xs font-semibold text-slate-200">
                    <- Retour postes
                </a>
            <?php elseif ($vehicle === null): ?>
                <a href="/index.php?controller=home&action=index" class="inline-flex rounded-lg border border-slate-600 px-3 py-2 text-xs font-semibold text-slate-200">
                    <- Retour vehicules
                </a>
            <?php endif; ?>

            <p class="mt-4 text-xs uppercase tracking-[0.18em] text-amber-300">Etape 3</p>
            <h1 class="mt-1 text-2xl font-extrabold text-white">Verification du poste</h1>

            <?php if ($vehicle !== null && $poste !== null): ?>
                <p class="mt-2 text-sm text-slate-300">
                    <span class="font-semibold text-white"><?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="mx-1 text-slate-500">/</span>
                    <span class="font-semibold text-white"><?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                </p>
            <?php endif; ?>

            <?php if (!$terrainStickyProgressEnabled): ?>
                <div class="mt-4">
                    <div class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-300">
                        <span>Progression</span>
                        <span id="progressText">0 / <?= $totalControles ?></span>
                    </div>
                    <div class="h-2 overflow-hidden rounded-full bg-slate-700">
                        <div id="progressBar" class="h-full w-0 rounded-full bg-amber-300 transition-all"></div>
                    </div>
                </div>
            <?php else: ?>
                <div id="progressText" class="hidden">0 / <?= $totalControles ?></div>
                <div id="progressBar" class="hidden"></div>
            <?php endif; ?>

            <p id="draftNotice" class="mt-3 hidden rounded-xl border border-emerald-300 bg-emerald-500/15 px-3 py-2 text-xs font-semibold text-emerald-200"></p>
            <p id="missingNotice" class="mt-3 hidden rounded-lg bg-red-500/20 px-3 py-2 text-xs font-semibold text-red-100"></p>
            <p id="draftIdentityHint" class="mt-2 hidden text-[11px] text-slate-400">Pour reprendre un brouillon non connecte: saisir le meme nom de verificateur.</p>
            <p id="draftRemainingInfo" class="mt-2 text-[11px] text-slate-400"></p>
        </header>

        <?php if ($vehicle === null): ?>
            <section class="rounded-3xl border border-slate-700 bg-slate-800/85 p-5 shadow-lg">
                <p class="text-base font-semibold text-white">Vehicule introuvable.</p>
            </section>
        <?php elseif ($poste === null): ?>
            <section class="rounded-3xl border border-slate-700 bg-slate-800/85 p-5 shadow-lg">
                <p class="text-base font-semibold text-white">Poste introuvable pour ce vehicule.</p>
            </section>
        <?php elseif ($controles === []): ?>
            <section class="rounded-3xl border border-slate-700 bg-slate-800/85 p-5 shadow-lg">
                <p class="text-base font-semibold text-white">Aucun controle actif pour ce poste.</p>
            </section>
        <?php else: ?>
            <?php if ($errorMessage !== null): ?>
                <section class="mb-4 rounded-2xl border border-red-300 bg-red-100 p-4 text-red-800 shadow">
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </section>
            <?php endif; ?>

            <form method="post" action="/index.php?controller=verifications&action=store" class="<?= $densityClass ?>" id="checklistForm">
                <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['id'] ?>">
                <input type="hidden" name="poste_id" value="<?= (int) $poste['id'] ?>">
                <?php if (!empty($fromVehicleQr)): ?>
                    <input type="hidden" name="from_vehicle_qr" value="1">
                <?php endif; ?>

                <section class="rounded-3xl border border-slate-700/70 bg-slate-800/75 <?= $cardPaddingClass ?> shadow-md space-y-4">
                    <?php if (is_array($fieldUser) && isset($fieldUser['nom'])): ?>
                        <p class="text-sm font-medium text-slate-200">
                            Verificateur : <span class="font-extrabold text-white"><?= htmlspecialchars((string) $fieldUser['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                        </p>
                    <?php else: ?>
                        <div>
                            <label for="agent" class="text-sm font-semibold text-slate-200">Nom du verificateur</label>
                            <input
                                id="agent"
                                name="agent"
                                type="text"
                                required
                                class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white"
                                placeholder="Nom et prenom"
                            >
                        </div>
                    <?php endif; ?>
                </section>

                <?php foreach ($controlesParZone as $zone => $controlesZone): ?>
                    <section class="rounded-3xl border border-slate-700/70 bg-slate-800/75 <?= $cardPaddingClass ?> shadow-md">
                        <h2 class="mb-3 text-lg font-extrabold text-amber-200"><?= htmlspecialchars($zone, ENT_QUOTES, 'UTF-8') ?></h2>

                        <ul class="<?= $densityClass ?>">
                            <?php foreach ($controlesZone as $controle): ?>
                                <?php
                                $controleId = (int) $controle['id'];
                                $inputType = strtolower((string) ($controle['type_saisie'] ?? 'statut'));
                                if (!in_array($inputType, ['statut', 'quantite', 'mesure'], true)) {
                                    $inputType = 'statut';
                                }
                                $unit = isset($controle['unite']) ? trim((string) $controle['unite']) : '';
                                $minThreshold = isset($controle['seuil_min']) ? (string) $controle['seuil_min'] : '';
                                $maxThreshold = isset($controle['seuil_max']) ? (string) $controle['seuil_max'] : '';
                                $controlLabel = (string) $controle['libelle'];
                                $expectedQuantity = isset($controle['valeur_attendue']) ? (int) $controle['valeur_attendue'] : 0;
                                if ($inputType === 'quantite' && $expectedQuantity > 0) {
                                    $controlLabel = $expectedQuantity . ' ' . $controlLabel;
                                }
                                ?>
                                <li class="control-card-base rounded-2xl border p-3 transition-colors" data-control-card data-control-type="<?= htmlspecialchars($inputType, ENT_QUOTES, 'UTF-8') ?>" data-control-id="<?= $controleId ?>">
                                    <p class="mb-3 text-base font-semibold text-white"><?= htmlspecialchars($controlLabel, ENT_QUOTES, 'UTF-8') ?></p>

                                    <?php if ($inputType === 'quantite'): ?>
                                        <div class="grid grid-cols-2 gap-2 text-sm font-extrabold">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="resultats[<?= $controleId ?>]" value="ok" class="peer sr-only control-radio" required data-control-id="<?= $controleId ?>">
                                                <span class="block rounded-xl border border-emerald-400 bg-emerald-200/10 px-2 py-3.5 text-center text-emerald-100 transition peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:shadow-lg">
                                                    Present
                                                </span>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="resultats[<?= $controleId ?>]" value="nok" class="peer sr-only control-radio" required data-control-id="<?= $controleId ?>">
                                                <span class="block rounded-xl border border-red-400 bg-red-200/10 px-2 py-3.5 text-center text-red-100 transition peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-lg">
                                                    Manquant
                                                </span>
                                            </label>
                                        </div>
                                        <div class="mt-3 hidden" data-comment-wrap="<?= $controleId ?>" data-require-on-nok="0">
                                            <label for="commentaire_<?= $controleId ?>" class="text-xs font-semibold text-slate-300">
                                                Commentaire (optionnel)
                                            </label>
                                            <textarea
                                                id="commentaire_<?= $controleId ?>"
                                                name="commentaires[<?= $controleId ?>]"
                                                rows="2"
                                                class="mt-1 w-full rounded-xl border border-slate-500 bg-slate-900 px-3 py-2 text-sm text-white"
                                                placeholder="Precision utile si manquant"
                                            ></textarea>
                                        </div>
                                    <?php elseif ($inputType === 'statut'): ?>
                                        <div class="grid grid-cols-2 gap-2 text-sm font-extrabold">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="resultats[<?= $controleId ?>]" value="ok" class="peer sr-only control-radio" required data-control-id="<?= $controleId ?>">
                                                <span class="block rounded-xl border border-emerald-400 bg-emerald-200/10 px-2 py-3.5 text-center text-emerald-100 transition peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:shadow-lg">
                                                    Fonctionnel
                                                </span>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="resultats[<?= $controleId ?>]" value="nok" class="peer sr-only control-radio" required data-control-id="<?= $controleId ?>">
                                                <span class="block rounded-xl border border-red-400 bg-red-200/10 px-2 py-3.5 text-center text-red-100 transition peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-lg">
                                                    Non fonctionnel
                                                </span>
                                            </label>
                                        </div>

                                        <div class="mt-3 hidden" data-comment-wrap="<?= $controleId ?>" data-require-on-nok="1">
                                            <label for="commentaire_<?= $controleId ?>" class="text-xs font-semibold text-red-200">
                                                Commentaire Non fonctionnel (obligatoire)
                                            </label>
                                            <textarea
                                                id="commentaire_<?= $controleId ?>"
                                                name="commentaires[<?= $controleId ?>]"
                                                rows="2"
                                                class="mt-1 w-full rounded-xl border border-red-400 bg-slate-900 px-3 py-2 text-sm text-white"
                                                placeholder="Precise la panne ou le dysfonctionnement"
                                            ></textarea>
                                        </div>
                                    <?php else: ?>
                                        <div class="rounded-xl border border-slate-600 bg-slate-900 p-3 space-y-2">
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                                <div>
                                                    <label for="valeur_<?= $controleId ?>" class="text-xs font-semibold text-slate-300">Valeur relevee</label>
                                                    <input
                                                        id="valeur_<?= $controleId ?>"
                                                        name="valeurs[<?= $controleId ?>]"
                                                        type="number"
                                                        step="1"
                                                        inputmode="numeric"
                                                        required
                                                        class="mt-1 w-full rounded-xl border border-slate-500 bg-slate-950 px-3 py-2 text-sm text-white"
                                                        data-control-value="<?= $controleId ?>"
                                                    >
                                                </div>
                                                <div class="text-xs text-slate-300 pt-1">
                                                    <?php if ($unit !== ''): ?><p>Unite : <strong><?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?></strong></p><?php endif; ?>
                                                    <?php if ($minThreshold !== ''): ?><p>Min : <strong><?= htmlspecialchars($minThreshold, ENT_QUOTES, 'UTF-8') ?></strong></p><?php endif; ?>
                                                    <?php if ($maxThreshold !== ''): ?><p>Max : <strong><?= htmlspecialchars($maxThreshold, ENT_QUOTES, 'UTF-8') ?></strong></p><?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="mt-3" data-comment-wrap="<?= $controleId ?>">
                                            <label for="commentaire_<?= $controleId ?>" class="text-xs font-semibold text-slate-300">
                                                Commentaire (optionnel)
                                            </label>
                                            <textarea
                                                id="commentaire_<?= $controleId ?>"
                                                name="commentaires[<?= $controleId ?>]"
                                                rows="2"
                                                class="mt-1 w-full rounded-xl border border-slate-500 bg-slate-900 px-3 py-2 text-sm text-white"
                                                placeholder="Contexte, precision, observation"
                                            ></textarea>
                                        </div>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>

                <section class="rounded-3xl border border-slate-700/70 bg-slate-800/75 <?= $cardPaddingClass ?> shadow-md space-y-3">
                    <label for="commentaire_global" class="text-sm font-semibold text-slate-200">Commentaire global (optionnel)</label>
                    <textarea
                        id="commentaire_global"
                        name="commentaire_global"
                        rows="2"
                        class="w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white"
                        placeholder="Info generale utile (contexte de la verification)"
                    ></textarea>
                </section>

                <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-700/80 bg-slate-900/95 p-3 backdrop-blur">
                    <div class="mx-auto w-full max-w-md">
                        <button type="submit" class="w-full rounded-2xl bg-amber-300 px-5 py-3.5 text-base font-extrabold text-slate-900 shadow-lg active:scale-[0.99]">
                            Enregistrer la verification
                        </button>
                        <?php if (!$terrainStickyProgressEnabled): ?>
                            <p class="mt-2 text-center text-xs font-semibold text-slate-300">
                                Controles completes : <span id="progressBottom">0 / <?= $totalControles ?></span>
                            </p>
                        <?php else: ?>
                            <span id="progressBottom" class="hidden">0 / <?= $totalControles ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </main>

    <script>
        (function () {
            const total = <?= $totalControles ?>;
            const cards = Array.from(document.querySelectorAll('[data-control-card]'));
            const radios = Array.from(document.querySelectorAll('.control-radio'));
            const numericInputs = Array.from(document.querySelectorAll('input[data-control-value]'));
            const progressText = document.getElementById('progressText');
            const progressBottom = document.getElementById('progressBottom');
            const progressBar = document.getElementById('progressBar');
            const progressTextSticky = document.getElementById('progressTextSticky');
            const progressBarSticky = document.getElementById('progressBarSticky');
            const form = document.getElementById('checklistForm');
            const draftNotice = document.getElementById('draftNotice');
            const draftIdentityHint = document.getElementById('draftIdentityHint');
            const missingNotice = document.getElementById('missingNotice');
            const isDraftEnabled = <?= $terrainDraftEnabled ? 'true' : 'false' ?>;
            const draftTtlHours = <?= $terrainDraftTtlHours ?>;
            const draftRemainingInfo = document.getElementById('draftRemainingInfo');
            const scrollMissingEnabled = <?= $terrainScrollMissingEnabled ? 'true' : 'false' ?>;
            const eveningStartHour = <?= $verificationEveningHour ?>;
            const caserneId = <?= $activeCaserneId ?>;
            const vehicleId = <?= (int) ($vehicle['id'] ?? 0) ?>;
            const posteId = <?= (int) ($poste['id'] ?? 0) ?>;
            const authenticatedUserId = <?= $fieldUserId ?>;
            const agentInput = document.querySelector('input[name="agent"]');
            let restoreAttempted = false;
            let draftExpiryTimestamp = null;
            let remainingTimer = null;

            function updateProgress() {
                let answered = 0;

                cards.forEach((card) => {
                    const type = card.dataset.controlType || 'statut';
                    const controlId = card.dataset.controlId || '';

                    if (type !== 'mesure') {
                        const checked = card.querySelector('input.control-radio:checked');
                        if (checked) {
                            answered += 1;
                        }
                        return;
                    }

                    const input = card.querySelector('input[data-control-value="' + controlId + '"]');
                    if (input && input.value.trim() !== '') {
                        answered += 1;
                    }
                });

                const pct = total > 0 ? Math.round((answered / total) * 100) : 0;
                if (progressText) progressText.textContent = answered + ' / ' + total;
                if (progressBottom) progressBottom.textContent = answered + ' / ' + total;
                if (progressBar) progressBar.style.width = pct + '%';
                if (progressTextSticky) progressTextSticky.textContent = answered + ' / ' + total;
                if (progressBarSticky) progressBarSticky.style.width = pct + '%';
            }

            function updateCommentVisibility(controlId, value) {
                const wrap = document.querySelector('[data-comment-wrap="' + controlId + '"]');
                if (!wrap) return;
                const textarea = wrap.querySelector('textarea');
                const requireOnNok = wrap.dataset.requireOnNok === '1';

                if (value === 'nok') {
                    wrap.classList.remove('hidden');
                    if (textarea && requireOnNok) textarea.required = true;
                } else {
                    wrap.classList.add('hidden');
                    if (textarea) {
                        textarea.required = false;
                        if (requireOnNok) {
                            textarea.value = '';
                        }
                    }
                }
            }

            function getShiftKey() {
                const now = new Date();
                const yyyy = now.getFullYear();
                const mm = String(now.getMonth() + 1).padStart(2, '0');
                const dd = String(now.getDate()).padStart(2, '0');
                const slot = now.getHours() >= eveningStartHour ? 'soir' : 'matin';
                return yyyy + '-' + mm + '-' + dd + '-' + slot;
            }

            function normalizeIdentity(value) {
                return (value || '')
                    .toLowerCase()
                    .normalize('NFD')
                    .replace(/[\u0300-\u036f]/g, '')
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '')
                    .slice(0, 48);
            }

            function getDraftIdentity() {
                if (authenticatedUserId > 0) {
                    return 'u_' + authenticatedUserId;
                }

                if (!agentInput) {
                    return null;
                }

                const agentValue = normalizeIdentity(agentInput.value);
                if (agentValue.length < 3) {
                    return null;
                }

                return 'a_' + agentValue;
            }

            function getDraftStorageKey() {
                const identity = getDraftIdentity();
                if (!identity) {
                    if (draftIdentityHint) {
                        draftIdentityHint.classList.remove('hidden');
                    }
                    return null;
                }
                if (draftIdentityHint) {
                    draftIdentityHint.classList.add('hidden');
                }
                return 'verifapp_draft_v2_' + caserneId + '_' + vehicleId + '_' + posteId + '_' + identity;
            }

            function showDraftNotice(message) {
                if (!draftNotice) return;
                draftNotice.textContent = message;
                draftNotice.classList.remove('hidden');
            }

            function formatRemaining(ms) {
                const totalMinutes = Math.max(0, Math.floor(ms / 60000));
                const hours = Math.floor(totalMinutes / 60);
                const minutes = totalMinutes % 60;
                if (hours <= 0) {
                    return minutes + ' min';
                }
                return hours + 'h' + String(minutes).padStart(2, '0');
            }

            function refreshDraftRemainingInfo() {
                if (!draftRemainingInfo) return;
                if (!isDraftEnabled) {
                    draftRemainingInfo.textContent = 'Brouillon desactive pour cette caserne.';
                    return;
                }

                const draftKey = getDraftStorageKey();
                if (!draftKey) {
                    draftRemainingInfo.textContent = 'Brouillon: saisis ton nom pour activer/reprendre.';
                    return;
                }

                if (draftExpiryTimestamp === null) {
                    draftRemainingInfo.textContent = 'Brouillon local: conservation max ' + draftTtlHours + 'h.';
                    return;
                }

                const remainingMs = draftExpiryTimestamp - Date.now();
                if (remainingMs <= 0) {
                    draftRemainingInfo.textContent = 'Brouillon expire (nouvelle saisie).';
                    draftExpiryTimestamp = null;
                    return;
                }

                draftRemainingInfo.textContent = 'Brouillon actif: expire dans ' + formatRemaining(remainingMs) + '.';
            }

            function startRemainingTimer() {
                if (remainingTimer !== null) {
                    window.clearInterval(remainingTimer);
                }
                remainingTimer = window.setInterval(refreshDraftRemainingInfo, 30000);
            }

            function clearMissingState() {
                cards.forEach((card) => {
                    card.classList.remove('control-card-missing');
                });
                if (missingNotice) {
                    missingNotice.classList.add('hidden');
                    missingNotice.textContent = '';
                }
            }

            function refreshAnsweredState() {
                cards.forEach((card) => {
                    const type = card.dataset.controlType || 'statut';
                    const controlId = card.dataset.controlId || '';
                    let answered = false;

                    if (type === 'mesure') {
                        const input = card.querySelector('input[data-control-value="' + controlId + '"]');
                        answered = !!(input && input.value.trim() !== '');
                    } else {
                        answered = !!card.querySelector('input.control-radio:checked');
                    }

                    card.classList.toggle('control-card-answered', answered);
                    if (!answered && !card.classList.contains('control-card-missing')) {
                        card.classList.remove('control-card-answered');
                    }
                });
            }

            function collectDraftData() {
                const statuses = {};
                radios.forEach((radio) => {
                    if (radio.checked) {
                        statuses[radio.name] = radio.value;
                    }
                });

                const values = {};
                numericInputs.forEach((input) => {
                    values[input.name] = input.value;
                });

                const comments = {};
                const commentFields = Array.from(document.querySelectorAll('textarea[name^="commentaires["], textarea[name="commentaire_global"], input[name="agent"]'));
                commentFields.forEach((field) => {
                    comments[field.name] = field.value;
                });

                return { statuses, values, comments };
            }

            function saveDraft() {
                if (!isDraftEnabled) return;
                try {
                    const draftKey = getDraftStorageKey();
                    if (!draftKey) {
                        return;
                    }
                    draftExpiryTimestamp = Date.now() + (draftTtlHours * 60 * 60 * 1000);
                    const payload = {
                        updatedAt: Date.now(),
                        shiftKey: getShiftKey(),
                        data: collectDraftData(),
                    };
                    window.localStorage.setItem(draftKey, JSON.stringify(payload));
                    refreshDraftRemainingInfo();
                } catch (error) {
                }
            }

            function restoreDraftIfValid() {
                if (!isDraftEnabled) return;
                const draftKey = getDraftStorageKey();
                if (!draftKey) return;
                restoreAttempted = true;

                let raw = null;
                try {
                    raw = window.localStorage.getItem(draftKey);
                } catch (error) {
                    return;
                }
                if (!raw) return;

                let parsed = null;
                try {
                    parsed = JSON.parse(raw);
                } catch (error) {
                    return;
                }
                if (!parsed || typeof parsed !== 'object') return;
                if (parsed.shiftKey !== getShiftKey()) return;
                if (typeof parsed.updatedAt !== 'number') return;

                const ageMs = Date.now() - parsed.updatedAt;
                if (ageMs > draftTtlHours * 60 * 60 * 1000) return;
                draftExpiryTimestamp = parsed.updatedAt + (draftTtlHours * 60 * 60 * 1000);

                const data = parsed.data || {};
                const statuses = data.statuses || {};
                const values = data.values || {};
                const comments = data.comments || {};

                radios.forEach((radio) => {
                    if (statuses[radio.name] === radio.value) {
                        radio.checked = true;
                        updateCommentVisibility(radio.dataset.controlId, radio.value);
                    }
                });

                numericInputs.forEach((input) => {
                    if (Object.prototype.hasOwnProperty.call(values, input.name)) {
                        input.value = values[input.name];
                    }
                });

                const commentFields = Array.from(document.querySelectorAll('textarea[name^="commentaires["], textarea[name="commentaire_global"], input[name="agent"]'));
                commentFields.forEach((field) => {
                    if (Object.prototype.hasOwnProperty.call(comments, field.name)) {
                        field.value = comments[field.name];
                    }
                });

                updateProgress();
                const expiresAt = new Date(parsed.updatedAt + (draftTtlHours * 60 * 60 * 1000));
                const hh = String(expiresAt.getHours()).padStart(2, '0');
                const mm = String(expiresAt.getMinutes()).padStart(2, '0');
                showDraftNotice('Brouillon restaure (valide jusqu a ' + hh + ':' + mm + ').');
                refreshDraftRemainingInfo();
            }

            function validateBeforeSubmit() {
                clearMissingState();
                const missingCards = [];

                cards.forEach((card) => {
                    const type = card.dataset.controlType || 'statut';
                    const controlId = card.dataset.controlId || '';
                    let missing = false;

                    if (type === 'mesure') {
                        const input = card.querySelector('input[data-control-value="' + controlId + '"]');
                        missing = !(input && input.value.trim() !== '');
                    } else {
                        const checked = card.querySelector('input.control-radio:checked');
                        if (!checked) {
                            missing = true;
                        } else if (checked.value === 'nok') {
                            const comment = card.querySelector('textarea[name="commentaires[' + controlId + ']"]');
                            const wrap = card.querySelector('[data-comment-wrap="' + controlId + '"]');
                            const requireOnNok = wrap ? wrap.dataset.requireOnNok === '1' : false;
                            if (requireOnNok) {
                                missing = !(comment && comment.value.trim() !== '');
                            }
                        }
                    }

                    if (missing) {
                        card.classList.add('control-card-missing');
                        card.classList.remove('control-card-answered');
                        missingCards.push(card);
                    }
                });

                if (missingCards.length > 0) {
                    if (missingNotice) {
                        missingNotice.textContent = 'Controle(s) incomplet(s): complete les zones en rouge.';
                        missingNotice.classList.remove('hidden');
                    }
                    if (scrollMissingEnabled) {
                        missingCards[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    return false;
                }

                refreshAnsweredState();
                return true;
            }

            radios.forEach((radio) => {
                radio.addEventListener('change', () => {
                    updateProgress();
                    updateCommentVisibility(radio.dataset.controlId, radio.value);
                    saveDraft();
                });
            });

            numericInputs.forEach((input) => {
                input.addEventListener('input', () => {
                    updateProgress();
                    saveDraft();
                });
            });

                if (form) {
                const draftInputs = Array.from(form.querySelectorAll('input[name="agent"], textarea[name="commentaire_global"], textarea[name^="commentaires["]'));
                draftInputs.forEach((field) => {
                    field.addEventListener('input', saveDraft);
                });

                if (agentInput) {
                    agentInput.addEventListener('blur', () => {
                        restoreDraftIfValid();
                    });
                    agentInput.addEventListener('input', () => {
                        if (!restoreAttempted) {
                            restoreDraftIfValid();
                        }
                    });
                }

                form.addEventListener('submit', (event) => {
                    if (!validateBeforeSubmit()) {
                        event.preventDefault();
                        return;
                    }
                    try {
                        const draftKey = getDraftStorageKey();
                        if (draftKey) {
                            window.localStorage.removeItem(draftKey);
                        }
                        draftExpiryTimestamp = null;
                        refreshDraftRemainingInfo();
                    } catch (error) {
                    }
                });
            }

            restoreDraftIfValid();
            updateProgress();
            refreshAnsweredState();
            refreshDraftRemainingInfo();
            startRemainingTimer();
        })();
    </script>
</body>
</html>
