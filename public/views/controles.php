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
    $errorMessage = 'Le nom du verificateur est obligatoire.';
} elseif ($errorCode === 'incomplete') {
    $errorMessage = 'Chaque controle doit etre renseigne (OK/NOK/NA).';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controles - VerifApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 min-h-screen text-slate-900">
    <main class="max-w-3xl mx-auto p-4 md:p-8 pb-20">
        <header class="mb-6">
            <?php if ($vehicle !== null): ?>
                <a href="/index.php?controller=postes&action=list&vehicle_id=<?= (int) $vehicle['id'] ?>" class="text-sm text-slate-500 hover:text-slate-700">
                    <- Retour aux postes
                </a>
            <?php else: ?>
                <a href="/index.php?controller=home&action=index" class="text-sm text-slate-500 hover:text-slate-700">
                    <- Retour a la liste des vehicules
                </a>
            <?php endif; ?>

            <h1 class="text-3xl font-bold mt-2">Checklist du poste</h1>

            <?php if ($vehicle !== null && $poste !== null): ?>
                <p class="text-slate-600 mt-2">
                    Vehicule : <?= htmlspecialchars($vehicle['nom'], ENT_QUOTES, 'UTF-8') ?>
                </p>
                <p class="text-slate-600">
                    Poste : <?= htmlspecialchars($poste['nom'], ENT_QUOTES, 'UTF-8') ?>
                </p>
            <?php endif; ?>
        </header>

        <?php if ($vehicle === null): ?>
            <section class="bg-white rounded-2xl shadow p-6">
                <p class="text-slate-500">Vehicule introuvable.</p>
            </section>
        <?php elseif ($poste === null): ?>
            <section class="bg-white rounded-2xl shadow p-6">
                <p class="text-slate-500">Poste introuvable pour ce vehicule.</p>
            </section>
        <?php elseif ($controles === []): ?>
            <section class="bg-white rounded-2xl shadow p-6">
                <p class="text-slate-500">Aucun controle actif pour ce poste.</p>
            </section>
        <?php else: ?>
            <?php if ($errorMessage !== null): ?>
                <section class="mb-4 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                    <?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?>
                </section>
            <?php endif; ?>

            <form method="post" action="/index.php?controller=verifications&action=store" class="space-y-6">
                <input type="hidden" name="vehicle_id" value="<?= (int) $vehicle['id'] ?>">
                <input type="hidden" name="poste_id" value="<?= (int) $poste['id'] ?>">

                <section class="bg-white rounded-2xl shadow p-4 md:p-6 space-y-4">
                    <div>
                        <label for="agent" class="text-sm font-medium text-slate-700">Verificateur</label>
                        <input
                            id="agent"
                            name="agent"
                            type="text"
                            required
                            class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-base"
                            placeholder="Nom et prenom"
                        >
                    </div>

                    <div>
                        <label for="commentaire_global" class="text-sm font-medium text-slate-700">Commentaire global (optionnel)</label>
                        <textarea
                            id="commentaire_global"
                            name="commentaire_global"
                            rows="3"
                            class="mt-1 w-full rounded-xl border border-slate-300 px-4 py-3 text-base"
                            placeholder="Informations utiles sur la verification"
                        ></textarea>
                    </div>
                </section>

                <?php foreach ($controlesParZone as $zone => $controlesZone): ?>
                    <section class="bg-white rounded-2xl shadow p-4 md:p-6">
                        <h2 class="text-xl font-semibold mb-4">
                            <?= htmlspecialchars($zone, ENT_QUOTES, 'UTF-8') ?>
                        </h2>

                        <ul class="space-y-4">
                            <?php foreach ($controlesZone as $controle): ?>
                                <?php $controleId = (int) $controle['id']; ?>
                                <li class="border border-slate-200 rounded-xl p-4">
                                    <p class="font-medium mb-3">
                                        <?= htmlspecialchars($controle['libelle'], ENT_QUOTES, 'UTF-8') ?>
                                    </p>

                                    <div class="grid grid-cols-3 gap-2 text-sm font-semibold">
                                        <label class="rounded-lg border border-emerald-300 bg-emerald-50 p-3 text-center cursor-pointer">
                                            <input type="radio" name="resultats[<?= $controleId ?>]" value="ok" class="sr-only" required>
                                            OK
                                        </label>
                                        <label class="rounded-lg border border-red-300 bg-red-50 p-3 text-center cursor-pointer">
                                            <input type="radio" name="resultats[<?= $controleId ?>]" value="nok" class="sr-only" required>
                                            NOK
                                        </label>
                                        <label class="rounded-lg border border-slate-300 bg-slate-50 p-3 text-center cursor-pointer">
                                            <input type="radio" name="resultats[<?= $controleId ?>]" value="na" class="sr-only" required>
                                            NA
                                        </label>
                                    </div>

                                    <div class="mt-3">
                                        <label for="commentaire_<?= $controleId ?>" class="text-sm text-slate-600">Commentaire</label>
                                        <textarea
                                            id="commentaire_<?= $controleId ?>"
                                            name="commentaires[<?= $controleId ?>]"
                                            rows="2"
                                            class="mt-1 w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"
                                            placeholder="Precision utile (obligatoire en cas de NOK)"
                                        ></textarea>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </section>
                <?php endforeach; ?>

                <div class="sticky bottom-0 py-3">
                    <button
                        type="submit"
                        class="w-full rounded-xl bg-slate-900 text-white px-5 py-4 text-base font-semibold shadow-lg"
                    >
                        Enregistrer la verification
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>
