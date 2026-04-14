<?php

namespace App\Support;

/**
 * Keyword-based subcategory labels for monthly/yearly reports.
 * First matching keyword wins (config order matters — put specific before broad).
 */
final class CashflowSubcategoryClassifier
{
    /**
     * Stored override wins when valid; otherwise keyword classification.
     *
     * @return array{key: string, label: string}
     */
    public static function resolve(string $type, string $description, ?string $overrideKey): array
    {
        if ($overrideKey !== null && $overrideKey !== '') {
            $label = self::labelForKey($type, $overrideKey);
            if ($label !== null) {
                return ['key' => $overrideKey, 'label' => $label];
            }
        }

        return self::classify($type, $description);
    }

    public static function isValidKeyForType(string $type, string $key): bool
    {
        return self::labelForKey($type, $key) !== null;
    }

    public static function labelForKey(string $type, string $key): ?string
    {
        if ($key === 'uncategorized') {
            return 'Uncategorized';
        }

        $rows = config('daily_cashflow.subcategory_lexicon.'.$type);
        if (! is_array($rows)) {
            return null;
        }

        foreach ($rows as $row) {
            if (($row['key'] ?? '') === $key) {
                return (string) ($row['label'] ?? $key);
            }
        }

        return null;
    }

    /**
     * Key → label for designated subcategories in config (plus uncategorized). For UI fallbacks.
     *
     * @return array<string, string>
     */
    public static function designatedLabelsMapForType(string $type): array
    {
        $map = [
            '' => 'Auto — match description keywords',
            'uncategorized' => 'Uncategorized',
        ];
        $rows = config('daily_cashflow.subcategory_lexicon.'.$type);
        if (! is_array($rows)) {
            return $map;
        }
        foreach ($rows as $row) {
            $k = (string) ($row['key'] ?? '');
            if ($k === '') {
                continue;
            }
            $map[$k] = (string) ($row['label'] ?? $k);
        }

        return $map;
    }

    /**
     * Dropdown for recategorize: Auto first, then subcategory_lexicon rows in config order, then Uncategorized if not listed.
     *
     * @return list<array{key: string, label: string}>
     */
    public static function designatedEditOptionsForType(string $type): array
    {
        $out = [['key' => '', 'label' => 'Auto — match description keywords']];
        $rows = config('daily_cashflow.subcategory_lexicon.'.$type);
        if (is_array($rows)) {
            foreach ($rows as $row) {
                $k = (string) ($row['key'] ?? '');
                if ($k === '') {
                    continue;
                }
                $out[] = ['key' => $k, 'label' => (string) ($row['label'] ?? $k)];
            }
        }

        $keys = array_column($out, 'key');
        if (! in_array('uncategorized', $keys, true)) {
            $out[] = ['key' => 'uncategorized', 'label' => 'Uncategorized'];
        }

        return $out;
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function classify(string $type, string $description): array
    {
        $haystack = mb_strtolower(trim($description), 'UTF-8');
        if ($haystack === '') {
            return self::uncategorized();
        }

        $rows = config('daily_cashflow.subcategory_lexicon.'.$type);
        if (! is_array($rows) || $rows === []) {
            return self::uncategorized();
        }

        foreach ($rows as $row) {
            if (($row['keywords'] ?? null) === null || $row['keywords'] === []) {
                continue;
            }
            foreach ($row['keywords'] as $kw) {
                $kw = mb_strtolower(trim((string) $kw), 'UTF-8');
                if ($kw !== '' && str_contains($haystack, $kw)) {
                    return [
                        'key' => (string) ($row['key'] ?? 'uncategorized'),
                        'label' => (string) ($row['label'] ?? 'Uncategorized'),
                    ];
                }
            }
        }

        return self::uncategorized();
    }

    /**
     * @return array{key: string, label: string}
     */
    public static function uncategorized(): array
    {
        return ['key' => 'uncategorized', 'label' => 'Uncategorized'];
    }
}
