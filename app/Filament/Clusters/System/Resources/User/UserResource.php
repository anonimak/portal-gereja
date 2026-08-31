<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\User;

use App\Filament\Clusters\System\Resources\User\Pages;
use App\Filament\Clusters\System\SystemCluster;
use App\Models\Church;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $modelLabel = 'User';

    protected static ?string $pluralModelLabel = 'Users';

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static ?string $cluster = SystemCluster::class;

    protected static ?int $navigationSort = 20;

    /**
     * Hanya Super Admin yang dapat mengelola user.
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
        return static::canViewAny() && $record?->id !== auth()->id();
    }

    public static function canDeleteAny(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        $isSuperAdmin = auth()->user()?->role === 'super_admin';

        return $schema
            ->schema([
                Section::make('User Information')
                    ->schema([
                        TextInput::make('name')
                            ->label('Name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(table: 'users', column: 'email', ignoreRecord: true)
                            ->maxLength(255),

                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn(null|string $state): null|string => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn(null|string $state): bool => filled($state))
                            ->required(fn(string $operation): bool => $operation === 'create')
                            ->hidden(fn(string $operation): bool => $operation === 'edit')
                            ->maxLength(255),

                        Select::make('role')
                            ->label('Role')
                            ->options(
                                fn(): array => $isSuperAdmin
                                    ? [
                                        'super_admin' => 'Super Admin',
                                        'church_admin' => 'Church Admin',
                                        'finance_admin' => 'Finance Admin',
                                    ]
                                    : [
                                        'church_admin' => 'Church Admin',
                                        'finance_admin' => 'Finance Admin',
                                    ]
                            )
                            ->rules([
                                Rule::in(['super_admin', 'church_admin', 'finance_admin']),
                            ])
                            ->required()
                            ->default('church_admin'),

                        Select::make('church_id')
                            ->label('Church')
                            ->preload()
                            ->options(fn(): array => Church::query()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->visible(fn(): bool => $isSuperAdmin)
                            ->rules([
                                Rule::exists('churches', 'id'),
                            ])
                            ->nullable(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isSuperAdmin = auth()->user()?->role === 'super_admin';

        return $table
            ->modifyQueryUsing(
                fn(Builder $query): Builder => $isSuperAdmin
                    ? $query
                    : $query->where('church_id', auth()->user()->church_id)
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Role')
                    ->badge()
                    ->sortable()
                    ->color(fn(string $state): string => match ($state) {
                        'super_admin' => 'danger',
                        'church_admin' => 'success',
                        'finance_admin' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'super_admin' => 'Super Admin',
                        'church_admin' => 'Church Admin',
                        'finance_admin' => 'Finance Admin',
                        default => $state,
                    }),

                TextColumn::make('church.name')
                    ->label('Church')
                    ->searchable()
                    ->sortable()
                    ->visible(fn(): bool => $isSuperAdmin),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Role')
                    ->options([
                        'super_admin' => 'Super Admin',
                        'church_admin' => 'Church Admin',
                        'finance_admin' => 'Finance Admin',
                    ]),

                SelectFilter::make('church_id')
                    ->label('Church')
                    ->options(fn(): array => Church::query()->pluck('name', 'id')->toArray())
                    ->visible(fn(): bool => $isSuperAdmin),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}'),
        ];
    }
}
