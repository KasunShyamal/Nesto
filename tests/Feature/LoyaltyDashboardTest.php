<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoyaltyDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $customerUser;
    protected Customer $customer;
    protected User $cashier;
    protected Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->create(['role' => 'cashier']);
        $this->branch = Branch::create(['name' => 'Colombo Central', 'code' => 'COL01']);

        // Create active customer
        $this->customerUser = User::factory()->create([
            'role' => 'customer',
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'nic_passport' => '199512345678',
            'mobile_number' => '0773456789',
            'name' => 'John Doe',
            'status' => 'active',
            'registered_by' => $this->cashier->id,
        ]);
    }

    /* Test balance retrieval and transaction updates */
    public function test_customer_can_retrieve_loyalty_balance(): void
    {
        // 1. Initial balance should be 0
        $response = $this->actingAs($this->customerUser)
            ->getJson('/api/v1/loyalty/balance');

        $response->assertStatus(200)
            ->assertJsonPath('points_balance', 0);

        // 2. Add some points via order
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-200',
            'branch_id' => $this->branch->id,
            'transaction_date' => '2026-07-19',
            'amount' => 15000.00,
        ]);

        LoyaltyTransaction::create([
            'customer_id' => $this->customer->id,
            'order_id' => $order->id,
            'points' => 150,
            'type' => 'earn',
            'description' => 'Earned points',
        ]);

        // 3. Balance should be 150 now
        $response = $this->actingAs($this->customerUser)
            ->getJson('/api/v1/loyalty/balance');

        $response->assertStatus(200)
            ->assertJsonPath('points_balance', 150);
    }

    /* Test transaction history list */
    public function test_customer_can_retrieve_transaction_history(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-201',
            'branch_id' => $this->branch->id,
            'transaction_date' => '2026-07-19',
            'amount' => 20000.00,
        ]);

        LoyaltyTransaction::create([
            'customer_id' => $this->customer->id,
            'order_id' => $order->id,
            'points' => 200,
            'type' => 'earn',
            'description' => 'Earned points from Colombo Central',
        ]);

        $response = $this->actingAs($this->customerUser)
            ->getJson('/api/v1/loyalty/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'transactions' => [
                    'data' => [
                        '*' => ['id', 'points', 'type', 'description', 'order', 'created_at']
                    ],
                    'links',
                    'meta'
                ]
            ])
            ->assertJsonPath('transactions.data.0.points', 200);
    }

    /* Test unified dashboard */
    public function test_customer_can_retrieve_dashboard_summary(): void
    {
        $order = Order::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-202',
            'branch_id' => $this->branch->id,
            'transaction_date' => '2026-07-19',
            'amount' => 20000.00,
        ]);

        LoyaltyTransaction::create([
            'customer_id' => $this->customer->id,
            'order_id' => $order->id,
            'points' => 200,
            'type' => 'earn',
            'description' => 'Earned points from Colombo Central',
        ]);

        $response = $this->actingAs($this->customerUser)
            ->getJson('/api/v1/loyalty/dashboard');

        $response->assertStatus(200)
            ->assertJsonPath('customer_name', 'John Doe')
            ->assertJsonPath('points_balance', 200)
            ->assertJsonStructure([
                'customer_name',
                'points_balance',
                'recent_transactions' => ['data'],
                'recent_orders' => ['data']
            ]);
    }

    /* Test cashier/staff cannot access dashboard endpoints */
    public function test_staff_cannot_access_customer_dashboard(): void
    {
        $response = $this->actingAs($this->cashier)
            ->getJson('/api/v1/loyalty/dashboard');

        $response->assertStatus(403);
    }
}
