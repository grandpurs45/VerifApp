<fieldset class="grid grid-cols-1 gap-3 md:col-span-12 md:grid-cols-3 hidden" data-wrap="slider-fields">
    <legend class="mb-2 text-xs font-semibold text-slate-600">Echelle du curseur (independante des seuils de conformite)</legend>
    <?php foreach (['curseur_min' => ['Minimum de l echelle', 0], 'curseur_max' => ['Maximum de l echelle', 300], 'curseur_pas' => ['Pas du curseur', 10]] as $sliderField => [$sliderLabel, $sliderDefault]): ?>
        <label>
            <span class="mb-1 block text-xs font-semibold text-slate-700"><?= $sliderLabel ?> *</span>
            <input type="number" step="1" name="<?= $sliderField ?>" value="<?= htmlspecialchars((string) ($sliderControl[$sliderField] ?? $sliderDefault), ENT_QUOTES, 'UTF-8') ?>" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm" disabled>
        </label>
    <?php endforeach; ?>
</fieldset>
