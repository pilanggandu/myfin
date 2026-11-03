<?php

namespace App\Http\Requests\Concerns;

trait NormalizesMonetaryInputs
{
    protected function normalizeMonetaryInput(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        if (is_string($value)) {
            $clean = trim($value);

            // Strip currency symbols and whitespace
            $clean = preg_replace('/^(rp|idr|\$)\s*/i', '', $clean);

            if (str_contains($clean, '.') && str_contains($clean, ',')) {
                $lastDot = strrpos($clean, '.');
                $lastComma = strrpos($clean, ',');
                if ($lastComma > $lastDot) {
                    // Indonesian format: 150.166,67
                    $clean = str_replace('.', '', $clean);
                    $clean = str_replace(',', '.', $clean);
                } else {
                    // US format: 150,166.67
                    $clean = str_replace(',', '', $clean);
                }
            } elseif (str_contains($clean, ',')) {
                // Comma as decimal: 150166,67 -> 150166.67
                $clean = str_replace(',', '.', $clean);
            }

            return is_numeric($clean) ? round((float) $clean, 2) : null;
        }

        return round((float) $value, 2);
    }
}
