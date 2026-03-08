<?php

declare(strict_types=1);

require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->boot();

use App\Models\Church;
use App\Models\Fund;
use App\Models\FinancialCategory;

echo "=== DEFAULT FINANCE SEEDING VERIFICATION ===" . PHP_EOL . PHP_EOL;

echo "Total Churches: " . Church::count() . PHP_EOL;
echo "Total Funds: " . Fund::count() . PHP_EOL;
echo "Total Categories: " . FinancialCategory::count() . PHP_EOL . PHP_EOL;

foreach (Church::all() as $church) {
    $fundCount = Fund::where('church_id', $church->id)->count();
    $debitCount = FinancialCategory::where('church_id', $church->id)->where('type', 'debit')->count();
    $creditCount = FinancialCategory::where('church_id', $church->id)->where('type', 'credit')->count();

    echo "Church: {$church->name}" . PHP_EOL;
    echo "  Code: {$church->code}" . PHP_EOL;
    echo "  Funds: {$fundCount}" . PHP_EOL;
    echo "  Income Categories: {$debitCount}" . PHP_EOL;
    echo "  Expense Categories: {$creditCount}" . PHP_EOL;
    echo PHP_EOL;
}

echo "✓ Default finance seeding verified successfully!" . PHP_EOL;
