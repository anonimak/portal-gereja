<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete();
            $table->index('church_id');
            $table->foreignId('fund_id')->constrained('funds')->cascadeOnDelete();
            $table->index('fund_id');
            $table->foreignId('category_id')->constrained('financial_categories')->cascadeOnDelete();
            $table->index('category_id');
            $table->enum('type', ['debit', 'credit']);
            $table->bigInteger('amount');
            $table->date('transaction_date')->index();
            $table->text('description');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
