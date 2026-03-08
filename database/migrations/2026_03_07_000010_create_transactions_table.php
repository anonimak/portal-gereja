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
            $table->foreignId('church_id')->constrained('churches')->cascadeOnDelete()->index();
            $table->foreignId('fund_id')->constrained('funds')->cascadeOnDelete()->index();
            $table->foreignId('category_id')->constrained('financial_categories')->cascadeOnDelete()->index();
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
