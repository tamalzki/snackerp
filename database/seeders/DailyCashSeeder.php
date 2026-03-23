<?php

namespace Database\Seeders;

use App\Models\DailyCashDay;
use App\Models\DailyCashEntry;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DailyCashSeeder extends Seeder
{
    public function run(): void
    {
        $days = [
            [
                'date'            => Carbon::today()->subDays(6)->toDateString(),
                'opening_balance' => 18000,
                'entries' => [
                    ['type' => 'CAPITAL',       'description' => 'SAVINGS WITHDRAWAL BDO',     'amount' => 4000],
                    ['type' => 'INCOME',        'description' => 'SUMAN',                       'amount' => 3850],
                    ['type' => 'INCOME',        'description' => 'WATER REFILL',                'amount' => 1305],
                    ['type' => 'INCOME',        'description' => 'ASSORTED PRODUCTS',           'amount' => 520],
                    ['type' => 'EXPENSES',      'description' => 'BBING SUMAN',                 'amount' => 1225],
                    ['type' => 'EXPENSES',      'description' => 'GAS - MULTI/REFUND/PDCB',     'amount' => 200],
                    ['type' => 'EXPENSES',      'description' => 'TRANSPORTATION & TRAVEL',     'amount' => 250],
                    ['type' => 'EXPENSES',      'description' => 'MARKET - BREAKFAST STAFF',    'amount' => 180],
                    ['type' => 'PURCHASES',     'description' => 'ASSRTD PRODS - OTHERS',       'amount' => 300],
                    ['type' => 'EXPENSES',      'description' => 'GROCERIES FAMILY NEEDS',      'amount' => 4220],
                    ['type' => 'EXPENSES',      'description' => 'LABOR',                       'amount' => 600],
                    ['type' => 'DISCRETIONARY', 'description' => 'HOME IMPROVEMENT - GAS MOWER','amount' => 1500],
                    ['type' => 'SAVINGS',       'description' => 'BDO CURRENT',                 'amount' => 5155],
                ],
            ],
            [
                'date'            => Carbon::today()->subDays(5)->toDateString(),
                'opening_balance' => 14545,
                'entries' => [
                    ['type' => 'INCOME',        'description' => 'SUMAN',                       'amount' => 4200],
                    ['type' => 'INCOME',        'description' => 'WATER REFILL',                'amount' => 980],
                    ['type' => 'EXPENSES',      'description' => 'BBING SUMAN',                 'amount' => 1400],
                    ['type' => 'EXPENSES',      'description' => 'COOKING OIL',                 'amount' => 280],
                    ['type' => 'EXPENSES',      'description' => 'CAMOTE 60 KLS @ 60',          'amount' => 3600],
                    ['type' => 'PURCHASES',     'description' => 'CHILI POWDER',                'amount' => 150],
                    ['type' => 'EXPENSES',      'description' => 'LABOR',                       'amount' => 600],
                    ['type' => 'DISCRETIONARY', 'description' => 'FOOD',                        'amount' => 350],
                    ['type' => 'SAVINGS',       'description' => 'BDO CURRENT',                 'amount' => 4000],
                ],
            ],
            [
                'date'            => Carbon::today()->subDays(4)->toDateString(),
                'opening_balance' => 9345,
                'entries' => [
                    ['type' => 'INCOME',        'description' => 'SUMAN',                       'amount' => 3650],
                    ['type' => 'INCOME',        'description' => 'WATER REFILL',                'amount' => 1100],
                    ['type' => 'INCOME',        'description' => 'ASSORTED PRODUCTS',           'amount' => 420],
                    ['type' => 'EXPENSES',      'description' => 'SOLLY SUMAN',                 'amount' => 800],
                    ['type' => 'EXPENSES',      'description' => 'DISHWASHING SUPPLIES',        'amount' => 120],
                    ['type' => 'EXPENSES',      'description' => 'TRANSPORTATION & TRAVEL',     'amount' => 200],
                    ['type' => 'PURCHASES',     'description' => 'ASSORTED PRODS INGREDIENTS',  'amount' => 500],
                    ['type' => 'EXPENSES',      'description' => 'GROCERIES BACKROOM NEEDS',    'amount' => 300],
                    ['type' => 'EXPENSES',      'description' => 'LABOR',                       'amount' => 600],
                    ['type' => 'DISCRETIONARY', 'description' => 'MERYENDA - BUKID/DIGOS',      'amount' => 250],
                    ['type' => 'SAVINGS',       'description' => 'BDO CURRENT',                 'amount' => 3500],
                ],
            ],
            [
                'date'            => Carbon::today()->subDays(3)->toDateString(),
                'opening_balance' => 8245,
                'entries' => [
                    ['type' => 'CAPITAL',       'description' => 'SAVINGS WITHDRAWAL BDO',     'amount' => 3000],
                    ['type' => 'INCOME',        'description' => 'SUMAN',                       'amount' => 4100],
                    ['type' => 'INCOME',        'description' => 'WATER REFILL',                'amount' => 1250],
                    ['type' => 'EXPENSES',      'description' => 'BBING SUMAN',                 'amount' => 1300],
                    ['type' => 'EXPENSES',      'description' => 'FLAVOR - CA CHEN',            'amount' => 180],
                    ['type' => 'EXPENSES',      'description' => 'PAMINTA',                     'amount' => 60],
                    ['type' => 'PURCHASES',     'description' => 'KITCHEN NEEDS',               'amount' => 450],
                    ['type' => 'EXPENSES',      'description' => 'LABOR',                       'amount' => 600],
                    ['type' => 'DISCRETIONARY', 'description' => 'HOME NEEDS - GARBAGE BAG',    'amount' => 150],
                    ['type' => 'SAVINGS',       'description' => 'BDO CURRENT',                 'amount' => 6195],
                ],
            ],
            [
                'date'            => Carbon::today()->subDays(2)->toDateString(),
                'opening_balance' => 7910,
                'entries' => [
                    ['type' => 'INCOME',        'description' => 'SUMAN',                       'amount' => 3900],
                    ['type' => 'INCOME',        'description' => 'WATER REFILL',                'amount' => 1050],
                    ['type' => 'EXPENSES',      'description' => 'MARKET - BREAKFAST STAFF',    'amount' => 200],
                    ['type' => 'EXPENSES',      'description' => 'BUTCHERON 60 KLS @ 110',      'amount' => 6600],
                    ['type' => 'EXPENSES',      'description' => 'TIP HATUB',                   'amount' => 100],
                    ['type' => 'EXPENSES',      'description' => 'ADD SWELDO LADY WORKER',      'amount' => 500],
                    ['type' => 'EXPENSES',      'description' => 'LABOR',                       'amount' => 600],
                    ['type' => 'DISCRETIONARY', 'description' => 'DISCRETIONARY',               'amount' => 400],
                    ['type' => 'SAVINGS',       'description' => 'BDO CURRENT',                 'amount' => 2000],
                ],
            ],
            [
                'date'            => Carbon::yesterday()->toDateString(),
                'opening_balance' => 3460,
                'entries' => [
                    ['type' => 'INCOME',        'description' => 'SUMAN',                       'amount' => 4500],
                    ['type' => 'INCOME',        'description' => 'WATER REFILL',                'amount' => 1200],
                    ['type' => 'INCOME',        'description' => 'ASSORTED PRODUCTS',           'amount' => 600],
                    ['type' => 'EXPENSES',      'description' => 'BBING SUMAN',                 'amount' => 1500],
                    ['type' => 'EXPENSES',      'description' => 'COOKING OIL',                 'amount' => 260],
                    ['type' => 'PURCHASES',     'description' => 'ASSORTED PRODS - OTHERS',     'amount' => 700],
                    ['type' => 'EXPENSES',      'description' => 'GROCERIES FAMILY NEEDS',      'amount' => 3500],
                    ['type' => 'EXPENSES',      'description' => 'LABOR',                       'amount' => 600],
                    ['type' => 'DISCRETIONARY', 'description' => 'DISCRETIONARY',               'amount' => 300],
                    ['type' => 'SAVINGS',       'description' => 'BDO CURRENT',                 'amount' => 3000],
                ],
            ],
            [
                'date'            => Carbon::today()->toDateString(),
                'opening_balance' => 900,
                'entries' => [
                    ['type' => 'INCOME',        'description' => 'SUMAN',                       'amount' => 3750],
                    ['type' => 'INCOME',        'description' => 'WATER REFILL',                'amount' => 950],
                    ['type' => 'EXPENSES',      'description' => 'BBING SUMAN',                 'amount' => 1100],
                    ['type' => 'EXPENSES',      'description' => 'LABOR',                       'amount' => 600],
                ],
            ],
        ];

        foreach ($days as $dayData) {
            $day = DailyCashDay::firstOrCreate(
                ['date' => $dayData['date']],
                ['opening_balance' => $dayData['opening_balance']]
            );

            foreach ($dayData['entries'] as $i => $e) {
                DailyCashEntry::firstOrCreate(
                    [
                        'daily_cash_day_id' => $day->id,
                        'type'              => $e['type'],
                        'description'       => $e['description'],
                    ],
                    ['amount' => $e['amount'], 'sort_order' => $i + 1]
                );
            }
        }
    }
}
