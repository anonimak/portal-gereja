<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Finance\Resources\Transaction;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Models\Transaction;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $modelLabel = 'Transaksi';

    protected static ?string $pluralModelLabel = 'Transaksi';

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::ArrowsRightLeft;

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Select::make('fund_id')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->relationship(
                        'fund',
                        'name',
                        fn (Builder $query) => $query->where('church_id', auth()->user()->church_id)
                    ),
                Select::make('type')
                    ->required()
                    ->options([
                        'debit' => 'Pemasukan (Debit)',
                        'credit' => 'Pengeluaran (Kredit)',
                    ])
                    ->live(),
                Select::make('category_id')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->disabled(fn (Get $get) => empty($get('type')))
                    ->placeholder(fn (Get $get) => empty($get('type')) ? 'Pilih Tipe Transaksi terlebih dahulu' : 'Pilih Kategori')
                    ->relationship(
                        'category',
                        'name',
                        fn (Builder $query, Get $get) => $query
                            ->where('church_id', auth()->user()->church_id)
                            ->where('type', $get('type'))
                    ),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->minValue(0),
                DatePicker::make('transaction_date')
                    ->required()
                    ->default(today()),
                Textarea::make('description')
                    ->required()
                    ->maxLength(1000),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('fund.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('category.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'debit' => 'Pemasukan',
                        'credit' => 'Pengeluaran',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'debit' => 'success',
                        'credit' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('amount')
                    ->money('idr', locale: 'id')
                    ->sortable(),
                TextColumn::make('description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: false),
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
                SelectFilter::make('fund_id')
                    ->relationship(
                        'fund',
                        'name',
                        fn (Builder $query) => $query->where('church_id', auth()->user()->church_id)
                    ),
                SelectFilter::make('type')
                    ->options([
                        'debit' => 'Pemasukan',
                        'credit' => 'Pengeluaran',
                    ]),
                // H3 Vera / AC-UI-01: filter untuk menampilkan record yang di-soft-delete.
                TrashedFilter::make(),
            ])
            ->recordActions([
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
