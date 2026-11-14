<?php

namespace App\Http\Requests\Concerns;

trait NormalizesMonetaryInputs
{
    protected function normalizeMonetaryInput(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
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
            } elseif (substr_count($clean, '.') > 1 || preg_match('/\.\d{3}(?:\.|$)/', $clean)) {
                // Indonesian format with thousand dots and no decimal comma: e.g. "25.000.000" or "25.000"
                $clean = str_replace('.', '', $clean);
            }

            return is_numeric($clean) ? round((float) $clean, 2) : null;
        }

        return round((float) $value, 2);
    }

    protected function normalizeRateInput(mixed $value, int $decimals = 5): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return round((float) $value, $decimals);
        }

        if (is_string($value)) {
            $clean = trim($value);

            // Strip percent signs, whitespace, and accidental currency symbols
            $clean = preg_replace('/[%\s]/', '', $clean);
            $clean = preg_replace('/^(rp|idr|\$)/i', '', $clean);

            if (str_contains($clean, '.') && str_contains($clean, ',')) {
                $lastDot = strrpos($clean, '.');
                $lastComma = strrpos($clean, ',');
                if ($lastComma > $lastDot) {
                    $clean = str_replace('.', '', $clean);
                    $clean = str_replace(',', '.', $clean);
                } else {
                    $clean = str_replace(',', '', $clean);
                }
            } elseif (str_contains($clean, ',')) {
                // Indonesian decimal comma: e.g. "2,45125" -> "2.45125"
                $clean = str_replace(',', '.', $clean);
            }

            return is_numeric($clean) ? round((float) $clean, $decimals) : null;
        }

        return round((float) $value, $decimals);
    }
}
