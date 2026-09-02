<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\Marriage;

use App\Filament\Clusters\Lifecycle\LifecycleCluster;
use App\Filament\Clusters\Lifecycle\Resources\Marriage\Pages\CreateMarriage;
use App\Filament\Clusters\Lifecycle\Resources\Marriage\Pages\EditMarriage;
use App\Filament\Clusters\Lifecycle\Resources\Marriage\Pages\ListMarriages;
use App\Models\Marriage;
use App\Models\Member;
use App\Models\Official;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fase 3B T9 — Resource Pernikahan (Akta Nikah).
 * Pola Fase 1/2: BelongsToChurch (global scope), SoftDeletes, RecordsAuditTrail,
 * TrashedFilter + RestoreAction, church_id dijamin trait (bukan form).
 * Membuat record -> otomatis 2 baris sakramen 'nikah' (AC-LC-04, model hook).
 */
class MarriageResource extends Resource
{
    protected static ?string $model = Marriage::class;

    protected static ?string $modelLabel = 'Pernikahan / Akta Nikah';

    protected static ?string $pluralModelLabel = 'Pernikahan / Akta Nikah';

    protected static ?string $cluster = LifecycleCluster::class;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Pasangan')
                    ->schema([
                        Select::make('husband_member_id')
                            ->label('Suami (Anggota)')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'husband',
                                'full_name',
                                function (Builder $query): Builder {
                                    if (auth()->user()?->role !== 'super_admin') {
                                        $query->where('church_id', auth()->user()->church_id);
                                    }

                                    return $query;
                                }
                            ),
                        Select::make('wife_member_id')
                            ->label('Istri (Anggota)')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'wife',
                                'full_name',
                                function (Builder $query): Builder {
                                    if (auth()->user()?->role !== 'super_admin') {
                                        $query->where('church_id', auth()->user()->church_id);
                                    }

                                    return $query;
                                }
                            ),
                    ])->columns(2),
                Section::make('Pemberkatan')
                    ->schema([
                        DatePicker::make('marriage_date')
                            ->label('Tanggal Pemberkatan')
                            ->required(),
                        Select::make('official_id')
                            ->label('Pendeta / Pelayan')
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'official',
                                'id',
                                fn (Builder $query): Builder => $query->when(
                                    auth()->user()?->role !== 'super_admin',
                                    fn (Builder $q) => $q->where('church_id', auth()->user()->church_id)
                                )
                            )
                            ->getOptionLabelFromRecordUsing(fn (Official $record): string => $record->display_name),
                        TextInput::make('location')
                            ->label('Tempat Pemberkatan')
                            ->maxLength(255)
                            ->nullable(),
                        Select::make('program_id')
                            ->label('Program Bimbingan Pra-Nikah (opsional)')
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'program',
                                'title',
                                fn (Builder $query): Builder => $query->when(
                                    auth()->user()?->role !== 'super_admin',
                                    fn (Builder $q) => $q->where('church_id', auth()->user()->church_id)
                                )
                            )
                            ->nullable(),
                    ])->columns(2),
                Section::make('Dokumen Akta Nikah')
                    ->description('Nomor akta diisi manual; dua baris sakramen nikah dibuat otomatis saat record disimpan.')
                    ->schema([
                        TextInput::make('certificate_number')
                            ->label('Nomor Akta')
                            ->maxLength(100)
                            ->nullable(),
                        DatePicker::make('issued_at')
                            ->label('Tanggal Terbit')
                            ->nullable(),
                        Repeater::make('witness_names')
                            ->label('Saksi')
                            ->schema([
                                TextInput::make('name')->label('Nama Saksi')->required(),
                            ])
                            ->default([])
                            ->nullable(),
                        TextInput::make('notes')
                            ->label('Catatan')
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('husband.full_name')
                    ->label('Suami')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('wife.full_name')
                    ->label('Istri')
                    ->searchable(),
                TextColumn::make('marriage_date')
                    ->label('Tanggal Pemberkatan')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('official.display_name')
                    ->label('Pendeta')
                    ->toggleable(),
                TextColumn::make('certificate_number')
                    ->label('No. Akta')
                    ->searchable(),
                TextColumn::make('issued_at')
                    ->label('Terbit')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak Akta')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (Marriage $record): string => route('marriage.export-pdf', $record))
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
            'index' => ListMarriages::route('/'),
            'create' => CreateMarriage::route('/create'),
            'edit' => EditMarriage::route('/{record}/edit'),
        ];
    }
}
