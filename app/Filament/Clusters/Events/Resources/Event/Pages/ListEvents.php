<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Resources\Event\Pages;

use App\Filament\Clusters\Events\Pages\KalenderIbadahPage;
use App\Filament\Clusters\Events\Resources\Event\EventResource;
use App\Filament\Clusters\Events\Resources\RecurringSchedule\RecurringScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEvents extends ListRecords
{
    protected static string $resource = EventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Kalender Ibadah')
                ->icon('heroicon-o-calendar')
                ->color('gray')
                ->url(fn (): string => KalenderIbadahPage::getUrl()),
            Actions\Action::make('recurring')
                ->label('Jadwal Berulang')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->url(fn (): string => RecurringScheduleResource::getUrl('index')),
            Actions\CreateAction::make(),
        ];
    }
}
