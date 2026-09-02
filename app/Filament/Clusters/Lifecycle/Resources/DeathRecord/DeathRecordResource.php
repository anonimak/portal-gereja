<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\DeathRecord;

use App\Filament\Clusters\Lifecycle\LifecycleCluster;
use App\Filament\Clusters\Lifecycle\Resources\DeathRecord\Pages\CreateDeathRecord;
use App\Filament\Clusters\Lifecycle\Resources\DeathRecord\Pages\EditDeathRecord;
use App\Filament\Clusters\Lifecycle\Resources\DeathRecord\Pages\ListDeathRecords;
use App\Models\DeathRecord;
use App\Models\Event;
use App\Models\Member;
use App\Models\Official;
use App\Support\ChurchContext;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fase 3B T11 — Resource Kematian (Surat Keterangan Kematian).
 *
 * Pola Fase 1/2: BelongsToChurch (global scope), SoftDeletes, RecordsAuditTrail,
 * TrashedFilter + RestoreAction, church_id dijamin trait (bukan form).
 * Side effect: member.status -> 'meninggal' (AC-LC-05, model hook).
 */
class DeathRecordResource extends Resource
{
    protected static ?string $model = DeathRecord::class;

    protected static ?string $modelLabel = 'Kematian / Surat Keterangan Kematian';

    protected static ?string $pluralModelLabel = 'Kematian';

    protected static ?string $cluster = LifecycleCluster::class;

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Data Kematian')
                    ->schema([
                        Select::make('member_id')
                            ->label('Anggota Meninggal')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'member',
                                'full_name',
                                fn (Builder $query): Builder => $query
                                    ->whereDoesntHave('deathRecord')
                                    ->when(
                                        ChurchContext::activeChurchId() !== null,
                                        fn (Builder $q) => $q->where('church_id', ChurchContext::activeChurchId())
                                    )
                            ),
                        DatePicker::make('death_date')
                            ->label('Tanggal Meninggal')
                            ->required(),
                        DatePicker::make('burial_date')
                            ->label('Tanggal Pemakaman')
                            ->nullable(),
                        TextInput::make('burial_location')
                            ->label('Tempat Pemakaman')
                            ->maxLength(255)
                            ->nullable(),
                        Select::make('official_id')
                            ->label('Pendeta / Pelayan Ibadah')
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'official',
                                'id',
                                fn (Builder $query): Builder => $query->when(
                                    ChurchContext::activeChurchId() !== null,
                                    fn (Builder $q) => $q->where('church_id', ChurchContext::activeChurchId())
                                )
                            )
                            ->getOptionLabelFromRecordUsing(fn (Official $record): string => $record->display_name)
                            ->nullable(),
                        Select::make('event_id')
                            ->label('Event Ibadah Pemakaman (opsional)')
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'event',
                                'id',
                                fn (Builder $query): Builder => $query->when(
                                    ChurchContext::activeChurchId() !== null,
                                    fn (Builder $q) => $q->where('church_id', ChurchContext::activeChurchId())
                                )
                            )
                            ->getOptionLabelFromRecordUsing(fn (Event $record): string => $record->title)
                            ->nullable(),
                    ])->columns(2),
                Section::make('Dokumen Surat Keterangan Kematian')
                    ->schema([
                        TextInput::make('certificate_number')
                            ->label('Nomor Surat')
                            ->maxLength(100)
                            ->nullable(),
                        DatePicker::make('issued_at')
                            ->label('Tanggal Terbit')
                            ->nullable(),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->nullable()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('member.full_name')
                    ->label('Nama Anggota')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('death_date')
                    ->label('Tanggal Meninggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('burial_date')
                    ->label('Pemakaman')
                    ->date('d M Y')
                    ->placeholder('-'),
                TextColumn::make('official.display_name')
                    ->label('Pendeta')
                    ->placeholder('-'),
                TextColumn::make('certificate_number')
                    ->label('No. Surat')
                    ->searchable()
                    ->placeholder('-'),
                TextColumn::make('member.status')
                    ->label('Status Anggota')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => $state === 'meninggal' ? 'danger' : 'gray'),
            ])
            ->filters([
                SelectFilter::make('member.status')
                    ->label('Status Anggota')
                    ->options([
                        'aktif' => 'Aktif',
                        'titipan' => 'Titipan',
                        'pindah' => 'Pindah',
                        'meninggal' => 'Meninggal',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak Surat')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (DeathRecord $record): string => route('death-record.export-pdf', $record))
                    ->openUrlInNewTab(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDeathRecords::route('/'),
            'create' => CreateDeathRecord::route('/create'),
            'edit' => EditDeathRecord::route('/{record}/edit'),
        ];
    }
}
