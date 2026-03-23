<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\CashAccount;
use App\Models\FinishedProduct;
use App\Models\RawMaterial;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin User ────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@snackerp.com'],
            [
                'name'     => 'Admin',
                'email'    => 'admin@snackerp.com',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ]
        );

        // ── Branches ──────────────────────────────────────────
        Branch::updateOrCreate(
            ['name' => 'Mers Badjang'],
            [
                'name'      => 'Mers Badjang',
                'address'   => 'Badjang',
                'is_active' => true,
            ]
        );

        Branch::updateOrCreate(
            ['name' => 'Mers Main'],
            [
                'name'      => 'Mers Main',
                'address'   => 'Main Branch',
                'is_active' => true,
            ]
        );

        // ── Cash Account ──────────────────────────────────────
        CashAccount::updateOrCreate(
            ['name' => 'Main Cash Fund'],
            [
                'name'    => 'Main Cash Fund',
                'balance' => 0,
                'notes'   => 'Primary cash account',
            ]
        );

        // ── Raw Materials ─────────────────────────────────────
        $rawMaterials = [
            // Ingredients
            ['name' => 'Durian Pulp',         'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 180.00, 'stock_quantity' => 500,  'low_stock_threshold' => 50],
            ['name' => 'All Purpose Flour',   'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 45.00,  'stock_quantity' => 800,  'low_stock_threshold' => 100],
            ['name' => 'Sugar',               'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 55.00,  'stock_quantity' => 600,  'low_stock_threshold' => 80],
            ['name' => 'Butter',              'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 320.00, 'stock_quantity' => 200,  'low_stock_threshold' => 30],
            ['name' => 'Cream Cheese',        'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 450.00, 'stock_quantity' => 150,  'low_stock_threshold' => 20],
            ['name' => 'Mango Puree',         'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 120.00, 'stock_quantity' => 300,  'low_stock_threshold' => 40],
            ['name' => 'Ube Extract',         'unit' => 'liters', 'category' => 'ingredients', 'cost_per_unit' => 280.00, 'stock_quantity' => 100,  'low_stock_threshold' => 15],
            ['name' => 'Strawberry Puree',    'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 130.00, 'stock_quantity' => 200,  'low_stock_threshold' => 25],
            ['name' => 'Funfetti Sprinkles',  'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 220.00, 'stock_quantity' => 80,   'low_stock_threshold' => 10],
            ['name' => 'Peanuts',             'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 90.00,  'stock_quantity' => 300,  'low_stock_threshold' => 50],
            ['name' => 'Vanilla Extract',     'unit' => 'liters', 'category' => 'ingredients', 'cost_per_unit' => 350.00, 'stock_quantity' => 50,   'low_stock_threshold' => 10],
            ['name' => 'Buko (Young Coconut)','unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 60.00,  'stock_quantity' => 400,  'low_stock_threshold' => 50],
            ['name' => 'Pinipig',             'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 85.00,  'stock_quantity' => 150,  'low_stock_threshold' => 20],
            ['name' => 'Cookies Crumbs',      'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 160.00, 'stock_quantity' => 120,  'low_stock_threshold' => 15],
            ['name' => 'Condensed Milk',      'unit' => 'pcs',    'category' => 'ingredients', 'cost_per_unit' => 45.00,  'stock_quantity' => 500,  'low_stock_threshold' => 60],
            ['name' => 'Eggs',                'unit' => 'pcs',    'category' => 'ingredients', 'cost_per_unit' => 8.00,   'stock_quantity' => 1000, 'low_stock_threshold' => 100],
            ['name' => 'Baking Powder',       'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 120.00, 'stock_quantity' => 50,   'low_stock_threshold' => 10],
            ['name' => 'Cornstarch',          'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 65.00,  'stock_quantity' => 100,  'low_stock_threshold' => 15],
            ['name' => 'Yema Filling',        'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 200.00, 'stock_quantity' => 150,  'low_stock_threshold' => 20],
            ['name' => 'Jam Base',            'unit' => 'kg',     'category' => 'ingredients', 'cost_per_unit' => 140.00, 'stock_quantity' => 200,  'low_stock_threshold' => 25],
            ['name' => 'Pineapple Extract',   'unit' => 'liters', 'category' => 'ingredients', 'cost_per_unit' => 180.00, 'stock_quantity' => 80,   'low_stock_threshold' => 10],

            // Packaging
            ['name' => 'Scotch Box (Small)',  'unit' => 'pcs', 'category' => 'packaging', 'cost_per_unit' => 8.00,   'stock_quantity' => 3000, 'low_stock_threshold' => 300],
            ['name' => 'Scotch Box (Medium)', 'unit' => 'pcs', 'category' => 'packaging', 'cost_per_unit' => 12.00,  'stock_quantity' => 2000, 'low_stock_threshold' => 200],
            ['name' => 'Roll Box',            'unit' => 'pcs', 'category' => 'packaging', 'cost_per_unit' => 10.00,  'stock_quantity' => 2000, 'low_stock_threshold' => 200],
            ['name' => 'Barquiron Wrapper',   'unit' => 'pcs', 'category' => 'packaging', 'cost_per_unit' => 5.00,   'stock_quantity' => 5000, 'low_stock_threshold' => 500],
            ['name' => 'Jam Jar (Small)',     'unit' => 'pcs', 'category' => 'packaging', 'cost_per_unit' => 15.00,  'stock_quantity' => 1000, 'low_stock_threshold' => 100],
            ['name' => 'Tower Box',           'unit' => 'pcs', 'category' => 'packaging', 'cost_per_unit' => 25.00,  'stock_quantity' => 1000, 'low_stock_threshold' => 100],
            ['name' => 'Yema Wrapper',        'unit' => 'pcs', 'category' => 'packaging', 'cost_per_unit' => 3.00,   'stock_quantity' => 5000, 'low_stock_threshold' => 500],
            ['name' => 'Sticker Labels',      'unit' => 'pcs', 'category' => 'packaging', 'cost_per_unit' => 2.00,   'stock_quantity' => 5000, 'low_stock_threshold' => 500],
            ['name' => 'Shrink Wrap',         'unit' => 'pcs', 'category' => 'packaging', 'cost_per_unit' => 120.00, 'stock_quantity' => 50,   'low_stock_threshold' => 5],
        ];

        foreach ($rawMaterials as $rm) {
            RawMaterial::updateOrCreate(
                ['name' => $rm['name']],
                $rm
            );
        }

        // ── Finished Products ─────────────────────────────────
        $finishedProducts = [
            // SCOTCH
            ['name' => 'Durian Scotch',        'selling_price' => 250.00, 'average_cost' => 130.00, 'current_stock' => 500, 'low_stock_threshold' => 50],
            ['name' => 'Funfetti Scotch',       'selling_price' => 220.00, 'average_cost' => 110.00, 'current_stock' => 400, 'low_stock_threshold' => 50],
            ['name' => 'Cheese Scotch',         'selling_price' => 230.00, 'average_cost' => 115.00, 'current_stock' => 400, 'low_stock_threshold' => 50],
            ['name' => 'Mango Scotch',          'selling_price' => 220.00, 'average_cost' => 105.00, 'current_stock' => 400, 'low_stock_threshold' => 50],
            ['name' => 'Bundle Scotch',         'selling_price' => 350.00, 'average_cost' => 180.00, 'current_stock' => 300, 'low_stock_threshold' => 30],

            // ROLLS
            ['name' => 'Pine Rolls',            'selling_price' => 180.00, 'average_cost' => 90.00,  'current_stock' => 500, 'low_stock_threshold' => 50],
            ['name' => 'Durian Rolls',          'selling_price' => 200.00, 'average_cost' => 100.00, 'current_stock' => 500, 'low_stock_threshold' => 50],
            ['name' => 'Mango Rolls',           'selling_price' => 180.00, 'average_cost' => 88.00,  'current_stock' => 500, 'low_stock_threshold' => 50],
            ['name' => 'Ube Rolls',             'selling_price' => 190.00, 'average_cost' => 95.00,  'current_stock' => 400, 'low_stock_threshold' => 50],

            // BARQUIRON
            ['name' => 'Peanut Barquiron',      'selling_price' => 150.00, 'average_cost' => 70.00,  'current_stock' => 800, 'low_stock_threshold' => 80],
            ['name' => 'Vanilla Barquiron',     'selling_price' => 150.00, 'average_cost' => 72.00,  'current_stock' => 800, 'low_stock_threshold' => 80],
            ['name' => 'Durian Barquiron',      'selling_price' => 180.00, 'average_cost' => 90.00,  'current_stock' => 700, 'low_stock_threshold' => 80],
            ['name' => 'Ube Barquiron',         'selling_price' => 160.00, 'average_cost' => 78.00,  'current_stock' => 700, 'low_stock_threshold' => 80],
            ['name' => 'Strawberry Barquiron',  'selling_price' => 155.00, 'average_cost' => 75.00,  'current_stock' => 700, 'low_stock_threshold' => 80],
            ['name' => 'Cookies Barquiron',     'selling_price' => 160.00, 'average_cost' => 80.00,  'current_stock' => 600, 'low_stock_threshold' => 60],
            ['name' => 'Pinipig Barquiron',     'selling_price' => 155.00, 'average_cost' => 75.00,  'current_stock' => 600, 'low_stock_threshold' => 60],
            ['name' => 'Mango Barquiron',       'selling_price' => 160.00, 'average_cost' => 78.00,  'current_stock' => 600, 'low_stock_threshold' => 60],
            ['name' => 'Buko Barquiron',        'selling_price' => 150.00, 'average_cost' => 70.00,  'current_stock' => 600, 'low_stock_threshold' => 60],

            // JAM & SPREADS
            ['name' => 'Durian Jam',            'selling_price' => 280.00, 'average_cost' => 140.00, 'current_stock' => 300, 'low_stock_threshold' => 30],
            ['name' => 'Durian Yema Spread',    'selling_price' => 300.00, 'average_cost' => 150.00, 'current_stock' => 300, 'low_stock_threshold' => 30],
            ['name' => 'Yema Spread',           'selling_price' => 250.00, 'average_cost' => 120.00, 'current_stock' => 300, 'low_stock_threshold' => 30],
            ['name' => 'Durian Pastiyema',      'selling_price' => 320.00, 'average_cost' => 160.00, 'current_stock' => 200, 'low_stock_threshold' => 20],

            // TOWERS
            ['name' => 'Durian Tower',          'selling_price' => 450.00, 'average_cost' => 220.00, 'current_stock' => 200, 'low_stock_threshold' => 20],
            ['name' => 'Strawberry Tower',      'selling_price' => 420.00, 'average_cost' => 200.00, 'current_stock' => 200, 'low_stock_threshold' => 20],
            ['name' => 'Buko Tower',            'selling_price' => 400.00, 'average_cost' => 190.00, 'current_stock' => 200, 'low_stock_threshold' => 20],
            ['name' => 'Ube Tower',             'selling_price' => 430.00, 'average_cost' => 210.00, 'current_stock' => 200, 'low_stock_threshold' => 20],
            ['name' => 'Mango Tower',           'selling_price' => 420.00, 'average_cost' => 200.00, 'current_stock' => 200, 'low_stock_threshold' => 20],
        ];

        foreach ($finishedProducts as $fp) {
            FinishedProduct::updateOrCreate(
                ['name' => $fp['name']],
                [
                    'name'                => $fp['name'],
                    'type'                => 'manufactured',
                    'selling_price'       => $fp['selling_price'],
                    'average_cost'        => $fp['average_cost'],
                    'current_stock'       => $fp['current_stock'],
                    'low_stock_threshold' => $fp['low_stock_threshold'],
                ]
            );
        }

        $this->command->info('✅ Demo data seeded successfully!');
        $this->command->info('   Admin: admin@snackerp.com / password');
        $this->command->info('   Branches: Mers Badjang, Mers Main');
        $this->command->info('   Cash Account: Main Cash Fund');
        $this->command->info('   Raw Materials: ' . RawMaterial::count());
        $this->command->info('   Finished Products: ' . FinishedProduct::count());
    }
}