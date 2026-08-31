<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Resources\Event;

use App\Filament\Clusters\Events\EventsCluster;
use App\Models\Event;
use App\Models\Official;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class EventResource extends Resource
{
    protected static ?string $model = Event::class;

    protected static ?string $modelLabel = 'Acara';

    protected static ?string $pluralModelLabel = 'Acara';

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;

    protected static ?string $cluster = EventsCluster::class;

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Acara')
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Acara')
                            ->required()
                            ->maxLength(255),
                        Select::make('category_id')
                            ->label('Kategori Acara')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'category',
                                'name',
                                fn (Builder $query) => $query->where('church_id', auth()->user()->church_id)
                            )
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->label('Nama Kategori')
                                    ->required(),
                            ])
                            ->createOptionAction(function (Action $action) {
                                return $action
                                    ->modalHeading('Tambah Kategori Baru')
                                    ->modalWidth('md');
                            }),
                        DateTimePicker::make('start_datetime')
                            ->label('Waktu Mulai')
                            ->required(),
                        DateTimePicker::make('end_datetime')
                            ->label('Waktu Selesai')
                            ->required(),
                        TextInput::make('location')
                            ->label('Lokasi')
                            ->maxLength(255),
                        Fieldset::make('Kehadiran')
                            ->schema([
                                TextInput::make('attendance_male')
                                    ->label('Kehadiran Laki-laki')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                                TextInput::make('attendance_female')
                                    ->label('Kehadiran Perempuan')
                                    ->numeric()
                                    ->default(0)
                                    ->minValue(0),
                            ])
                            ->columns(2),
                    ]),

                Section::make('Petugas Acara')
                    ->schema([
                        Repeater::make('rosters')
                            ->label('Daftar Petugas')
                            ->relationship()
                            // MED Vera (re-review PR #1): assignee_type tidak disimpan ke DB
                            // (dehydrated false), sehingga saat edit member_id/official_id
                            // tersembunyi dan petugas tidak bisa diubah. Isi assignee_type
                            // dari data roster yang tersimpan agar field yang benar tampil.
                            ->mutateRelationshipDataBeforeFillUsing(function (array $data): array {
                                if (filled($data['member_id'] ?? null)) {
                                    $data['assignee_type'] = 'member';
                                } elseif (filled($data['official_id'] ?? null)) {
                                    $data['assignee_type'] = 'official';
                                }

                                return $data;
                            })
                            ->schema([
                                Select::make('assignee_type')
                                    ->label('Tipe Petugas')
                                    ->required()
                                    ->options([
                                        'member' => 'Jemaat Biasa',
                                        'official' => 'Pendeta / Majelis',
                                    ])
                                    ->live()
                                    ->native(false)
                                    // UI-only: tidak ada kolom assignee_type di event_rosters.
                                    ->dehydrated(false)
                                    // Saat pindah tipe, null-kan field yang tidak dipakai
                                    // supaya satu roster tidak punya member DAN official.
                                    ->afterStateUpdated(function (Set $set, ?string $state): void {
                                        if ($state === 'member') {
                                            $set('official_id', null);
                                        } elseif ($state === 'official') {
                                            $set('member_id', null);
                                        }
                                    }),
                                Select::make('member_id')
                                    ->label('Anggota')
                                    ->required(fn (Get $get): bool => $get('assignee_type') === 'member')
                                    ->searchable()
                                    ->preload()
                                    ->hidden(fn (Get $get): bool => $get('assignee_type') !== 'member')
                                    ->relationship(
                                        'member',
                                        'full_name',
                                        // M2 Vera: sertakan member yang di-soft-delete supaya roster
                                        // dengan member terhapus tetap bisa diedit.
                                        fn (Builder $query) => $query->withTrashed()->where('church_id', auth()->user()->church_id)
                                    ),
                                Select::make('official_id')
                                    ->label('Pendeta / Majelis')
                                    ->required(fn (Get $get): bool => $get('assignee_type') === 'official')
                                    ->searchable()
                                    ->preload()
                                    ->hidden(fn (Get $get): bool => $get('assignee_type') !== 'official')
                                    // Title attribute kolom nyata 'id' + label dari accessor
                                    // (hindari pluck display_name yang bukan kolom).
                                    ->relationship(
                                        'official',
                                        'id',
                                        fn (Builder $query) => $query->where('church_id', auth()->user()->church_id)
                                    )
                                    ->getOptionLabelFromRecordUsing(fn (Official $record): string => $record->display_name),
                                Select::make('role_id')
                                    ->label('Peran')
                                    ->required()
                                    ->searchable()
                                    ->preload()
                                    ->relationship(
                                        'role',
                                        'name',
                                        fn (Builder $query) => $query->where('church_id', auth()->user()->church_id)
                                    ),
                            ])
                            ->columns(2)
                            ->collapsible()
                            ->reorderableWithButtons(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Acara')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori Acara')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('start_datetime')
                    ->label('Waktu Mulai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('rosters_count')
                    ->counts('rosters')
                    ->label('Petugas')
                    ->formatStateUsing(fn (int $state): string => "{$state} Petugas"),
                TextColumn::make('total_attendance')
                    ->label('Total Kehadiran')
                    ->sortable(),
                TextColumn::make('location')
                    ->label('Lokasi')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // H3 Vera / AC-UI-01: filter untuk menampilkan record yang di-soft-delete.
                TrashedFilter::make(),
            ])
            ->recordActions([
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEvents::route('/'),
            'create' => Pages\CreateEvent::route('/create'),
            'edit' => Pages\EditEvent::route('/{record}/edit'),
        ];
    }
}
