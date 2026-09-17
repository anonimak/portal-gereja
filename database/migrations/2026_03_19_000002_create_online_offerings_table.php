<?php

declare(strict_types=1);

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
        Schema::create('online_offerings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->foreignId('fund_id')->constrained('funds')->restrictOnDelete();
            $table->foreignId('financial_category_id')->constrained('financial_categories')->restrictOnDelete();
            $table->string('donor_name')->default('Hamba Allah');
            $table->string('donor_phone')->nullable();
            $table->string('donor_email')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('payment_method')->default('qris'); // qris, bank_transfer, va
            $table->string('bank_name')->nullable();
            $table->string('reference_code')->unique();
            $table->string('proof_path')->nullable();
            $table->text('prayer_notes')->nullable();
            $table->string('status')->default('pending'); // pending, confirmed, rejected
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('transaction_id')->nullable()->constrained('transactions')->nullOnDelete();
            $table->string('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['church_id', 'status']);
            $table->index(['church_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_offerings');
    }
};
