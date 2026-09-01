<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceSession\RelationManagers;

use App\Models\GuidanceSessionMember;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Relation manager "Peserta" pada GuidanceSessionResource.
 * Tambah member per pertemuan + toggle kehadiran (attended) — restore-or-create
 * (AC-LC-03): duplikat aktif dilewati, record soft-deleted di-restore.
 */
class ParticipantsRelationManager extends RelationManager
{
    protected static string $relationship = 'participantRows';

    protected static ?string $recordTitleAttribute = 'member.full_name';

    private function ownerChurchId(): int
    {
        return (int) ($this->getOwnerRecord()?->church_id ?? auth()->user()?->church_id);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Tambah Peserta')
                    ->schema([
                        Select::make('member_id')
                            ->label('Anggota')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->hiddenOn('edit')
                            ->relationship(
                                'member',
                                'full_name',
                                fn (Builder $query): Builder => $query
                                    ->where('church_id', $this->ownerChurchId()),
                            ),
                        Toggle::make('attended')
                            ->label('Hadir pada pertemuan ini')
                            ->default(false),
                        \Filament\Forms\Components\TextInput::make('notes')
                            ->label('Catatan')
                            ->nullable()
                            ->maxLength(255),
                    ])
                    ->columns(1),
            ]);
    }

    /**
     * church_id pivot mengikuti gereja sesi (owner record) — penting untuk
     * super_admin yang membuka sesi gereja lain (AC-LC-10).
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['church_id'] = $this->getOwnerRecord()->church_id;

        return $data;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.full_name')
                    ->label('Anggota')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('attended')
                    ->label('Hadir')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->placeholder('-'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('toggle_attended')
                    ->label(fn (GuidanceSessionMember $record): string => $record->attended ? 'Tandai Tidak Hadir' : 'Tandai Hadir')
                    ->icon(fn (GuidanceSessionMember $record): string => $record->attended ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->requiresConfirmation()
                    ->action(function (GuidanceSessionMember $record): void {
                        $record->update(['attended' => ! $record->attended]);
                        Notification::make()
                            ->title($record->attended ? 'Ditandai hadir.' : 'Ditandai tidak hadir.')
                            ->success()
                            ->send();
                    }),
                Action::make('restore')
                    ->label('Pulihkan')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->requiresConfirmation()
                    ->visible(fn (GuidanceSessionMember $record): bool => $record->trashed())
                    ->action(fn (GuidanceSessionMember $record) => $record->restore()),
                \Filament\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->visible(fn (GuidanceSessionMember $record): bool => ! $record->trashed()),
            ])
            ->toolbarActions([
                \Filament\Actions\CreateAction::make()
                    ->using(function (array $data): ?GuidanceSessionMember {
                        $session = $this->getOwnerRecord();

                        return GuidanceSessionMember::checkInOrRestore(
                            (int) $session->id,
                            (int) $data['member_id'],
                            (bool) ($data['attended'] ?? false),
                            $data['notes'] ?? null,
                        );
                    }),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
