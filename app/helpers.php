<?php

if (! function_exists('qty_fmt')) {
    /**
     * Format quantities for display: whole numbers without ".00"; fractional trims trailing zeros.
     * Use number_format($x, 2) (or 4 for unit costs) for money.
     */
    function qty_fmt(float|int|string|null $value, int $maxDecimals = 4): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $n = is_numeric($value) ? (float) $value : 0.0;
        if (! is_finite($n)) {
            return '0';
        }

        if (abs($n - round($n)) < 1e-9) {
            return (string) (int) round($n);
        }

        $s = number_format($n, $maxDecimals, '.', '');
        $s = rtrim(rtrim($s, '0'), '.');

        return $s === '' || $s === '-0' ? '0' : $s;
    }
}
