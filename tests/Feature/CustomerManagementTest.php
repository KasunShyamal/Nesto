<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CustomerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $admin;
    protected User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Create base users for testing
        $this->cashier = User::factory()->create([
            'role' => 'cashier',
        ]);

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->otherUser = User::factory()->create([
            'role' => 'customer',
        ]);
    }

    /* Test a cashier can register a customer*/
    public function test_cashier_can_register_customer(): void
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/customers', [
                'nic_passport' => '199512345678',
                'mobile_number' => '0773456789',
                'name' => 'John Doe',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'message',
                'customer' => ['id', 'nic_passport', 'mobile_number', 'name', 'status']
            ]);

        $this->assertDatabaseHas('customers', [
            'nic_passport' => '199512345678',
            'status' => 'pending',
            'registered_by' => $this->cashier->id,
        ]);
    }

    /* Test validation rules for registration (Sri Lankan phone and NIC formats)*/
    public function test_customer_registration_validates_sri_lankan_formats(): void
    {
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/customers', [
                'nic_passport' => 'invalid-nic-format',
                'mobile_number' => '12345',
                'name' => 'J',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['nic_passport', 'mobile_number', 'name']);
    }

    /* Test a customer role cannot register other customers*/
    public function test_customer_cannot_register_customer(): void
    {
        $response = $this->actingAs($this->otherUser)
            ->postJson('/api/v1/customers', [
                'nic_passport' => '199512345678',
                'mobile_number' => '0773456789',
                'name' => 'John Doe',
            ]);

        $response->assertStatus(403);
    }

    /* Test account activation*/
    public function test_customer_can_activate_account(): void
    {
        // 1. First register a pending customer
        $customer = Customer::create([
            'nic_passport' => '199512345678',
            'mobile_number' => '0773456789',
            'name' => 'John Doe',
            'status' => 'pending',
            'registered_by' => $this->cashier->id,
        ]);

        // 2. Perform activation
        $response = $this->postJson('/api/v1/customers/activate', [
            'nic_passport' => '199512345678',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('customer.status', 'active');

        // Verify user account was created and linked
        $user = User::where('email', 'john@example.com')->first();
        $this->assertNotNull($user);
        $this->assertEquals('customer', $user->role);
        $this->assertEquals($user->id, $customer->fresh()->user_id);
        $this->assertEquals('active', $customer->fresh()->status);
        $this->assertNotNull($customer->fresh()->activated_at);
    }

    /* Test that  inactivated customer cannot log in*/
    public function test_unactivated_customer_cannot_login(): void
    {
        // Register but do not activate (so no User record exists, or if a user record does exist but status is pending)
        $customer = Customer::create([
            'nic_passport' => '199512345678',
            'mobile_number' => '0773456789',
            'name' => 'John Doe',
            'status' => 'pending',
            'registered_by' => $this->cashier->id,
        ]);

        // Create a User record manually linked to it but keep customer status as pending
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
        ]);
        $customer->update(['user_id' => $user->id]);

        // Try to log in
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403)
            ->assertJsonPath('message', 'Your account is pending activation. Please activate your profile using your NIC/Passport first.');
    }

    /* Test login and profile endpoint*/
    public function test_active_customer_can_login_and_retrieve_profile(): void
    {
        $user = User::create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => Hash::make('password123'),
            'role' => 'customer',
        ]);

        $customer = Customer::create([
            'user_id' => $user->id,
            'nic_passport' => '199512345678',
            'mobile_number' => '0773456789',
            'name' => 'John Doe',
            'status' => 'active',
            'registered_by' => $this->cashier->id,
            'activated_at' => now(),
        ]);

        // 1. Log in
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'john@example.com',
            'password' => 'password123',
        ]);

        $loginResponse->assertStatus(200)
            ->assertJsonStructure(['access_token', 'user']);

        $token = $loginResponse->json('access_token');

        // 2. Get profile using token
        $profileResponse = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/v1/customers/me');

        $profileResponse->assertStatus(200)
            ->assertJsonPath('customer.nic_passport', '199512345678')
            ->assertJsonPath('customer.status', 'active');
    }

    /* Test cashier can retrieve all registered customers paginated */
    public function test_cashier_can_retrieve_paginated_customers(): void
    {
        Customer::create([
            'nic_passport' => '199512345678',
            'mobile_number' => '0773456789',
            'name' => 'John Doe',
            'status' => 'active',
            'registered_by' => $this->cashier->id,
        ]);

        $response = $this->actingAs($this->cashier)
            ->getJson('/api/v1/customers');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'customers' => [
                    'data' => [
                        '*' => ['id', 'nic_passport', 'mobile_number', 'name', 'status']
                    ],
                    'links',
                    'meta'
                ]
            ]);
    }

    /* Test Sri Lankan NIC and phone number inputs are sanitized and standardized */
    public function test_customer_inputs_are_sanitized_and_normalized(): void
    {
        // 1. Post registration with spaced/dashed phone "+94 77-123 4567" and lowercase NIC with space "123456789 v"
        $response = $this->actingAs($this->cashier)
            ->postJson('/api/v1/customers', [
                'nic_passport' => '123456789 v',
                'mobile_number' => '+94 77-123 4567',
                'name' => 'Sanitized User',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('customer.nic_passport', '123456789V')
            ->assertJsonPath('customer.mobile_number', '0771234567');

        $this->assertDatabaseHas('customers', [
            'nic_passport' => '123456789V',
            'mobile_number' => '0771234567',
        ]);
    }
}
