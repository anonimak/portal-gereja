<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Members\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SacramentsRelationManager extends RelationManager
{
    protected static string $relationship = 'sacraments';

    protected static ?string $recordTitleAttribute = 'type';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Sacrament Details')
                    ->schema([
                        Select::make('type')
                            ->label('Jenis Sakramen')
                            ->required()
                            ->options([
                                'penyerahan' => 'Penyerahan',
                                'baptis_anak' => 'Baptis Anak',
                                'sidi' => 'Sidi',
                                'baptis_dewasa' => 'Baptis Dewasa',
                                'nikah' => 'Nikah',
                            ])
                            ->native(false),
                        DatePicker::make('sacrament_date')
                            ->label('Tanggal Sakramen')
                            ->required(),
                        TextInput::make('minister_name')
                            ->label('Nama Pendeta')
                            ->nullable(),
                        TextInput::make('certificate_number')
                            ->label('Nomor Sertifikat')
                            ->nullable(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                TextColumn::make('type')
                    ->label('Jenis Sakramen')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'penyerahan' => 'blue',
                        'baptis_anak' => 'cyan',
                        'sidi' => 'purple',
                        'baptis_dewasa' => 'green',
                        'nikah' => 'pink',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'penyerahan' => 'Penyerahan',
                        'baptis_anak' => 'Baptis Anak',
                        'sidi' => 'Sidi',
                        'baptis_dewasa' => 'Baptis Dewasa',
                        'nikah' => 'Nikah',
                        default => $state,
                    }),
                TextColumn::make('sacrament_date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),
                TextColumn::make('minister_name')
                    ->label('Pendeta')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('certificate_number')
                    ->label('Nomor Sertifikat')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make(),
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
}
