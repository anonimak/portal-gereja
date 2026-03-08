<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Family;

use App\Filament\Clusters\Demographics\DemographicsCluster;
use App\Filament\Clusters\Demographics\Resources\Family\Pages;
use App\Models\Family;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FamilyResource extends Resource
{
    protected static ?string $model = Family::class;

    protected static ?string $modelLabel = 'Keluarga';

    protected static ?string $pluralModelLabel = 'Keluarga';

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static ?string $cluster = DemographicsCluster::class;

    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Family Information')
                    ->schema([
                        TextInput::make('family_number')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('address')
                            ->required()
                            ->maxLength(500),
                    ]),

                Section::make('Members')
                    ->schema([
                        Repeater::make('members')
                            ->relationship()
                            ->schema([
                                TextInput::make('full_name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('id_card_number')
                                    ->label('ID Card Number')
                                    ->maxLength(255),
                                Select::make('gender')
                                    ->options([
                                        'm' => 'Laki-laki',
                                        'f' => 'Perempuan',
                                    ]),
                                TextInput::make('birth_place')
                                    ->maxLength(255),
                                DatePicker::make('birth_date'),
                                Select::make('family_relation')
                                    ->required()
                                    ->options([
                                        'kepala_keluarga' => 'Kepala Keluarga',
                                        'istri' => 'Istri',
                                        'anak' => 'Anak',
                                        'lainnya' => 'Lainnya',
                                    ]),
                                Select::make('status')
                                    ->required()
                                    ->options([
                                        'aktif' => 'Aktif',
                                        'titipan' => 'Titipan',
                                        'pindah' => 'Pindah',
                                        'meninggal' => 'Meninggal',
                                    ])
                                    ->default('aktif'),
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
                TextColumn::make('family_number')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('address')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('members_count')
                    ->counts('members')
                    ->label('Members'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFamilies::route('/'),
            'create' => Pages\CreateFamily::route('/create'),
            'edit' => Pages\EditFamily::route('/{record}/edit'),
        ];
    }
}
