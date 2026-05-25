<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cashflow period start (month / day)
    |--------------------------------------------------------------------------
    |
    | Each new period begins on this date. Opening balance does not carry from
    | the day before the period start (e.g. no Feb 28 → Mar 1 carry). Within
    | the period, closing balance still carries to the next day that exists.
    |
    */

    'period_start_month' => 3,
    'period_start_day' => 1,

    /*
    |--------------------------------------------------------------------------
    | Category presets (stored in daily_cash_entries.category)
    |--------------------------------------------------------------------------
    |
    | Use "Income + …" / "Discretionary + …" / "Savings + …" via the dynamic
    | preset keys income_plus, discretionary_plus, savings_plus in forms.
    |
    */
    'category_presets' => [
        'INCOME' => [
            'income_water' => 'Water',
            'income_farm' => 'Farm',
            'cash_from_bank' => 'Cash from Bank — Withdrawals',
            // Metro daily worksheet (Income under INCOME: ASSORTED)
            'metro_income_suman' => 'Suman',
            'metro_income_others' => 'Others',
        ],
        'EXPENSES' => [
            // Metro daily worksheet — expense buckets / lines (labels align with worksheet Category column)
            'metro_exp_assorted_ingredients' => 'Ingredients',
            'metro_exp_assorted_cooking_fuel' => 'Cooking Fuel',
            'metro_exp_assorted_transportation' => 'Transportation',
            'metro_exp_assorted_electricity_water' => 'Electricity & Water',
            'metro_exp_assorted_salaries_wages' => 'Salary & Wages',
            'metro_exp_assorted_repair_maintenance' => 'Repair and Maintenance',
            'metro_exp_assorted_label_packaging' => 'Label & Packaging',
            'metro_exp_assorted_others' => 'Others',
            'metro_exp_water_supplies' => 'Supplies',
            'metro_exp_water_repairs_maintenance' => 'Repair & Maintenance',
            'metro_exp_water_electricity_water' => 'Electricity & Water',
            'metro_exp_water_salaries_wages' => 'Salary & Wages',
            'metro_exp_water_others' => 'Others',
            'metro_exp_farm_seedlings' => 'Seedlings',
            'metro_exp_farm_fertilizers' => 'Fertilizers / Herbicide',
            'metro_exp_farm_others' => 'Others',
            'metro_exp_suman_suman' => 'Suman',
            'metro_exp_suman_water' => 'Water',
            'metro_exp_suman_farm' => 'Farm',
            'metro_exp_suman_others' => 'Others',
            'utilities' => 'Utilities — electric, water, gas / LPG',
            'internet_phone' => 'Internet & phone — broadband, mobile plan',
            'transportation' => 'Transportation — fuel, commute, delivery, parking, tolls',
            'repairs_maintenance' => 'Repairs & maintenance — equipment, vehicle, building',
            'office_admin' => 'Office & admin — supplies, tools, postage, non-inventory software',
            'bank_fees' => 'Bank & fees — account, payment / merchant fees',
            'interest' => 'Interest — loan interest',
            'owner_compensation' => 'Owner pay / draw',
        ],
        'PURCHASES' => [
            'raw_materials' => 'Raw materials / supplies — ingredients, materials',
            'packaging' => 'Packaging — bags, boxes, labels',
            'merchandise' => 'Merchandise — goods for resale',
        ],
        'DISCRETIONARY' => [
            'home_sa_balay' => 'Sa balay / home & household (discretionary)',
            'dining_out' => 'Dining out',
            'groceries_household' => 'Groceries / household',
            'entertainment' => 'Entertainment',
            'personal_care' => 'Personal care',
            'gifts_donations' => 'Gifts & donations',
            'travel_vacation' => 'Travel & vacation',
            'metro_discretionary_others' => 'Others',
        ],
        'SAVINGS' => [
            'cash_bank_investment' => 'Cash in bank / savings / investment',
            'savings_investment' => 'Savings / investment (reporting)',
        ],
        'CAPITAL' => [
            'capital_contribution' => 'Cash',
            'cash_from_bank' => 'Cash from bank — withdrawals',
            'metro_capital_cfb_loan' => 'Loan',
            'metro_capital_cfb_others' => 'Others',
        ],

        /** Metro daily worksheet rows (stored on category, type OTHER). */
        'OTHER' => [
            'metro_other_misc' => 'Miscellaneous',
            'metro_other_others' => 'Others',
        ],

        /** Adjustment lines on the daily worksheet (per-group reconciliation + standalone group). Amounts may be negative. */
        'ADJUSTMENT' => [
            'adj_income_assorted' => 'Income: Assorted — Adjustments',
            'adj_exp_assorted' => 'Expense: Assorted — Adjustments',
            'adj_exp_water' => 'Expense: Water — Adjustments',
            'adj_exp_farm' => 'Expense: Farm — Adjustments',
            'adj_exp_suman' => 'Expense: Suman — Adjustments',
            'adj_others' => 'Adjustments — Others',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Add-entry UI: category groups → subcategory keys (monthly / annual reports)
    |--------------------------------------------------------------------------
    |
    | category_value: stored on daily_cash_entries.category when the user picks
    | a subcategory in this group (optional ledger tag aligned with presets).
    |
    */
    'category_form_groups' => [
        'INCOME' => [
            [
                'key' => 'income_class',
                'label' => 'Income — how earned (monthly subcategory)',
                'category_value' => null,
                'subcategory_keys' => ['sales', 'salary_wages', 'commissions', 'other_income'],
            ],
        ],
        'EXPENSES' => [
            [
                'key' => 'utilities',
                'label' => 'Utilities — electric, water, gas / LPG',
                'category_value' => 'utilities',
                'subcategory_keys' => ['utilities'],
            ],
            [
                'key' => 'internet_phone',
                'label' => 'Internet & phone — broadband, mobile plan',
                'category_value' => 'internet_phone',
                'subcategory_keys' => ['internet_phone'],
            ],
            [
                'key' => 'transportation',
                'label' => 'Transportation — fuel, commute, delivery, parking, tolls',
                'category_value' => 'transportation',
                'subcategory_keys' => ['transportation'],
            ],
            [
                'key' => 'repairs_maintenance',
                'label' => 'Repairs & maintenance — equipment, vehicle, building',
                'category_value' => 'repairs_maintenance',
                'subcategory_keys' => ['repairs_maintenance'],
            ],
            [
                'key' => 'office_admin',
                'label' => 'Office & admin — supplies, small tools, postage, non-inventory software',
                'category_value' => 'office_admin',
                'subcategory_keys' => ['office_admin'],
            ],
            [
                'key' => 'bank_fees',
                'label' => 'Bank & fees — account, payment / merchant fees',
                'category_value' => 'bank_fees',
                'subcategory_keys' => ['bank_fees'],
            ],
            [
                'key' => 'interest',
                'label' => 'Interest — loan interest',
                'category_value' => 'interest',
                'subcategory_keys' => ['interest'],
            ],
            [
                'key' => 'owner_compensation',
                'label' => 'Owner pay / draw',
                'category_value' => 'owner_compensation',
                'subcategory_keys' => ['owner_compensation'],
            ],
        ],
        'PURCHASES' => [
            [
                'key' => 'raw_materials',
                'label' => 'Raw materials / supplies — ingredients, materials',
                'category_value' => 'raw_materials',
                'subcategory_keys' => ['raw_materials'],
            ],
            [
                'key' => 'packaging',
                'label' => 'Packaging — bags, boxes, labels',
                'category_value' => 'packaging',
                'subcategory_keys' => ['packaging'],
            ],
            [
                'key' => 'merchandise',
                'label' => 'Merchandise — goods for resale',
                'category_value' => 'merchandise',
                'subcategory_keys' => ['merchandise'],
            ],
        ],
        'DISCRETIONARY' => [
            [
                'key' => 'dining_out',
                'label' => 'Dining out',
                'category_value' => 'dining_out',
                'subcategory_keys' => ['dining_out'],
            ],
            [
                'key' => 'groceries_household',
                'label' => 'Groceries / household',
                'category_value' => 'groceries_household',
                'subcategory_keys' => ['groceries_household'],
            ],
            [
                'key' => 'entertainment',
                'label' => 'Entertainment',
                'category_value' => 'entertainment',
                'subcategory_keys' => ['entertainment'],
            ],
            [
                'key' => 'personal_care',
                'label' => 'Personal care',
                'category_value' => 'personal_care',
                'subcategory_keys' => ['personal_care'],
            ],
            [
                'key' => 'gifts_donations',
                'label' => 'Gifts & donations',
                'category_value' => 'gifts_donations',
                'subcategory_keys' => ['gifts_donations'],
            ],
            [
                'key' => 'travel_vacation',
                'label' => 'Travel & vacation',
                'category_value' => 'travel_vacation',
                'subcategory_keys' => ['travel_vacation'],
            ],
        ],
        'SAVINGS' => [
            [
                'key' => 'savings_main',
                'label' => 'Savings / investment',
                'category_value' => 'savings_investment',
                'subcategory_keys' => ['savings_investment'],
            ],
        ],
        'CAPITAL' => [
            [
                'key' => 'capital_cash',
                'label' => 'Capital — Cash',
                'category_value' => 'capital_contribution',
                'subcategory_keys' => ['capital_contribution'],
            ],
            [
                'key' => 'capital_from_bank_line',
                'label' => 'Capital — Cash from bank',
                'category_value' => 'cash_from_bank',
                'subcategory_keys' => [
                    'cash_from_bank',
                    'metro_capital_cfb_loan',
                    'metro_capital_cfb_others',
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Subcategory lexicon (keyword → label for monthly/yearly reports)
    |--------------------------------------------------------------------------
    |
    | Owner pay / draw: use EXPENSES keywords (owner salary, draw, etc.) — not Income.
    |
    */
    'subcategory_lexicon' => [

        'INCOME' => [
            ['key' => 'commissions', 'label' => 'Commissions & bonuses', 'keywords' => ['commission', 'bonus', 'incentive']],
            ['key' => 'sales', 'label' => 'Sales — products/services', 'keywords' => ['sale', 'benta', 'customer', 'invoice', 'pos', 'order payment', 'product sold']],
            ['key' => 'salary_wages', 'label' => 'Salary / wages (external)', 'keywords' => ['salary', 'wages', 'sweldo', 'payroll in', 'employer']],
            ['key' => 'other_income', 'label' => 'Other income', 'keywords' => ['rebate', 'refund', 'misc income', 'interest earned', 'other income']],
        ],

        'EXPENSES' => [
            ['key' => 'owner_compensation', 'label' => 'Owner pay / draw', 'keywords' => ['owner salary', 'owner pay', 'owner draw', 'drawing', 'proprietor', 'sweldo owner', 'tag-iya', 'personal draw']],
            ['key' => 'interest', 'label' => 'Interest', 'keywords' => ['loan interest', 'interest expense', 'financing charge', 'interest payment']],
            ['key' => 'bank_fees', 'label' => 'Bank & fees', 'keywords' => ['bank fee', 'merchant fee', 'wire fee', 'atm fee', 'payment fee', 'transaction fee', 'service charge']],
            ['key' => 'internet_phone', 'label' => 'Internet & phone', 'keywords' => ['internet', 'wifi', 'wi-fi', 'broadband', 'pldt', 'converge', 'globe', 'smart', 'mobile plan', 'phone bill', 'prepaid load']],
            ['key' => 'utilities', 'label' => 'Utilities', 'keywords' => ['electric', 'electricity', 'meralco', 'water bill', 'maynilad', 'manila water', 'utility', 'utilities', 'sewer', 'lpg', 'gas bill']],
            ['key' => 'transportation', 'label' => 'Transportation', 'keywords' => ['fuel', 'gasoline', 'petrol', 'diesel', 'shell', 'petron', 'caltex', 'commute', 'delivery', 'parking', 'toll', 'grab', 'angkas', 'taxi', 'jeepney', 'transport']],
            ['key' => 'repairs_maintenance', 'label' => 'Repairs & maintenance', 'keywords' => ['repair', 'maintenance', 'fix', 'pms', 'overhaul', 'service center']],
            ['key' => 'office_admin', 'label' => 'Office & admin', 'keywords' => ['office', 'supplies', 'postage', 'software', 'subscription', 'admin', 'stationery', 'stamps']],
        ],

        'PURCHASES' => [
            ['key' => 'packaging', 'label' => 'Packaging', 'keywords' => ['packaging', 'label', 'pouch', 'carton']],
            ['key' => 'merchandise', 'label' => 'Merchandise', 'keywords' => ['merchandise', 'inventory', 'resale', 'wholesale', 'stock purchase']],
            ['key' => 'raw_materials', 'label' => 'Raw materials / supplies', 'keywords' => ['raw material', 'ingredient', 'rm ', 'material purchase', 'flour', 'sugar batch']],
        ],

        'DISCRETIONARY' => [
            ['key' => 'travel_vacation', 'label' => 'Travel & vacation', 'keywords' => ['travel', 'vacation', 'hotel', 'flight', 'resort', 'airbnb', 'booking']],
            ['key' => 'dining_out', 'label' => 'Dining out', 'keywords' => ['dining', 'restaurant', 'mcdo', 'jollibee', 'food delivery', 'grab food', 'coffee shop', 'kain']],
            ['key' => 'groceries_household', 'label' => 'Groceries / household', 'keywords' => ['grocery', 'supermarket', 'palengke', 'hypermarket', 'household']],
            ['key' => 'entertainment', 'label' => 'Entertainment', 'keywords' => ['netflix', 'spotify', 'movie', 'entertainment', 'hobby', 'games', 'streaming']],
            ['key' => 'personal_care', 'label' => 'Personal care', 'keywords' => ['salon', 'haircut', 'gym', 'spa', 'personal care']],
            ['key' => 'gifts_donations', 'label' => 'Gifts & donations', 'keywords' => ['gift', 'donation', 'charity', 'abuloy']],
        ],

        'SAVINGS' => [
            ['key' => 'savings_investment', 'label' => 'Savings / investment', 'keywords' => ['savings', 'investment', 'deposit', '401k', 'mutual', 'time deposit', 'bank transfer out']],
        ],

        'CAPITAL' => [
            ['key' => 'capital_contribution', 'label' => 'Cash', 'keywords' => ['capital', 'cash on hand', 'owner investment', 'till']],
            ['key' => 'cash_from_bank', 'label' => 'Cash from bank — withdrawals', 'keywords' => ['atm withdrawal', 'bank withdrawal', 'cash from bank']],
            ['key' => 'metro_capital_cfb_loan', 'label' => 'Loan', 'keywords' => ['loan draw', 'bank loan']],
            ['key' => 'metro_capital_cfb_others', 'label' => 'Others', 'keywords' => ['capital other', 'cfb other']],
        ],

        'OTHER' => [],

        'ADJUSTMENT' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Metro daily worksheet (fixed rows on Daily Cash — match client outline)
    |--------------------------------------------------------------------------
    |
    | Each line uses category_key on daily_cash_entries.category. Stubs are
    | created per day (amount 0) so the grid is always full.
    |
    */
    'metro_daily_sheet' => [
        ['kind' => 'heading', 'title' => 'CAPITAL'],
        ['kind' => 'line', 'type' => 'CAPITAL', 'category_key' => 'capital_contribution', 'category_display' => 'Cash'],
        ['kind' => 'line', 'type' => 'CAPITAL', 'category_key' => 'cash_from_bank', 'category_display' => 'Cash from bank — withdrawals'],
        ['kind' => 'line', 'type' => 'CAPITAL', 'category_key' => 'metro_capital_cfb_loan', 'category_display' => 'Loan'],
        ['kind' => 'line', 'type' => 'CAPITAL', 'category_key' => 'metro_capital_cfb_others', 'category_display' => 'Others'],

        ['kind' => 'heading', 'title' => 'INCOME: ASSORTED'],
        ['kind' => 'line', 'type' => 'INCOME', 'category_key' => 'metro_income_suman', 'category_display' => 'Suman'],
        ['kind' => 'line', 'type' => 'INCOME', 'category_key' => 'income_water', 'category_display' => 'Water'],
        ['kind' => 'line', 'type' => 'INCOME', 'category_key' => 'income_farm', 'category_display' => 'Farm'],
        ['kind' => 'line', 'type' => 'INCOME', 'category_key' => 'metro_income_others', 'category_display' => 'Others'],
        ['kind' => 'line', 'type' => 'ADJUSTMENT', 'category_key' => 'adj_income_assorted', 'category_display' => 'Adjustments'],

        ['kind' => 'heading', 'title' => 'EXPENSE: ASSORTED'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_assorted_ingredients', 'category_display' => 'Ingredients'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_assorted_cooking_fuel', 'category_display' => 'Cooking Fuel'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_assorted_transportation', 'category_display' => 'Transportation'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_assorted_electricity_water', 'category_display' => 'Electricity & Water'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_assorted_salaries_wages', 'category_display' => 'Salary & Wages'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_assorted_repair_maintenance', 'category_display' => 'Repair and Maintenance'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_assorted_label_packaging', 'category_display' => 'Label & Packaging'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_assorted_others', 'category_display' => 'Others'],
        ['kind' => 'line', 'type' => 'ADJUSTMENT', 'category_key' => 'adj_exp_assorted', 'category_display' => 'Adjustments'],

        ['kind' => 'heading', 'title' => 'EXPENSE: WATER'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_water_supplies', 'category_display' => 'Supplies'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_water_repairs_maintenance', 'category_display' => 'Repair & Maintenance'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_water_electricity_water', 'category_display' => 'Electricity & Water'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_water_salaries_wages', 'category_display' => 'Salary & Wages'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_water_others', 'category_display' => 'Others'],
        ['kind' => 'line', 'type' => 'ADJUSTMENT', 'category_key' => 'adj_exp_water', 'category_display' => 'Adjustments'],

        ['kind' => 'heading', 'title' => 'EXPENSE: FARM'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_farm_seedlings', 'category_display' => 'Seedlings'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_farm_fertilizers', 'category_display' => 'Fertilizers / Herbicide'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_farm_others', 'category_display' => 'Others'],
        ['kind' => 'line', 'type' => 'ADJUSTMENT', 'category_key' => 'adj_exp_farm', 'category_display' => 'Adjustments'],

        ['kind' => 'heading', 'title' => 'EXPENSE: SUMAN'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_suman_suman', 'category_display' => 'Suman'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_suman_water', 'category_display' => 'Water'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_suman_farm', 'category_display' => 'Farm'],
        ['kind' => 'line', 'type' => 'EXPENSES', 'category_key' => 'metro_exp_suman_others', 'category_display' => 'Others'],
        ['kind' => 'line', 'type' => 'ADJUSTMENT', 'category_key' => 'adj_exp_suman', 'category_display' => 'Adjustments'],

        ['kind' => 'heading', 'title' => 'DISCRETIONARY'],
        ['kind' => 'line', 'type' => 'DISCRETIONARY', 'category_key' => 'dining_out', 'category_display' => 'Dining out'],
        ['kind' => 'line', 'type' => 'DISCRETIONARY', 'category_key' => 'groceries_household', 'category_display' => 'Groceries / household'],
        ['kind' => 'line', 'type' => 'DISCRETIONARY', 'category_key' => 'entertainment', 'category_display' => 'Entertainment'],
        ['kind' => 'line', 'type' => 'DISCRETIONARY', 'category_key' => 'personal_care', 'category_display' => 'Personal care'],
        ['kind' => 'line', 'type' => 'DISCRETIONARY', 'category_key' => 'gifts_donations', 'category_display' => 'Gifts & donations'],
        ['kind' => 'line', 'type' => 'DISCRETIONARY', 'category_key' => 'travel_vacation', 'category_display' => 'Travel & vacation'],
        ['kind' => 'line', 'type' => 'DISCRETIONARY', 'category_key' => 'metro_discretionary_others', 'category_display' => 'Others'],

        ['kind' => 'heading', 'title' => 'SAVINGS'],
        ['kind' => 'line', 'type' => 'SAVINGS', 'category_key' => 'cash_bank_investment', 'category_display' => 'Cash in bank / savings / investment'],
        ['kind' => 'line', 'type' => 'SAVINGS', 'category_key' => 'savings_investment', 'category_display' => 'Savings / investment'],

        ['kind' => 'heading', 'title' => 'ADJUSTMENTS'],
        ['kind' => 'line', 'type' => 'ADJUSTMENT', 'category_key' => 'adj_others', 'category_display' => 'Others'],

        ['kind' => 'heading', 'title' => 'OTHER'],
        ['kind' => 'line', 'type' => 'OTHER', 'category_key' => 'metro_other_misc', 'category_display' => 'Miscellaneous'],
        ['kind' => 'line', 'type' => 'OTHER', 'category_key' => 'metro_other_others', 'category_display' => 'Others'],
    ],

];
