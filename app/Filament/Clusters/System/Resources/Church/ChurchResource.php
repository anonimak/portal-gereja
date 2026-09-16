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
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChurchResource extends Resource
{
    protected static ?string $model = Church::class;

    protected static ?string $modelLabel = 'Gereja';

    protected static ?string $pluralModelLabel = 'Gereja';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?int $navigationSort = 10;

    public static function canViewAny(): bool
    {
        return in_array(auth()->user()?->role, ['super_admin', 'church_admin'], true);
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public static function canUpdate(Model $record): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->role === 'super_admin') {
            return true;
        }

        return $user->role === 'church_admin' && (int) $user->church_id === (int) $record->id;
    }

    public static function canDelete(Model $record): bool
    {
        return auth()->user()?->role === 'super_admin';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user && $user->role === 'church_admin') {
            $query->where('id', $user->church_id);
        }

        return $query;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Identitas Resmi & Kop Dokumen')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo Gereja')
                            ->disk('public')
                            ->directory('church-logos')
                            ->visibility('public')
                            ->image()
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/svg+xml'])
                            ->helperText('Format PNG/JPG/SVG transparan. Digunakan untuk Kop Surat Resmi dokumen cetak dan PDF.')
                            ->columnSpanFull(),

                        TextInput::make('name')
                            ->label('Nama Jemaat / Gereja')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('synod')
                            ->label('Sinode / Klasis / Wilayah')
                            ->placeholder('Contoh: Sinode GKSBS / Klasis Tulang Bawang')
                            ->maxLength(255),

                        TextInput::make('code')
                            ->label('Kode Gereja')
                            ->required()
                            ->unique(table: 'churches', column: 'code', ignoreRecord: true)
                            ->maxLength(50)
                            ->disabled(fn () => auth()->user()?->role !== 'super_admin')
                            ->dehydrated(),
                    ])
                    ->columns(2),

                Section::make('Kontak & Kesekretariatan')
                    ->schema([
                        Textarea::make('address')
                            ->label('Alamat Lengkap Gereja')
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('phone')
                            ->label('No. Telepon Sekretariat')
                            ->tel()
                            ->maxLength(20),

                        TextInput::make('email')
                            ->label('Email Resmi Gereja')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('website')
                            ->label('Website Gereja')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama Gereja')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('synod')
                    ->label('Sinode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('phone')
                    ->label('Telepon')
                    ->copyable()
                    ->copyableState(fn(string $state): string => $state),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->visible(fn () => auth()->user()?->role === 'super_admin'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->visible(fn () => auth()->user()?->role === 'super_admin'),
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
