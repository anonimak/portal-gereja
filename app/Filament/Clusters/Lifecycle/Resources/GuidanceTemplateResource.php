<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources;

use App\Filament\Clusters\Lifecycle\LifecycleCluster;
use App\Filament\Clusters\Lifecycle\Resources\GuidanceTemplate\Pages;
use App\Filament\Clusters\Lifecycle\Resources\GuidanceTemplate\RelationManagers\TemplateSessionsRelationManager;
use App\Models\GuidanceTemplate;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class GuidanceTemplateResource extends Resource
{
    protected static ?string $model = GuidanceTemplate::class;

    protected static ?string $modelLabel = 'Template Topik Bimbingan';

    protected static ?string $pluralModelLabel = 'Template Topik Bimbingan';

    protected static ?string $cluster = LifecycleCluster::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'lifecycle/template';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Template')
                    ->schema([
                        Select::make('type')
                            ->label('Jenis Bimbingan')
                            ->required()
                            ->options([
                                'pra_sidi' => 'Pra-Sidi',
                                'pra_nikah' => 'Pra-Nikah',
                            ])
                            ->disabled()
                            ->native(false),
                        TextInput::make('name')
                            ->label('Nama Template')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('session_count')
                            ->label('Jumlah Sesi')
                            ->numeric()
                            ->default(12),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Template')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'pra_sidi' ? 'Pra-Sidi' : 'Pra-Nikah'),
                TextColumn::make('session_count')
                    ->label('Jumlah Sesi'),
                TextColumn::make('is_default')
                    ->label('Default')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Ya' : 'Tidak')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])
            ->filters([
                \Filament\Tables\Filters\TrashedFilter::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TemplateSessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuidanceTemplates::route('/'),
            'create' => Pages\CreateGuidanceTemplate::route('/create'),
            'edit' => Pages\EditGuidanceTemplate::route('/{record}/edit'),
        ];
    }
}
