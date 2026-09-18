<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Reporting\Resources;

use App\Filament\Clusters\Reporting\ReportingCluster;
use App\Filament\Clusters\Reporting\Resources\MeetingMinutesResource\Pages;
use App\Models\Event;
use App\Models\MeetingMinutes;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MeetingMinutesResource extends Resource
{
    protected static ?string $model = MeetingMinutes::class;

    protected static ?string $cluster = ReportingCluster::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static string|\UnitEnum|null $navigationGroup = 'Rapat & Notulen';

    protected static ?string $navigationLabel = 'Notulen Rapat';

    protected static ?string $modelLabel = 'Notulen Rapat';

    protected static ?string $pluralModelLabel = 'Notulen Rapat';

    protected static ?int $navigationSort = 8;

    public static function canAccess(): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'church_admin', 'finance_admin', 'report_viewer'], true);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Rapat')
                    ->description('Tautkan notulen ke agenda rapat atau acara gereja')
                    ->schema([
                        Select::make('event_id')
                            ->label('Sematkan ke Laporan / Acara Rapat')
                            ->relationship('event', 'title', fn (Builder $query) => $query->latest())
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Pilih acara atau rapat yang bersangkutan untuk menyematkan notulen ini'),

                        TextInput::make('title')
                            ->label('Judul Notulen / Topik Rapat')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Contoh: Rapat Majelis Pleno Pembahasan Anggaran 2027'),

                        DatePicker::make('meeting_date')
                            ->label('Tanggal Pelaksanaan')
                            ->required()
                            ->default(now()),
                    ])->columns(2),

                Section::make('Daftar Agenda & Peserta')
                    ->schema([
                        TagsInput::make('agenda')
                            ->label('Agenda Rapat')
                            ->placeholder('Tambah poin agenda, tekan Enter')
                            ->helperText('Poin-poin agenda yang dibahas dalam rapat'),

                        TagsInput::make('participants')
                            ->label('Daftar Hadir / Peserta')
                            ->placeholder('Tambah nama peserta, tekan Enter')
                            ->helperText('Nama majelis atau peserta yang hadir'),
                    ])->columns(2),

                Section::make('Pembahasan & Keputusan')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Catatan & Ringkasan Pembahasan')
                            ->rows(5)
                            ->columnSpanFull(),

                        TagsInput::make('decisions')
                            ->label('Keputusan Rapat')
                            ->placeholder('Tambah butir keputusan, tekan Enter')
                            ->columnSpanFull()
                            ->helperText('Keputusan resmi yang disepakati bersama'),
                    ]),

                Section::make('Berkas Lampiran')
                    ->schema([
                        FileUpload::make('attachments')
                            ->label('Lampiran Berkas / Dokumen')
                            ->multiple()
                            ->directory('meeting-minutes-attachments')
                            ->disk('public')
                            ->maxSize(10240)
                            ->helperText('Format PDF, gambar, atau dokumen pendukung rapat')
                            ->columnSpanFull(),
                    ])->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meeting_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('title')
                    ->label('Judul Notulen')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(fn (MeetingMinutes $record): ?string => $record->event?->title ? 'Terselip di: ' . $record->event->title : null),

                TextColumn::make('event.title')
                    ->label('Tautan Acara / Rapat')
                    ->badge()
                    ->color('info')
                    ->placeholder('Tanpa Tautan Event')
                    ->searchable(),

                TextColumn::make('church.name')
                    ->label('Gereja')
                    ->visible(fn () => auth()->user()?->role === 'super_admin')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('meeting_date', 'desc')
            ->filters([
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
                RestoreAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetingMinutes::route('/'),
            'create' => Pages\CreateMeetingMinutes::route('/create'),
            'view' => Pages\ViewMeetingMinutes::route('/{record}'),
            'edit' => Pages\EditMeetingMinutes::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
