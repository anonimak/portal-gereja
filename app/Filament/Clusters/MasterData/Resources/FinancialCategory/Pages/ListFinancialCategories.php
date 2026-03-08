<?php

declare(strict_types=1);

namespace App\Filament\Clusters\MasterData\Resources\FinancialCategory\Pages;

use App\Filament\Clusters\MasterData\Resources\FinancialCategory\FinancialCategoryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialCategories extends ListRecords
{
    protected static string $resource = FinancialCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
