<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Resources\RecurringSchedule;

use App\Filament\Clusters\Events\EventsCluster;
use App\Models\Church;
use App\Models\RecurringSchedule;
use App\Services\RecurringEventService;
use App\Support\ChurchScope;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class RecurringScheduleResource extends Resource
{
    protected static ?string $model = RecurringSchedule::class;

    protected static ?string $modelLabel = 'Jadwal Berulang';

    protected static ?string $pluralModelLabel = 'Jadwal Berulang';

    protected static ?string $cluster = EventsCluster::class;

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Jadwal')
                    ->schema([
                        Select::make('church_id')
                            ->label('Gereja')
                            ->options(fn (): array => Church::query()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => auth()->user()?->role === 'super_admin')
                            ->rules([Rule::exists('churches', 'id')])
                            ->nullable(),
                        TextInput::make('title')
                            ->label('Judul Ibadah / Acara')
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
                                fn (Builder $query): Builder => ChurchScope::forActorSelect($query)
                            ),
                        TextInput::make('location')
                            ->label('Lokasi')
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Keterangan')
                            ->rows(3),
                    ]),

                Section::make('Pola Pengulangan')
                    ->schema([
                        Select::make('frequency')
                            ->label('Frekuensi')
                            ->required()
                            ->options([
                                'daily' => 'Harian',
                                'weekly' => 'Mingguan',
                                'monthly' => 'Bulanan',
                            ])
                            ->live()
                            ->default('weekly'),
                        TextInput::make('interval')
                            ->label('Interval')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1)
                            ->helperText('Contoh: 1 = setiap minggu/hari/bulan; 2 = dua minggu sekali/dua bulan sekali.'),
                        CheckboxList::make('days_of_week')
                            ->label('Hari Pelaksanaan')
                            ->options([
                                0 => 'Minggu',
                                1 => 'Senin',
                                2 => 'Selasa',
                                3 => 'Rabu',
                                4 => 'Kamis',
                                5 => 'Jumat',
                                6 => 'Sabtu',
                            ])
                            ->visible(fn (Get $get): bool => $get('frequency') === 'weekly')
                            ->columns(4),
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->required()
                            ->default(now()->toDateString()),
                        TimePicker::make('start_time')
                            ->label('Jam Mulai')
                            ->required()
                            ->default('08:00:00'),
                        TimePicker::make('end_time')
                            ->label('Jam Selesai')
                            ->required()
                            ->default('10:00:00'),
                        Select::make('end_type')
                            ->label('Batas Akhir')
                            ->required()
                            ->options([
                                'until_date' => 'Sampai Tanggal Tertentu',
                                'count' => 'Berdasarkan Jumlah Pengulangan',
                                'never' => 'Berulang Terus-menerus',
                            ])
                            ->live()
                            ->default('until_date'),
                        DatePicker::make('until_date')
                            ->label('Tanggal Berakhir')
                            ->visible(fn (Get $get): bool => $get('end_type') === 'until_date')
                            ->required(fn (Get $get): bool => $get('end_type') === 'until_date')
                            ->default(now()->addMonths(3)->toDateString()),
                        TextInput::make('repeat_count')
                            ->label('Jumlah Pengulangan')
                            ->numeric()
                            ->minValue(1)
                            ->visible(fn (Get $get): bool => $get('end_type') === 'count')
                            ->required(fn (Get $get): bool => $get('end_type') === 'count'),
                        Toggle::make('is_active')
                            ->label('Jadwal Aktif')
                            ->default(true),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Judul Jadwal')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('frequency')
                    ->label('Pola')
                    ->formatStateUsing(function (RecurringSchedule $record): string {
                        $labels = [
                            'daily' => 'Harian',
                            'weekly' => 'Mingguan',
                            'monthly' => 'Bulanan',
                        ];
                        $freq = $labels[$record->frequency] ?? $record->frequency;

                        return "{$freq} (setiap {$record->interval})";
                    })
                    ->sortable(),
                TextColumn::make('start_time')
                    ->label('Waktu')
                    ->formatStateUsing(fn (RecurringSchedule $record): string => sprintf('%s - %s', substr((string) $record->start_time, 0, 5), substr((string) $record->end_time, 0, 5))),
                TextColumn::make('events_count')
                    ->counts('events')
                    ->label('Acara Dibuat')
                    ->formatStateUsing(fn (int $state): string => "{$state} Acara"),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                TextColumn::make('last_generated_at')
                    ->label('Terakhir Digenerate')
                    ->dateTime()
                    ->placeholder('Belum pernah')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('frequency')
                    ->label('Frekuensi')
                    ->options([
                        'daily' => 'Harian',
                        'weekly' => 'Mingguan',
                        'monthly' => 'Bulanan',
                    ]),
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('generate')
                    ->label('Generate Acara')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Generate Acara dari Jadwal Berulang')
                    ->modalDescription('Sistem akan mengenerate acara (Event) nyata untuk jadwal ini sesuai tanggal dan jam pengulangan.')
                    ->action(function (RecurringSchedule $record): void {
                        $created = app(RecurringEventService::class)->generateEvents($record);
                        Notification::make()
                            ->title(sprintf('%d acara berhasil digenerate!', $created->count()))
                            ->success()
                            ->send();
                    }),
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
            'index' => Pages\ListRecurringSchedules::route('/'),
            'create' => Pages\CreateRecurringSchedule::route('/create'),
            'edit' => Pages\EditRecurringSchedule::route('/{record}/edit'),
        ];
    }
}
