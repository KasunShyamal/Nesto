<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->onDelete('set null');
            $table->string('nic_passport')->unique();
            $table->string('mobile_number');
            $table->string('name');
            $table->enum('status', ['pending', 'active'])->default('pending');
            $table->foreignId('registered_by')->constrained('users');
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->string('invoice_number');
            $table->foreignId('branch_id')->constrained('branches');
            $table->date('transaction_date');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['branch_id', 'invoice_number']);
        });

        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('cascade');
            $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('set null');
            $table->integer('points');
            $table->enum('type', ['earn', 'redeem', 'adjustment']);
            $table->string('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('branches');
    }
};
