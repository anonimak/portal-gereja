<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceProgram\Pages;

use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgramResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditGuidanceProgram extends EditRecord
{
    protected static string $resource = GuidanceProgramResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('instantiate_from_template')
                ->label('Instantiate dari Template')
                ->icon('heroicon-o-sparkles')
                ->requiresConfirmation()
                ->modalDescription('Membuat sesi otomatis 1..N dari template yang dipilih (topik template disalin, sesi yang sudah ada tidak diduplikasi).')
                ->visible(fn (): bool => (bool) $this->record->template_id)
                ->action(function (): void {
                    $created = $this->record->instantiateFromTemplate();
                    $this->sendSuccessNotification(
                        $created > 0
                            ? "{$created} sesi dibuat otomatis dari template."
                            : 'Tidak ada sesi baru (semua topik sudah ada atau template kosong).'
                    );
                }),
            Actions\DeleteAction::make(),
        ];
    }
}
