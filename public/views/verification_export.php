<?php

declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Export verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 24px;
            color: #111827;
        }

        h1, h2 {
            margin: 0 0 12px 0;
        }

        .meta {
            margin-bottom: 16px;
            padding: 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
        }

        .zone {
            margin-top: 16px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            text-align: left;
            font-size: 13px;
        }

        .actions {
            margin-bottom: 16px;
        }

        @media print {
            .actions {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a href="/index.php?controller=verifications&action=show&id=<?= isset($verification['id']) ? (int) $verification['id'] : 0 ?>">Retour detail</a>
        <button onclick="window.print()">Imprimer / Enregistrer en PDF</button>
    </div>

    <?php if ($verification === null): ?>
        <p>Verification introuvable.</p>
    <?php else: ?>
        <h1>Rapport de verification #<?= (int) $verification['id'] ?></h1>

        <div class="meta">
            <p><strong>Vehicule :</strong> <?= htmlspecialchars($verification['vehicule_nom'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Poste :</strong> <?= htmlspecialchars($verification['poste_nom'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Date :</strong> <?= htmlspecialchars((string) $verification['date_heure'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Verificateur :</strong> <?= htmlspecialchars($verification['agent'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Statut :</strong> <?= htmlspecialchars($verification['statut_global'], ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Signature :</strong> __________________________</p>
        </div>

        <?php
        $linesByZone = [];
        foreach ($lines as $line) {
            $linesByZone[$line['zone']][] = $line;
        }
        ?>

        <?php foreach ($linesByZone as $zone => $zoneLines): ?>
            <div class="zone">
                <h2><?= htmlspecialchars($zone, ENT_QUOTES, 'UTF-8') ?></h2>
                <table>
                    <thead>
                        <tr>
                            <th>Controle</th>
                            <th>Resultat</th>
                            <th>Valeur</th>
                            <th>Commentaire</th>
                            <th>Anomalie</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($zoneLines as $line): ?>
                            <tr>
                                <td><?= htmlspecialchars($line['libelle'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= strtoupper(htmlspecialchars((string) $line['resultat'], ENT_QUOTES, 'UTF-8')) ?></td>
                                <td>
                                    <?php if (($line['type_saisie'] ?? 'statut') !== 'statut'): ?>
                                        <?= htmlspecialchars((string) ($line['valeur_saisie'] ?? '-'), ENT_QUOTES, 'UTF-8') ?>
                                        <?php if (($line['unite'] ?? '') !== ''): ?>
                                            <?= htmlspecialchars((string) $line['unite'], ENT_QUOTES, 'UTF-8') ?>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td><?= nl2br(htmlspecialchars((string) ($line['commentaire'] ?? ''), ENT_QUOTES, 'UTF-8')) ?></td>
                                <td>
                                    <?php if (($line['anomalie_id'] ?? null) !== null): ?>
                                        #<?= (int) $line['anomalie_id'] ?> (<?= htmlspecialchars((string) $line['anomalie_statut'], ENT_QUOTES, 'UTF-8') ?>)
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>
