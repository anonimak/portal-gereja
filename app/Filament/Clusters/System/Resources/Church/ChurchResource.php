<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\Church;

use App\Filament\Clusters\System\Resources\Church\Pages;
use App\Filament\Clusters\System\SystemCluster;
use App\Models\Church;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ChurchResource extends Resource
{
    protected static ?string $model = Church::class;

    protected static ?string $modelLabel = 'Gereja';

    protected static ?string $pluralModelLabel = 'Gereja';

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    protected static ?string $cluster = SystemCluster::class;

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public static function canUpdate(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Church Information')
                    ->schema([
                        TextInput::make('code')
                            ->label('Church Code')
                            ->required()
                            ->unique(table: 'churches', column: 'code', ignoreRecord: true)
                            ->maxLength(50),

                        TextInput::make('name')
                            ->label('Church Name')
                            ->required()
                            ->maxLength(255),

                        Textarea::make('address')
                            ->label('Address')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->maxLength(20),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Phone')
                    ->copyable()
                    ->copyableState(fn(string $state): string => $state),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListChurches::route('/'),
            'create' => Pages\CreateChurch::route('/create'),
            'edit' => Pages\EditChurch::route('/{record}'),
        ];
    }
}
