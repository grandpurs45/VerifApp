<?php

declare(strict_types=1);

namespace App\Core;

final class SliderMeasure
{
    /** @return array{min:int,max:int,step:int}|null */
    public static function configuration(mixed $min, mixed $max, mixed $step): ?array
    {
        $values = [];
        foreach ([$min, $max, $step] as $raw) {
            if (!is_int($raw) && !is_string($raw)) {
                return null;
            }
            $value = filter_var(trim((string) $raw), FILTER_VALIDATE_INT);
            if ($value === false || abs($value) > 99999999) {
                return null;
            }
            $values[] = $value;
        }
        [$min, $max, $step] = $values;
        if ($min >= $max || $step <= 0 || ($max - $min) % $step !== 0) {
            return null;
        }

        return ['min' => $min, 'max' => $max, 'step' => $step];
    }

    public static function accepts(array $configuration, int $value): bool
    {
        return $value >= $configuration['min'] && $value <= $configuration['max']
            && ($value - $configuration['min']) % $configuration['step'] === 0;
    }
}
