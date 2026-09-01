<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources;

use App\Filament\Clusters\Lifecycle\LifecycleCluster;
use App\Filament\Clusters\Lifecycle\Resources\GuidanceSession\Pages;
use App\Filament\Clusters\Lifecycle\Resources\GuidanceSession\RelationManagers\ParticipantsRelationManager;
use App\Models\GuidanceSession;
use App\Models\Official;
use App\Support\ChurchContext;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuidanceSessionResource extends Resource
{
    protected static ?string $model = GuidanceSession::class;

    protected static ?string $modelLabel = 'Pertemuan Bimbingan';

    protected static ?string $pluralModelLabel = 'Pertemuan Bimbingan';

    protected static ?string $cluster = LifecycleCluster::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'lifecycle/bimbingan/sesi';

    protected static bool $shouldRegisterNavigation = true;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Pertemuan')
                    ->schema([
                        Select::make('program_id')
                            ->label('Program Bimbingan')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'program',
                                'title',
                                fn (Builder $query): Builder => ChurchContext::activeChurchId() !== null ? $query->where('church_id', ChurchContext::activeChurchId()) : $query,
                            )
                            ->native(false),
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
                                fn (Builder $query): Builder => ChurchContext::activeChurchId() !== null ? $query->where('church_id', ChurchContext::activeChurchId()) : $query,
                            )
                            ->getOptionLabelFromRecordUsing(fn (Official $record): string => $record->display_name)
                            ->native(false),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('program.title')
                    ->label('Program')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Topik')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('session_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('official.display_name')
                    ->label('Pembimbing')
                    ->placeholder('-'),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ParticipantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuidanceSessions::route('/'),
            'create' => Pages\CreateGuidanceSession::route('/create'),
            'edit' => Pages\EditGuidanceSession::route('/{record}/edit'),
        ];
    }
}
