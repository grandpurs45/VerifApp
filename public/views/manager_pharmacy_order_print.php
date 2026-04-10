<?php

declare(strict_types=1);

$scopeLabel = $summaryScope === 'pending' ? 'Reste a traiter (non acquitte)' : 'Toutes sorties';
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Bon de commande pharmacie</title>
    <style>
        @page { size: A4 portrait; margin: 12mm; }
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            color: #0f172a;
            background: #ffffff;
            font-size: 12px;
        }
        .actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
            margin: 8px 0 12px;
        }
        .btn {
            border: 1px solid #94a3b8;
            background: #f8fafc;
            color: #0f172a;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }
        .btn.primary {
            background: #0f172a;
            color: #ffffff;
            border-color: #0f172a;
        }
        .sheet {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 12px;
        }
        h1 {
            margin: 0 0 6px;
            font-size: 22px;
            line-height: 1.2;
        }
        .meta {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 6px 16px;
            margin: 10px 0 12px;
        }
        .meta p {
            margin: 0;
            color: #334155;
        }
        .cards {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
            margin-bottom: 12px;
        }
        .card {
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 8px;
            background: #f8fafc;
        }
        .card .label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: .08em;
            color: #64748b;
        }
        .card .value {
            margin-top: 4px;
            font-size: 20px;
            font-weight: 800;
            color: #0f172a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #e2e8f0;
            padding: 7px 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #f1f5f9;
            color: #334155;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .05em;
        }
        .empty {
            margin-top: 8px;
            border: 1px dashed #cbd5e1;
            border-radius: 10px;
            padding: 12px;
            color: #64748b;
        }
        @media print {
            .actions { display: none; }
            .sheet { border: none; padding: 0; }
        }
    </style>
</head>
<body>
    <div class="actions">
        <a href="/index.php?controller=manager_pharmacy&action=outputs&summary_scope=<?= htmlspecialchars((string) $summaryScope, ENT_QUOTES, 'UTF-8') ?>" class="btn">Retour sorties</a>
        <button type="button" class="btn primary" onclick="window.print()">Imprimer</button>
    </div>

    <main class="sheet">
        <h1>Bon de commande pharmacie</h1>

        <div class="meta">
            <p><strong>Caserne:</strong> <?= htmlspecialchars($caserneName !== '' ? $caserneName : ('ID ' . (string) $caserneId), ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Date generation:</strong> <?= htmlspecialchars($generatedAt, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Mode synthese:</strong> <?= htmlspecialchars($scopeLabel, ENT_QUOTES, 'UTF-8') ?></p>
            <p><strong>Derniere commande:</strong> <?= htmlspecialchars((string) ($lastOrder['commande_le'] ?? 'Aucune'), ENT_QUOTES, 'UTF-8') ?></p>
        </div>

        <div class="cards">
            <div class="card">
                <div class="label">Articles</div>
                <div class="value"><?= count($rows) ?></div>
            </div>
            <div class="card">
                <div class="label">Quantite totale</div>
                <div class="value"><?= (int) $totalQuantity ?></div>
            </div>
            <div class="card">
                <div class="label">Lignes sorties</div>
                <div class="value"><?= (int) $totalLines ?></div>
            </div>
        </div>

        <?php if ($rows === []): ?>
            <section class="empty">Aucune sortie a inclure pour ce mode de synthese.</section>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Unite</th>
                        <th>Quantite a commander</th>
                        <th>Lignes sorties</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($row['article_nom'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row['article_unite'] ?? 'u'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) round((float) ($row['quantite_totale'] ?? 0)) ?></td>
                            <td><?= (int) ($row['lignes'] ?? 0) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </main>
</body>
</html>
