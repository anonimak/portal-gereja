<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\Official;

use App\Filament\Clusters\System\Resources\Official\Pages;
use App\Filament\Clusters\System\SystemCluster;
use App\Models\Official;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OfficialResource extends Resource
{
    protected static ?string $model = Official::class;

    protected static ?string $modelLabel = 'Pelayan Gereja';

    protected static ?string $pluralModelLabel = 'Pelayan Gereja';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?int $navigationSort = 3;

    /**
     * Hanya Super Admin yang dapat mengelola Official (cluster System).
     */
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->role === 'super_admin';
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit($record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete($record): bool
    {
        return static::canViewAny();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Data Pelayan Gereja')
                    ->schema([
                        Select::make('type')
                            ->label('Tipe Pelayan')
                            ->required()
                            ->options([
                                'majelis_lokal' => 'Majelis Lokal (Dari Jemaat)',
                                'pendeta_internal' => 'Pendeta Internal/Sinode',
                                'pelayan_tamu' => 'Pelayan Tamu (Luar Jemaat)',
                            ])
                            ->live()
                            ->native(false),
                        Select::make('member_id')
                            ->label('Anggota Jemaat')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->hidden(fn(Get $get): bool => $get('type') !== 'majelis_lokal')
                            ->relationship(
                                'member',
                                'full_name',
                                fn(Builder $query): Builder => $query->where('church_id', auth()->user()->church_id)
                            ),
                        TextInput::make('external_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255)
                            ->hidden(fn(Get $get): bool => !in_array($get('type'), ['pendeta_internal', 'pelayan_tamu'])),
                        TextInput::make('origin_church')
                            ->label('Asal Gereja/Wilayah')
                            ->required()
                            ->maxLength(255)
                            ->hidden(fn(Get $get): bool => $get('type') !== 'pelayan_tamu')
                            ->placeholder('Contoh: Gereja Trimulyo, GKI Ahmad Yani'),
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai Melayani')
                            ->required(),
                        DatePicker::make('end_date')
                            ->label('Tanggal Akhir Melayani')
                            ->nullable()
                            ->helperText('Kosongkan jika ini adalah Pelayan Tamu sekali jalan, atau jika masih aktif menjabat'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')
                    ->label('Nama Pelayan')
                    ->searchable(['external_name', 'member.full_name', 'origin_church'])
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipe Pelayan')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'majelis_lokal' => 'Majelis Lokal',
                        'pendeta_internal' => 'Pendeta Internal',
                        'pelayan_tamu' => 'Pelayan Tamu',
                        default => $state,
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'majelis_lokal' => 'success',
                        'pendeta_internal' => 'primary',
                        'pelayan_tamu' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('start_date')
                    ->label('Tanggal Mulai Melayani')
                    ->date()
                    ->sortable(),
                TextColumn::make('end_date')
                    ->label('Tanggal Akhir Melayani')
                    ->date()
                    ->sortable()
                    ->formatStateUsing(fn(?string $state): string => $state ? $state : 'Aktif'),
                TextColumn::make('created_at')
                    ->label('Dibuat Pada')
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
                CreateAction::make(),
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
            'index' => Pages\ListOfficials::route('/'),
            'create' => Pages\CreateOfficial::route('/create'),
            'edit' => Pages\EditOfficial::route('/{record}/edit'),
        ];
    }
}
