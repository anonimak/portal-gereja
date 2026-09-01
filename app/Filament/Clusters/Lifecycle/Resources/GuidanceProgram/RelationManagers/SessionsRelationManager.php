<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceProgram\RelationManagers;

use App\Models\Official;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Relation manager "Sesi/Pertemuan" pada GuidanceProgramResource.
 * Create/edit sesi: jadwal (session_at), topik (title), lokasi, pembimbing (official).
 */
class SessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $recordTitleAttribute = 'title';

    private function ownerChurchId(): int
    {
        return (int) ($this->getOwnerRecord()?->church_id ?? auth()->user()?->church_id);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Pertemuan')
                    ->schema([
                        TextInput::make('title')
                            ->label('Topik Pertemuan')
                            ->required()
                            ->maxLength(255),
                        DateTimePicker::make('session_at')
                            ->label('Waktu Pertemuan')
                            ->nullable(),
                        TextInput::make('location')
                            ->label('Lokasi')
                            ->nullable()
                            ->maxLength(255),
                        Select::make('official_id')
                            ->label('Pembimbing (Pendeta/Majelis)')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->relationship(
                                'official',
                                'id',
                                fn (Builder $query): Builder => $query->where('church_id', $this->ownerChurchId()),
                            )
                            ->getOptionLabelFromRecordUsing(fn (Official $record): string => $record->display_name),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Topik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('session_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->placeholder('-'),
                TextColumn::make('official.display_name')
                    ->label('Pembimbing')
                    ->placeholder('-'),
                TextColumn::make('participant_rows_count')
                    ->label('Peserta')
                    ->counts('participantRows'),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
