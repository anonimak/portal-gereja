<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\BirthRecord;

use App\Filament\Clusters\Lifecycle\LifecycleCluster;
use App\Filament\Clusters\Lifecycle\Resources\BirthRecord\Pages\CreateBirthRecord;
use App\Filament\Clusters\Lifecycle\Resources\BirthRecord\Pages\EditBirthRecord;
use App\Filament\Clusters\Lifecycle\Resources\BirthRecord\Pages\ListBirthRecords;
use App\Models\BirthRecord;
use App\Models\Member;
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
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Fase 3B T5 — Resource Kelahiran (Akta Lahir).
 * Pola Fase 1/2: BelongsToChurch (global scope), SoftDeletes, RecordsAuditTrail,
 * TrashedFilter + RestoreAction, church_id dijamin oleh trait (bukan form).
 */
class BirthRecordResource extends Resource
{
    protected static ?string $model = BirthRecord::class;

    protected static ?string $modelLabel = 'Kelahiran / Akta Lahir';

    protected static ?string $pluralModelLabel = 'Kelahiran / Akta Lahir';

    protected static ?string $cluster = LifecycleCluster::class;

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Data Kelahiran')
                    ->schema([
                        Select::make('member_id')
                            ->label('Anggota (Bayi/Anak)')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->live()
                            ->relationship(
                                'member',
                                'full_name',
                                function (Builder $query): Builder {
                                    // Non-super_admin hanya member gereja sendiri;
                                    // super_admin melihat semua gereja (lintas gereja).
                                    if (auth()->user()?->role !== 'super_admin') {
                                        $query->where('church_id', auth()->user()->church_id);
                                    }

                                    return $query;
                                }
                            )
                            ->afterStateUpdated(function (Set $set, ?int $state): void {
                                if (! $state) {
                                    return;
                                }

                                $member = Member::query()
                                    ->withoutGlobalScopes()
                                    ->with('family.members')
                                    ->find($state);

                                if (! $member) {
                                    return;
                                }

                                $defaults = BirthRecord::defaultsFor($member);

                                $set('birth_date', $defaults['birth_date']);
                                $set('birth_place_full', $defaults['birth_place_full']);
                                $set('father_name', $defaults['father_name']);
                                $set('mother_name', $defaults['mother_name']);
                            }),
                        TextInput::make('birth_order')
                            ->label('Anak ke-')
                            ->numeric()
                            ->minValue(1)
                            ->nullable(),
                        TextInput::make('birth_place_full')
                            ->label('Tempat Lahir (untuk dokumen)')
                            ->maxLength(255)
                            ->nullable(),
                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->required(),
                        TextInput::make('father_name')
                            ->label('Nama Ayah')
                            ->maxLength(255)
                            ->nullable(),
                        TextInput::make('mother_name')
                            ->label('Nama Ibu')
                            ->maxLength(255)
                            ->nullable(),
                    ])->columns(2),
                Section::make('Dokumen Akta Lahir')
                    ->description('Nomor akta diisi manual (default editable), bisa dikosongkan dulu')
                    ->schema([
                        TextInput::make('certificate_number')
                            ->label('Nomor Akta')
                            ->maxLength(100)
                            ->nullable(),
                        DatePicker::make('issued_at')
                            ->label('Tanggal Terbit')
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
                TextColumn::make('member.full_name')
                    ->label('Nama Anak')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('member.gender')
                    ->label('Kelamin')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'm' ? 'blue' : 'pink')
                    ->formatStateUsing(fn (string $state): string => $state === 'm' ? 'Laki-laki' : 'Perempuan'),
                TextColumn::make('birth_date')
                    ->label('Tanggal Lahir')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('birth_place_full')
                    ->label('Tempat Lahir')
                    ->toggleable(),
                TextColumn::make('father_name')
                    ->label('Ayah')
                    ->toggleable(),
                TextColumn::make('mother_name')
                    ->label('Ibu')
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
                // Tombol cetak Akta Lahir — export PDF via dompdf (route ter-guard).
                \Filament\Actions\Action::make('cetak')
                    ->label('Cetak Akta')
                    ->icon('heroicon-o-printer')
                    ->color('info')
                    ->url(fn (BirthRecord $record): string => route('birth-record.export-pdf', $record))
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
            'index' => ListBirthRecords::route('/'),
            'create' => CreateBirthRecord::route('/create'),
            'edit' => EditBirthRecord::route('/{record}/edit'),
        ];
    }
}
