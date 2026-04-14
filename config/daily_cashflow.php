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
            'income_water' => 'Income — Water',
            'income_farm' => 'Income — Farm',
            'cash_from_bank' => 'Cash from Bank — Withdrawals',
        ],
        'EXPENSES' => [
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
            'groceries_household' => 'Groceries / household (food-at-home)',
            'entertainment' => 'Entertainment — streaming, hobbies',
            'personal_care' => 'Personal care — haircut, gym',
            'gifts_donations' => 'Gifts & donations',
            'travel_vacation' => 'Travel & vacation',
        ],
        'SAVINGS' => [
            'cash_bank_investment' => 'Cash in bank / savings / investment',
            'savings_investment' => 'Savings / investment (reporting)',
        ],
        'CAPITAL' => [
            'capital_contribution' => 'Capital contribution',
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
                'label' => 'Groceries / household — food-at-home',
                'category_value' => 'groceries_household',
                'subcategory_keys' => ['groceries_household'],
            ],
            [
                'key' => 'entertainment',
                'label' => 'Entertainment — streaming, hobbies',
                'category_value' => 'entertainment',
                'subcategory_keys' => ['entertainment'],
            ],
            [
                'key' => 'personal_care',
                'label' => 'Personal care — haircut, gym',
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
                'key' => 'capital_main',
                'label' => 'Capital',
                'category_value' => 'capital_contribution',
                'subcategory_keys' => ['capital_contribution'],
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
            ['key' => 'capital_contribution', 'label' => 'Capital contribution', 'keywords' => ['capital', 'cash in', 'owner investment', 'additional capital']],
        ],

        'OTHER' => [],
    ],

];
