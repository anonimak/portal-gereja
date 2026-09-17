<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Finance\Resources\OnlineOffering;

use App\Filament\Clusters\Finance\FinanceCluster;
use App\Filament\Clusters\Finance\Resources\OnlineOffering\Pages\ListOnlineOfferings;
use App\Filament\Clusters\Finance\Resources\OnlineOffering\Pages\ViewOnlineOffering;
use App\Models\Church;
use App\Models\FinancialCategory;
use App\Models\Fund;
use App\Models\OnlineOffering;
use App\Support\ChurchScope;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OnlineOfferingResource extends Resource
{
    protected static ?string $model = OnlineOffering::class;

    protected static ?string $modelLabel = 'Persembahan Online';

    protected static ?string $pluralModelLabel = 'Persembahan Online';

    protected static ?string $cluster = FinanceCluster::class;

    protected static ?int $navigationSort = 8;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Rincian Persembahan')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('church_id')
                                ->label('Gereja')
                                ->options(fn (): array => Church::query()->pluck('name', 'id')->toArray())
                                ->visible(fn (): bool => auth()->user()?->role === 'super_admin')
                                ->disabled(),
                            TextInput::make('reference_code')
                                ->label('Nomor Referensi')
                                ->disabled(),
                            TextInput::make('donor_name')
                                ->label('Nama Jemaat / Donatur')
                                ->disabled(),
                            TextInput::make('amount')
                                ->label('Nominal (Rp)')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                                ->disabled(),
                            TextInput::make('payment_method')
                                ->label('Metode Pembayaran')
                                ->formatStateUsing(fn ($state) => strtoupper((string) $state))
                                ->disabled(),
                            TextInput::make('bank_name')
                                ->label('Bank / E-Wallet')
                                ->disabled(),
                            Select::make('fund_id')
                                ->label('Pos Kantong Kas')
                                ->relationship('fund', 'name')
                                ->disabled(),
                            Select::make('financial_category_id')
                                ->label('Kategori')
                                ->relationship('financialCategory', 'name')
                                ->disabled(),
                        ]),
                        Textarea::make('prayer_notes')
                            ->label('Pokok Doa / Ucapan Syukur')
                            ->disabled()
                            ->columnSpanFull(),
                    ]),

                Section::make('Verifikasi & Bukti')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('status')
                                ->label('Status Verifikasi')
                                ->formatStateUsing(fn ($state) => match ($state) {
                                    'confirmed' => 'Dikonfirmasi (Lunas)',
                                    'rejected' => 'Ditolak',
                                    default => 'Menunggu Verifikasi',
                                })
                                ->disabled(),
                            TextInput::make('confirmed_at')
                                ->label('Waktu Dikonfirmasi')
                                ->disabled(),
                            TextInput::make('rejection_reason')
                                ->label('Alasan Penolakan')
                                ->visible(fn ($record) => filled($record?->rejection_reason))
                                ->disabled()
                                ->columnSpanFull(),
                        ]),
                        FileUpload::make('proof_path')
                            ->label('Foto / Bukti Transfer')
                            ->disk('public')
                            ->image()
                            ->disabled()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('reference_code')
                    ->label('Kode Ref')
                    ->searchable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('church.name')
                    ->label('Gereja')
                    ->visible(fn (): bool => auth()->user()?->role === 'super_admin')
                    ->sortable(),
                TextColumn::make('donor_name')
                    ->label('Donatur')
                    ->searchable(),
                TextColumn::make('fund.name')
                    ->label('Kantong Kas')
                    ->sortable(),
                TextColumn::make('financialCategory.name')
                    ->label('Kategori')
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format((int) $state, 0, ',', '.'))
                    ->sortable()
                    ->weight('black')
                    ->color('success'),
                TextColumn::make('payment_method')
                    ->label('Metode')
                    ->badge()
                    ->formatStateUsing(fn ($state) => strtoupper((string) $state)),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'confirmed' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'confirmed' => 'Dikonfirmasi',
                        'rejected' => 'Ditolak',
                        default => 'Menunggu',
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Menunggu',
                        'confirmed' => 'Dikonfirmasi',
                        'rejected' => 'Ditolak',
                    ]),
                SelectFilter::make('fund_id')
                    ->label('Kantong Kas')
                    ->relationship('fund', 'name'),
                TrashedFilter::make(),
            ])
            ->actions([
                ViewAction::make(),
                Action::make('confirm')
                    ->label('Konfirmasi')
                    ->icon(Heroicon::CheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Konfirmasi Penerimaan Persembahan')
                    ->modalDescription('Tindakan ini akan memverifikasi persembahan dan otomatis mencatat kas masuk (debit) ke pembukuan keuangan.')
                    ->visible(fn (OnlineOffering $record): bool => $record->status === 'pending')
                    ->action(function (OnlineOffering $record): void {
                        $record->confirm(auth()->user());
                        Notification::make()
                            ->title('Persembahan berhasil dikonfirmasi')
                            ->body("Transaksi kas masuk telah otomatis dicatat untuk {$record->donor_name}.")
                            ->success()
                            ->send();
                    }),
                Action::make('reject')
                    ->label('Tolak')
                    ->icon(Heroicon::XCircle)
                    ->color('danger')
                    ->form([
                        Textarea::make('rejection_reason')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->visible(fn (OnlineOffering $record): bool => $record->status === 'pending')
                    ->action(function (OnlineOffering $record, array $data): void {
                        $record->reject(auth()->user(), $data['rejection_reason']);
                        Notification::make()
                            ->title('Persembahan ditolak')
                            ->body("Persembahan {$record->reference_code} telah ditandai ditolak.")
                            ->warning()
                            ->send();
                    }),
                DeleteAction::make(),
                RestoreAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOnlineOfferings::route('/'),
            'view' => ViewOnlineOffering::route('/{record}'),
        ];
    }
}
