<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Members\Pages;

use App\Filament\Clusters\Demographics\Resources\Members\MemberResource;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMembers extends ListRecords
{
    protected static string $resource = MemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
            Action::make('templateCsv')
                ->label('Template CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(fn (): string => route('csv-jemaat.template'))
                ->visible(fn (): bool => in_array(auth()->user()?->role, ['super_admin', 'church_admin', 'jemaat_admin', 'report_viewer'], true)),
            Action::make('exportCsv')
                ->label('Export CSV')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->url(fn (): string => route('csv-jemaat.export'))
                ->visible(fn (): bool => in_array(auth()->user()?->role, ['super_admin', 'church_admin', 'jemaat_admin', 'report_viewer'], true)),
        ];
    }
}
