<?php $sliderConfiguration = \App\Core\SliderMeasure::configuration($controle['curseur_min'] ?? '', $controle['curseur_max'] ?? '', $controle['curseur_pas'] ?? ''); ?>
<?php if ($sliderConfiguration === null): ?>
    <p role="alert" class="text-sm text-red-200">Echelle du curseur invalide. Contacte un gestionnaire.</p>
<?php else: ?>
    <div class="measure-slider" data-measure-slider data-unit="<?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?>" data-threshold-min="<?= htmlspecialchars($minThreshold, ENT_QUOTES, 'UTF-8') ?>" data-threshold-max="<?= htmlspecialchars($maxThreshold, ENT_QUOTES, 'UTF-8') ?>" data-exclusive-minimum="<?= !empty($controle['seuil_min_exclusif']) ? '1' : '0' ?>">
        <div class="measure-slider-heading">
            <output data-slider-output aria-live="polite">Non renseign&eacute;</output>
            <span data-slider-status class="measure-slider-status" hidden></span>
        </div>
        <input type="range" min="<?= $sliderConfiguration['min'] ?>" max="<?= $sliderConfiguration['max'] ?>" step="<?= $sliderConfiguration['step'] ?>" value="<?= $sliderConfiguration['min'] ?>" data-slider-range aria-label="<?= htmlspecialchars($controlLabel . ' - curseur', ENT_QUOTES, 'UTF-8') ?>" aria-valuetext="Non renseigne">
        <div class="measure-slider-scale"><span><?= $sliderConfiguration['min'] ?> <?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?></span><span><?= $sliderConfiguration['max'] ?> <?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?></span></div>
        <div class="measure-slider-value">
            <input id="valeur_<?= $controleId ?>" type="hidden" name="valeurs[<?= $controleId ?>]" data-control-value="<?= $controleId ?>" data-slider-number>
            <p>Conformit&eacute;<?php if ($minThreshold !== ''): ?><span>Min : <strong><?= htmlspecialchars($minThreshold, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?></strong></span><?php endif; ?><?php if ($maxThreshold !== ''): ?><span>Max : <strong><?= htmlspecialchars($maxThreshold, ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($unit, ENT_QUOTES, 'UTF-8') ?></strong></span><?php endif; ?></p>
        </div>
    </div>
<?php endif; ?>
