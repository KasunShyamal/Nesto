<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Staff (Admin & Cashiers)
        $admin = User::create([
            'name' => 'Nesto Admin',
            'email' => 'admin@nesto.lk',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $cashier = User::create([
            'name' => 'Cashier 01',
            'email' => 'cashier01@nesto.lk',
            'password' => Hash::make('password'),
            'role' => 'cashier',
        ]);

        // 2. Create Branches
        $colombo = Branch::create(['name' => 'Colombo Central', 'code' => 'COL01']);
        $kandy = Branch::create(['name' => 'Kandy City', 'code' => 'KAN02']);
        $galle = Branch::create(['name' => 'Galle Harbor', 'code' => 'GAL03']);

        // 3. Create Customers
        // Customer 1: Registered & Activated
        $userCustomer1 = User::create([
            'name' => 'Kasun Shyamal',
            'email' => 'kasun@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $customer1 = Customer::create([
            'user_id' => $userCustomer1->id,
            'nic_passport' => '199501201452',
            'mobile_number' => '0771234567',
            'name' => 'Kasun Shyamal',
            'status' => 'active',
            'registered_by' => $cashier->id,
            'activated_at' => now(),
        ]);

        // Customer 2: Pending activation (no user account associated yet)
        $customer2 = Customer::create([
            'user_id' => null,
            'nic_passport' => '199806158521',
            'mobile_number' => '0719876543',
            'name' => 'Nimal Perera',
            'status' => 'pending',
            'registered_by' => $cashier->id,
        ]);

        // Customer 3: Active customer with multiple orders and loyalty transactions
        $userCustomer3 = User::create([
            'name' => 'Dilshan Silva',
            'email' => 'dilshan@gmail.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $customer3 = Customer::create([
            'user_id' => $userCustomer3->id,
            'nic_passport' => '199008234852',
            'mobile_number' => '0754567890',
            'name' => 'Dilshan Silva',
            'status' => 'active',
            'registered_by' => $cashier->id,
            'activated_at' => now()->subDays(5),
        ]);

        // 4. Create Orders for Customer 3
        // Order A: Eligible (15,500 LKR -> 155 points)
        $orderA = Order::create([
            'customer_id' => $customer3->id,
            'invoice_number' => 'INV-0001',
            'branch_id' => $colombo->id,
            'transaction_date' => now()->subDays(3)->toDateString(),
            'amount' => 15500.00,
        ]);

        LoyaltyTransaction::create([
            'customer_id' => $customer3->id,
            'order_id' => $orderA->id,
            'points' => 155,
            'type' => 'earn',
            'description' => 'Earned points from Invoice INV-0001 at Colombo Central',
        ]);

        // Order B: Ineligible (8,500 LKR -> 0 points)
        Order::create([
            'customer_id' => $customer3->id,
            'invoice_number' => 'INV-0002',
            'branch_id' => $colombo->id,
            'transaction_date' => now()->subDays(2)->toDateString(),
            'amount' => 8500.00,
        ]);

        // Order C: Eligible (24,000 LKR -> 240 points)
        $orderC = Order::create([
            'customer_id' => $customer3->id,
            'invoice_number' => 'INV-0003',
            'branch_id' => $kandy->id,
            'transaction_date' => now()->subDays(1)->toDateString(),
            'amount' => 24000.00,
        ]);

        LoyaltyTransaction::create([
            'customer_id' => $customer3->id,
            'order_id' => $orderC->id,
            'points' => 240,
            'type' => 'earn',
            'description' => 'Earned points from Invoice INV-0003 at Kandy City',
        ]);
    }
}
