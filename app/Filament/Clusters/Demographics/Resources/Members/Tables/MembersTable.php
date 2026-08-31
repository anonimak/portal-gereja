<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Members\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MembersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')
                    ->label('Nama Lengkap')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('family.name')
                    ->label('Keluarga')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('id_card_number')
                    ->label('KTP/NIK')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('gender')
                    ->label('Kelamin')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'm' ? 'blue' : 'pink')
                    ->formatStateUsing(fn (string $state): string => $state === 'm' ? 'Laki-laki' : 'Perempuan'),
                TextColumn::make('birth_date')
                    ->label('Lahir')
                    ->date('d M Y')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'aktif' => 'success',
                        'titipan' => 'warning',
                        'pindah' => 'info',
                        'meninggal' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'aktif' => 'Aktif',
                        'titipan' => 'Titipan',
                        'pindah' => 'Pindah',
                        'meninggal' => 'Meninggal',
                        default => $state,
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status Anggota')
                    ->options([
                        'aktif' => 'Aktif',
                        'titipan' => 'Titipan',
                        'pindah' => 'Pindah',
                        'meninggal' => 'Meninggal',
                    ]),
                SelectFilter::make('gender')
                    ->label('Jenis Kelamin')
                    ->options([
                        'm' => 'Laki-laki',
                        'f' => 'Perempuan',
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
}
