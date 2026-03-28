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
    </style>
</head>
<body class="min-h-screen bg-gradient-to-b from-slate-900 via-slate-800 to-slate-900 text-slate-100">
    <main class="mx-auto w-full max-w-md px-4 pb-32 pt-5">
        <header class="mb-5 rounded-3xl border border-slate-700/80 bg-slate-800/85 p-4 shadow-lg">
            <?php if ($vehicle !== null): ?>
                <a href="/index.php?controller=postes&action=list&vehicle_id=<?= (int) $vehicle['id'] ?>" class="inline-flex rounded-lg border border-slate-600 px-3 py-2 text-xs font-semibold text-slate-200">
                    <- Retour postes
                </a>
            <?php else: ?>
                <a href="/index.php?controller=home&action=index" class="inline-flex rounded-lg border border-slate-600 px-3 py-2 text-xs font-semibold text-slate-200">
                    <- Retour vehicules
                </a>
            <?php endif; ?>

            <p class="mt-4 text-xs uppercase tracking-[0.18em] text-amber-300">Etape 3</p>
            <h1 class="mt-1 text-2xl font-extrabold text-white">Checklist du poste</h1>

            <?php if ($vehicle !== null && $poste !== null): ?>
                <p class="mt-2 text-sm text-slate-300">
                    <span class="font-semibold text-white"><?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="mx-1 text-slate-500">/</span>
                    <span class="font-semibold text-white"><?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?></span>
                </p>
            <?php endif; ?>

            <div class="mt-4">
                <div class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-300">
                    <span>Progression</span>
                    <span id="progressText">0 / <?= $totalControles ?></span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-slate-700">
                    <div id="progressBar" class="h-full w-0 rounded-full bg-amber-300 transition-all"></div>
                </div>
            </div>
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

            <form method="post" action="/index.php?controller=verifications&action=store" class="space-y-4" id="checklistForm">
                <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['id'] ?>">
                <input type="hidden" name="poste_id" value="<?= (int) $poste['id'] ?>">

                <section class="rounded-3xl border border-slate-700 bg-slate-800/85 p-4 shadow-lg space-y-4">
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

                    <div>
                        <label for="commentaire_global" class="text-sm font-semibold text-slate-200">Commentaire global (optionnel)</label>
                        <textarea
                            id="commentaire_global"
                            name="commentaire_global"
                            rows="2"
                            class="mt-1 w-full rounded-2xl border border-slate-500 bg-slate-900 px-4 py-3 text-base text-white"
                            placeholder="Info generale utile"
                        ></textarea>
                    </div>
                </section>

                <?php foreach ($controlesParZone as $zone => $controlesZone): ?>
                    <section class="rounded-3xl border border-slate-700 bg-slate-800/85 p-4 shadow-lg">
                        <h2 class="mb-3 text-lg font-extrabold text-amber-200"><?= htmlspecialchars($zone, ENT_QUOTES, 'UTF-8') ?></h2>

                        <ul class="space-y-4">
                            <?php foreach ($controlesZone as $controle): ?>
                                <?php
                                $controleId = (int) $controle['id'];
                                $inputType = strtolower((string) ($controle['type_saisie'] ?? 'statut'));
                                if (!in_array($inputType, ['statut', 'quantite', 'mesure'], true)) {
                                    $inputType = 'statut';
                                }
                                $expectedValue = isset($controle['valeur_attendue']) ? (string) $controle['valeur_attendue'] : '';
                                $unit = isset($controle['unite']) ? trim((string) $controle['unite']) : '';
                                $minThreshold = isset($controle['seuil_min']) ? (string) $controle['seuil_min'] : '';
                                $maxThreshold = isset($controle['seuil_max']) ? (string) $controle['seuil_max'] : '';
                                $controlLabel = (string) $controle['libelle'];
                                $displayLabel = $controlLabel;

                                if ($inputType === 'quantite' && $expectedValue !== '') {
                                    $normalizedExpected = str_replace(',', '.', trim($expectedValue));
                                    if (is_numeric($normalizedExpected)) {
                                        $expectedNumber = (float) $normalizedExpected;
                                        if (abs($expectedNumber - round($expectedNumber)) < 0.0000001) {
                                            $expectedText = (string) (int) round($expectedNumber);
                                        } else {
                                            $expectedText = rtrim(rtrim(number_format($expectedNumber, 2, '.', ''), '0'), '.');
                                        }
                                    } else {
                                        $expectedText = $expectedValue;
                                    }
                                    $displayLabel = trim($expectedText . ' ' . $controlLabel);
                                }
                                ?>
                                <li class="rounded-2xl border border-slate-600 bg-slate-900/70 p-3" data-control-card data-control-type="<?= htmlspecialchars($inputType, ENT_QUOTES, 'UTF-8') ?>" data-control-id="<?= $controleId ?>">
                                    <p class="mb-3 text-base font-semibold text-white"><?= htmlspecialchars($displayLabel, ENT_QUOTES, 'UTF-8') ?></p>

                                    <?php if ($inputType === 'statut' || $inputType === 'quantite'): ?>
                                        <div class="grid grid-cols-3 gap-2 text-sm font-extrabold">
                                            <label class="cursor-pointer">
                                                <input type="radio" name="resultats[<?= $controleId ?>]" value="ok" class="peer sr-only control-radio" required data-control-id="<?= $controleId ?>">
                                                <span class="block rounded-xl border border-emerald-400 bg-emerald-200/20 px-2 py-4 text-center text-emerald-100 transition peer-checked:bg-emerald-500 peer-checked:text-white peer-checked:shadow-lg">
                                                    OK
                                                </span>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="resultats[<?= $controleId ?>]" value="nok" class="peer sr-only control-radio" required data-control-id="<?= $controleId ?>">
                                                <span class="block rounded-xl border border-red-400 bg-red-200/20 px-2 py-4 text-center text-red-100 transition peer-checked:bg-red-500 peer-checked:text-white peer-checked:shadow-lg">
                                                    NOK
                                                </span>
                                            </label>
                                            <label class="cursor-pointer">
                                                <input type="radio" name="resultats[<?= $controleId ?>]" value="na" class="peer sr-only control-radio" required data-control-id="<?= $controleId ?>">
                                                <span class="block rounded-xl border border-slate-400 bg-slate-300/20 px-2 py-4 text-center text-slate-100 transition peer-checked:bg-slate-500 peer-checked:text-white peer-checked:shadow-lg">
                                                    NA
                                                </span>
                                            </label>
                                        </div>

                                        <div class="mt-3 hidden" data-comment-wrap="<?= $controleId ?>">
                                            <label for="commentaire_<?= $controleId ?>" class="text-xs font-semibold text-red-200">
                                                Commentaire NOK (obligatoire)
                                            </label>
                                            <textarea
                                                id="commentaire_<?= $controleId ?>"
                                                name="commentaires[<?= $controleId ?>]"
                                                rows="2"
                                                class="mt-1 w-full rounded-xl border border-red-400 bg-slate-900 px-3 py-2 text-sm text-white"
                                                placeholder="Explique l'anomalie constatee"
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
                                                        step="0.01"
                                                        inputmode="decimal"
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

                <div class="fixed inset-x-0 bottom-0 z-20 border-t border-slate-700 bg-slate-900/95 p-3 backdrop-blur">
                    <div class="mx-auto w-full max-w-md">
                        <button type="submit" class="w-full rounded-2xl bg-amber-300 px-5 py-4 text-base font-extrabold text-slate-900 shadow-lg active:scale-[0.99]">
                            Enregistrer la verification
                        </button>
                        <p class="mt-2 text-center text-xs font-semibold text-slate-300">
                            Controles completes : <span id="progressBottom">0 / <?= $totalControles ?></span>
                        </p>
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
            }

            function updateCommentVisibility(controlId, value) {
                const wrap = document.querySelector('[data-comment-wrap="' + controlId + '"]');
                if (!wrap) return;
                const textarea = wrap.querySelector('textarea');

                if (value === 'nok') {
                    wrap.classList.remove('hidden');
                    if (textarea) textarea.required = true;
                } else {
                    wrap.classList.add('hidden');
                    if (textarea) {
                        textarea.required = false;
                        textarea.value = '';
                    }
                }
            }

            radios.forEach((radio) => {
                radio.addEventListener('change', () => {
                    updateProgress();
                    updateCommentVisibility(radio.dataset.controlId, radio.value);
                });
            });

            numericInputs.forEach((input) => {
                input.addEventListener('input', updateProgress);
            });

            updateProgress();
        })();
    </script>
</body>
</html>
