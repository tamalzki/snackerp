<?php

namespace App\Support;

use App\Models\DailyCashEntry;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class DailyCashflowCategories
{
    public const CASH_FROM_BANK = 'cash_from_bank';

    public const HOME_SA_BALAY = 'home_sa_balay';

    /** @return array<string, string> value => label */
    public static function presetsForType(string $type): array
    {
        $common = config('daily_cashflow.category_presets.'.$type, []);

        return is_array($common) ? $common : [];
    }

    /**
     * Add-entry / edit UI: category groups with subcategory keys (see config daily_cashflow.category_form_groups).
     *
     * @return list<array{key: string, label: string, category_value: ?string, subcategory_keys: list<string>}>
     */
    public static function categoryFormGroupsForType(string $type): array
    {
        $rows = config('daily_cashflow.category_form_groups.'.$type, []);

        return is_array($rows) ? $rows : [];
    }

    /** Stored ledger category when user picks this report subcategory (null = no preset tag). */
    public static function categoryValueForSubcategory(string $type, string $subKey): ?string
    {
        foreach (self::categoryFormGroupsForType($type) as $g) {
            if (in_array($subKey, $g['subcategory_keys'] ?? [], true)) {
                return $g['category_value'] ?? null;
            }
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: ?string} category, subcategory_override
     */
    public static function resolveCategoryAndSubcategoryForEntryRequest(Request $request, string $type, bool $isCashFromBank): array
    {
        if ($isCashFromBank) {
            return [self::CASH_FROM_BANK, null];
        }

        $sub = self::normalizedSubcategoryFromForm($type, $request->input('subcategory_key'));

        $category = null;
        if ($request->filled('category_preset') && $request->input('category_preset') !== 'none') {
            $category = self::validatedFromRequest($request);
        } elseif ($sub !== null) {
            $category = self::categoryValueForSubcategory($type, $sub);
        }

        $category = self::sanitizeCategoryForType($category, $type);

        return [$category, $sub];
    }

    public static function normalizedSubcategoryFromForm(string $type, mixed $raw): ?string
    {
        if ($raw === null || $raw === '' || $raw === 'auto') {
            return null;
        }
        $s = (string) $raw;
        if (! CashflowSubcategoryClassifier::isValidKeyForType($type, $s)) {
            throw ValidationException::withMessages([
                'subcategory_key' => ['Choose a valid subcategory for this type, or “Other / infer from description”.'],
            ]);
        }

        return $s;
    }

    public static function normalize(?string $type, ?string $preset, ?string $customPiece): ?string
    {
        $preset = $preset !== null && $preset !== '' ? trim($preset) : null;
        $customPiece = $customPiece !== null ? trim($customPiece) : '';

        if ($preset === null || $preset === '') {
            return null;
        }

        if (in_array($preset, ['income_plus', 'discretionary_plus', 'savings_plus'], true)) {
            if ($customPiece === '') {
                return null;
            }
            $slug = strtoupper(preg_replace('/\s+/', ' ', $customPiece));

            return match ($preset) {
                'income_plus' => 'income:'.$slug,
                'discretionary_plus' => 'discretionary:'.$slug,
                'savings_plus' => 'savings:'.$slug,
                default => null,
            };
        }

        return $preset;
    }

    public static function label(DailyCashEntry $entry): string
    {
        $c = $entry->category;
        if ($c === null || $c === '') {
            return $entry->description !== '' && $entry->description !== null
                ? (string) $entry->description
                : 'Uncategorized';
        }

        $map = [];
        foreach (['INCOME', 'EXPENSES', 'PURCHASES', 'DISCRETIONARY', 'SAVINGS', 'CAPITAL', 'OTHER'] as $t) {
            $map = array_merge($map, self::presetsForType($t));
        }

        if (isset($map[$c])) {
            return $map[$c];
        }

        if (str_starts_with($c, 'income:')) {
            return 'Income + '.substr($c, 7);
        }
        if (str_starts_with($c, 'discretionary:')) {
            return 'Discretionary + '.substr($c, 14);
        }
        if (str_starts_with($c, 'savings:')) {
            return 'Savings + '.substr($c, 8);
        }

        return $c;
    }

    public static function isAllowedPresetForType(string $type, string $preset): bool
    {
        if (in_array($preset, ['income_plus', 'discretionary_plus', 'savings_plus'], true)) {
            return match ($preset) {
                'income_plus' => $type === 'INCOME',
                'discretionary_plus' => $type === 'DISCRETIONARY',
                'savings_plus' => $type === 'SAVINGS',
                default => false,
            };
        }

        $allowed = array_keys(self::presetsForType($type));

        return in_array($preset, $allowed, true);
    }

    /**
     * Whether a stored category value is valid for the given entry type (preset keys, dynamic prefixes, bank withdrawal).
     */
    public static function categoryAllowedForType(?string $category, string $type): bool
    {
        if ($category === null || $category === '') {
            return true;
        }

        if ($category === self::CASH_FROM_BANK) {
            return $type === 'INCOME';
        }

        if (str_starts_with($category, 'income:')) {
            return $type === 'INCOME';
        }
        if (str_starts_with($category, 'discretionary:')) {
            return $type === 'DISCRETIONARY';
        }
        if (str_starts_with($category, 'savings:')) {
            return $type === 'SAVINGS';
        }

        foreach (array_keys(DailyCashEntry::$types) as $t) {
            $presets = self::presetsForType($t);
            if (isset($presets[$category])) {
                return $t === $type;
            }
        }

        return true;
    }

    /** Drop category when it does not match the entry type (e.g. after changing type in the editor). */
    public static function sanitizeCategoryForType(?string $category, string $type): ?string
    {
        if ($category === null || $category === '') {
            return null;
        }

        return self::categoryAllowedForType($category, $type) ? $category : null;
    }

    /**
     * @return array{preset: string, custom: string}
     */
    public static function entryToFormPreset(DailyCashEntry $entry): array
    {
        $c = $entry->category;
        if ($c === null || $c === '') {
            return ['preset' => 'none', 'custom' => ''];
        }
        if (str_starts_with($c, 'income:')) {
            return ['preset' => 'income_plus', 'custom' => substr($c, 7)];
        }
        if (str_starts_with($c, 'discretionary:')) {
            return ['preset' => 'discretionary_plus', 'custom' => substr($c, 14)];
        }
        if (str_starts_with($c, 'savings:')) {
            return ['preset' => 'savings_plus', 'custom' => substr($c, 8)];
        }

        return ['preset' => $c, 'custom' => ''];
    }

    public static function validatedFromRequest(Request $request): ?string
    {
        $type = (string) $request->input('type');
        $presetIn = $request->input('category_preset');
        if ($presetIn === null || $presetIn === '' || $presetIn === 'none') {
            return null;
        }
        if (in_array($presetIn, ['income_plus', 'discretionary_plus', 'savings_plus'], true)) {
            $request->validate(['category_custom_piece' => 'required|string|max:120']);

            return self::normalize($type, $presetIn, (string) $request->category_custom_piece);
        }
        if (! self::isAllowedPresetForType($type, $presetIn)) {
            throw ValidationException::withMessages([
                'category_preset' => ['This category does not apply to the selected entry type.'],
            ]);
        }

        return $presetIn;
    }
}
