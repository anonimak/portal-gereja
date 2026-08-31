<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Events\Resources\Event\RelationManagers;

use App\Models\EventAttendance;
use App\Models\Member;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Relation manager "Kehadiran" pada EventResource (Fase 2 Task 2).
 *
 * Check-in per anggota + check-in massal (skip duplikat). Semua query member
 * di-scope ke gereja event (owner record) — bukan gereja aktor — sehingga
 * super_admin yang membuka event gereja lain tetap mendapat member yang benar.
 *
 * AC-T2-08: form HANYA menerima member_id (create) dan notes (create/edit);
 * status, checked_in_at, checked_in_by TIDAK ada sebagai input — di-set
 * server-side (mutateFormDataBeforeCreate + booted creating). Perubahan status
 * dilakukan lewat aksi validasi "Tandai Hadir / Tidak Hadir" pada tabel.
 */
class AttendancesRelationManager extends RelationManager
{
    protected static string $relationship = 'attendances';

    protected static ?string $recordTitleAttribute = 'status';

    /**
     * church_id record mengikuti gereja event (owner record). checked_in_at dan
     * checked_in_by diisi otomatis (AC-T2-08) — tidak bergantung input form.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['church_id'] = $this->getOwnerRecord()->church_id;
        $data['status'] = 'hadir';
        $data['checked_in_at'] = now();
        $data['checked_in_by'] = auth()->id();

        return $data;
    }

    /**
     * Gereja yang dipakai untuk opsi member pada form/aksi RM.
     */
    private function ownerChurchId(): int
    {
        return (int) ($this->getOwnerRecord()?->church_id ?? auth()->user()?->church_id);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Detail Kehadiran')
                    ->schema([
                        Select::make('member_id')
                            ->label('Anggota')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->hiddenOn('edit')
                            // MED-1 Vera (re-review PR #5): member yang di-soft-delete TIDAK boleh
                            // dipilih untuk check-in — kalau dipilih, muncul row attendance
                            // dengan nama kosong (ghost row). member_id hiddenOn('edit')
                            // sehingga nilai lama tetap tersimpan tanpa perlu withTrashed.
                            // Konsisten dengan check-in massal yang juga mengecualikan trashed.
                            ->relationship(
                                'member',
                                'full_name',
                                fn (Builder $query): Builder => $query
                                    ->where('church_id', $this->ownerChurchId()),
                            ),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->rows(2)
                            ->nullable(),
                    ])
                    ->columns(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('status')
            ->columns([
                TextColumn::make('member.full_name')
                    ->label('Anggota')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'hadir' ? 'success' : 'danger')
                    ->formatStateUsing(fn (string $state): string => $state === 'hadir' ? 'Hadir' : 'Tidak Hadir'),
                TextColumn::make('checked_in_at')
                    ->label('Waktu Check-in')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('checkedInBy.name')
                    ->label('Dicentang Oleh')
                    ->toggleable(),
                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(40)
                    ->toggleable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    // Blocker Vera: single check-in memakai restore-or-create supaya
                    // member yang pernah di-soft-delete tetap bisa check-in ulang
                    // untuk event yang sama tanpa melanggar UNIQUE(event_id, member_id).
                    // church_id diisi ulang dari owner record (gereja event).
                    ->using(function (array $data, HasActions&HasSchemas $livewire): Model {
                        /** @var RelationManager $livewire */
                        $owner = $livewire->getOwnerRecord();

                        return EventAttendance::checkInOrRestore([
                            ...$data,
                            // Filament tidak menyuntikkan owner key saat memakai
                            // ->using() custom → set event_id dari owner record.
                            'event_id' => $owner->id,
                            'church_id' => $owner->church_id,
                        ]);
                    }),
                // Check-in massal: hanya member yang belum tercatat pada event ini
                // yang dibuat; duplikat dilewati (AC-T2-09) — lihat Event::checkInMembers().
                Action::make('checkInMassal')
                    ->label('Check-in Massal')
                    ->form([
                        Select::make('member_ids')
                            ->label('Pilih Anggota')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(fn (): array => Member::query()
                                ->where('church_id', $this->ownerChurchId())
                                ->where('status', 'aktif')
                                ->orderBy('full_name')
                                ->pluck('full_name', 'id')
                                ->all()),
                    ])
                    ->action(function (array $data): void {
                        $result = $this->getOwnerRecord()->checkInMembers($data['member_ids'] ?? []);

                        Notification::make()
                            ->title(sprintf(
                                'Check-in selesai: %d dicatat, %d dipulihkan (check-in ulang), %d dilewati (duplikat).',
                                $result['created'],
                                $result['restored'],
                                $result['skipped'],
                            ))
                            ->success()
                            ->send();
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                // AC-T2-08: perubahan status lewat aksi yang DIVALIDASI server-side,
                // bukan lewat input form. Dua aksi eksplisit: Tandai Hadir / Tidak Hadir.
                Action::make('tandaiHadir')
                    ->label('Tandai Hadir')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (EventAttendance $record): bool => $record->status !== 'hadir')
                    ->action(function (EventAttendance $record): void {
                        $record->update(['status' => 'hadir']);
                    }),
                Action::make('tandaiTidakHadir')
                    ->label('Tandai Tidak Hadir')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (EventAttendance $record): bool => $record->status !== 'tidak_hadir')
                    ->action(function (EventAttendance $record): void {
                        $record->update(['status' => 'tidak_hadir']);
                    }),
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
