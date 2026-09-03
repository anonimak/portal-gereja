<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\AuditLog;

use App\Filament\Clusters\System\SystemCluster;
use App\Models\AuditLog;
use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Viewer audit trail (Fase 2 / Task A slot sore).
 *
 * - Read-only & append-only: tidak ada create/edit/delete — hanya list + detail.
 * - Cluster System => hanya Super Admin (lihat LOW-2 review Vera).
 * - Isolasi tenant: global scope AuditLog membatasi church_id untuk non-super_admin;
 *   super_admin melihat semua gereja (kolom Gereja ditampilkan).
 */
class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $modelLabel = 'Audit Trail';

    protected static ?string $pluralModelLabel = 'Audit Trail';

    protected static ?string $cluster = SystemCluster::class;

    protected static ?int $navigationSort = 30;

    /**
     * Hanya Super Admin yang boleh membuka viewer audit (LOW-2).
     */
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->role === 'super_admin';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('action')
                    ->label('Aksi')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted', 'force_deleted' => 'danger',
                        'restored' => 'primary',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable(),
                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('auditable_id')
                    ->label('ID Record')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('church.name')
                    ->label('Gereja')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label('Aksi')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                        'restored' => 'Restored',
                        'force_deleted' => 'Force Deleted',
                    ]),
                SelectFilter::make('auditable_type')
                    ->label('Model')
                    ->options(fn (): array => AuditLog::query()
                        ->select('auditable_type')
                        ->distinct()
                        ->orderBy('auditable_type')
                        ->pluck('auditable_type')
                        ->mapWithKeys(fn (string $t): array => [$t => class_basename($t)])
                        ->all()),
                SelectFilter::make('user_id')
                    ->label('User')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label('Detail'),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
