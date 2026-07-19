<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\LoyaltyTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PointsAccumulationTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected Customer $customer;
    protected Branch $branchColombo;
    protected Branch $branchKandy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cashier = User::factory()->create(['role' => 'cashier']);
        
        $this->customer = Customer::create([
            'nic_passport' => '199512345678',
            'mobile_number' => '0773456789',
            'name' => 'John Doe',
            'status' => 'active',
            'registered_by' => $this->cashier->id,
        ]);

        $this->branchColombo = Branch::create(['name' => 'Colombo Central', 'code' => 'COL01']);
        $this->branchKandy = Branch::create(['name' => 'Kandy City', 'code' => 'KAN02']);
    }

    /* Test capturing order under 10k LKR earns 0 points */
    public function test_order_under_threshold_earns_zero_points(): void
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/orders', [
                'nic_passport' => '199512345678',
                'invoice_number' => 'INV-999',
                'branch_code' => 'COL01',
                'transaction_date' => '2026-07-19',
                'amount' => 9999.99,
            ]);

        $response->assertStatus(201);

        // Assert order exists but no loyalty transaction was written
        $this->assertDatabaseHas('orders', [
            'invoice_number' => 'INV-999',
            'amount' => 9999.99,
        ]);

        $this->assertDatabaseMissing('loyalty_transactions', [
            'customer_id' => $this->customer->id,
        ]);
    }

    /* Test capturing order over 10k LKR calculates points correctly */
    public function test_eligible_order_earns_correct_points(): void
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/orders', [
                'nic_passport' => '199512345678',
                'invoice_number' => 'INV-1000',
                'branch_code' => 'COL01',
                'transaction_date' => '2026-07-19',
                'amount' => 15550.00,
            ]);

        $response->assertStatus(201);

        // Verify order capture
        $this->assertDatabaseHas('orders', [
            'invoice_number' => 'INV-1000',
            'amount' => 15550.00,
        ]);

        // Verify points ledger entry: 15,550 LKR -> 155 points (1 point per 100 LKR)
        $this->assertDatabaseHas('loyalty_transactions', [
            'customer_id' => $this->customer->id,
            'points' => 155,
            'type' => 'earn',
        ]);

        // Ensure exactly one transaction was recorded
        $this->assertEquals(1, LoyaltyTransaction::count());
    }

    /* Test duplicate invoice is blocked within the same branch */
    public function test_duplicate_invoice_blocked_same_branch(): void
    {
        // Create first order
        $this->actingAs($this->cashier)
            ->postJson('/api/v1/orders', [
                'nic_passport' => '199512345678',
                'invoice_number' => 'INV-DUP',
                'branch_code' => 'COL01',
                'transaction_date' => '2026-07-19',
                'amount' => 12000.00,
            ]);

        // Attempt same invoice, same branch
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/orders', [
                'nic_passport' => '199512345678',
                'invoice_number' => 'INV-DUP',
                'branch_code' => 'COL01',
                'transaction_date' => '2026-07-19',
                'amount' => 15000.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['invoice_number']);
    }

    /* Test same invoice is allowed across different branches */
    public function test_same_invoice_allowed_different_branches(): void
    {
        // Branch 1 order
        $this->actingAs($this->cashier)
            ->postJson('/api/v1/orders', [
                'nic_passport' => '199512345678',
                'invoice_number' => 'INV-DIFF',
                'branch_code' => 'COL01',
                'transaction_date' => '2026-07-19',
                'amount' => 12000.00,
            ])->assertStatus(201);

        // Branch 2 order
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/orders', [
                'nic_passport' => '199512345678',
                'invoice_number' => 'INV-DIFF',
                'branch_code' => 'KAN02',
                'transaction_date' => '2026-07-19',
                'amount' => 15000.00,
            ]);

        $response->assertStatus(201);
    }

    /* Test cashier can retrieve all captured orders paginated */
    public function test_cashier_can_retrieve_paginated_orders(): void
    {
        \App\Models\Order::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-PAG',
            'branch_id' => $this->branchColombo->id,
            'transaction_date' => '2026-07-19',
            'amount' => 5000.00,
        ]);

        $response = $this->actingAs($this->cashier)
            ->getJson('/api/v1/orders');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'orders' => [
                    'data' => [
                        '*' => ['id', 'invoice_number', 'branch', 'amount', 'transaction_date']
                    ],
                    'links',
                    'meta'
                ]
            ]);
    }

    /* Test order capture auto-generates invoice number sequentially if not supplied */
    public function test_order_can_auto_generate_invoice_number(): void
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/orders', [
                'nic_passport' => '199512345678',
                'branch_code' => 'COL01',
                'transaction_date' => '2026-07-19',
                'amount' => 12000.00,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('order.invoice_number', 'INV-00001');

        // Post second order without invoice number, should be INV-00002
        $response2 = $this->actingAs($this->cashier)
            ->postJson('/api/v1/orders', [
                'nic_passport' => '199512345678',
                'branch_code' => 'COL01',
                'transaction_date' => '2026-07-19',
                'amount' => 15000.00,
            ]);

        $response2->assertStatus(201)
            ->assertJsonPath('order.invoice_number', 'INV-00002');
    }

    /* Test CalculateLoyaltyPoints listener is idempotent and does not award points twice on retry */
    public function test_points_listener_is_idempotent(): void
    {
        $order = \App\Models\Order::create([
            'customer_id' => $this->customer->id,
            'invoice_number' => 'INV-IDEMP',
            'branch_id' => $this->branchColombo->id,
            'transaction_date' => '2026-07-19',
            'amount' => 15000.00,
        ]);

        $event = new \App\Events\OrderCreated($order);
        $listener = resolve(\App\Listeners\CalculateLoyaltyPoints::class);

        // Run handler first time
        $listener->handle($event);
        $this->assertEquals(1, \App\Models\LoyaltyTransaction::where('order_id', $order->id)->count());

        // Run handler second time (simulating a queue retry)
        $listener->handle($event);
        $this->assertEquals(1, \App\Models\LoyaltyTransaction::where('order_id', $order->id)->count());
    }
}
