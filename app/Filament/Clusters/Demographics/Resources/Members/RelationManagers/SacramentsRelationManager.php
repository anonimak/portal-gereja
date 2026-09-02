<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Members\RelationManagers;

use App\Models\MemberSacrament;
use App\Models\Official;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class SacramentsRelationManager extends RelationManager
{
    protected static string $relationship = 'sacraments';

    protected static ?string $recordTitleAttribute = 'type';

    /**
     * Pastikan sakramen baru selalu mengikuti gereja member induk (owner record).
     *
     * HIGH-1 Vera: super_admin tidak ter-scope ke gereja sendiri — saat membuka
     * member gereja lain dan membuat sakramen, church_id harus mengikuti member,
     * bukan gereja aktor.
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['church_id'] = $this->getOwnerRecord()->church_id;

        return $data;
    }

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
                        Select::make('official_id')
                            ->label('Pendeta/Majelis')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            // HIGH Vera (re-review PR #1): display_name adalah accessor,
                            // bukan kolom. Memakai ->relationship('official', 'display_name')
                            // membuat Filament memanggil pluck('display_name') → SQL error.
                            // Solusi: title attribute = kolom nyata 'id' + label dari accessor
                            // via getOptionLabelFromRecordUsing (tidak di-pluck).
                            ->relationship('official', 'id')
                            ->getOptionLabelFromRecordUsing(fn (Official $record): string => $record->display_name),
                        Select::make('program_id')
                            ->label('Program Bimbingan (Pra-Sidi)')
                            ->helperText('Diisi saat menerbitkan Sidi / Baptis Dewasa (T8)')
                            ->nullable()
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'program',
                                'title',
                                function (Builder $query): Builder {
                                    $query->where('type', 'pra_sidi');

                                    // Super admin: ikut gereja yang dipilih (null = semua gereja).
                                    $activeChurchId = \App\Support\ChurchContext::activeChurchId();
                                    if ($activeChurchId !== null) {
                                        $query->where('church_id', $activeChurchId);
                                    }

                                    return $query;
                                }
                            ),
                        TextInput::make('certificate_number')
                            ->label('Nomor Sertifikat')
                            ->nullable(),
                        DatePicker::make('issued_at')
                            ->label('Tanggal Terbit Dokumen')
                            ->nullable(),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            // Scoping church_id dijamin global scope BelongsToChurch (T1):
            // query relasi sacramens dari member induk yang sudah ter-scope.
            ->columns([
                TextColumn::make('type')
                    ->label('Jenis Sakramen')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'penyerahan' => 'blue',
                        'baptis_anak' => 'cyan',
                        'sidi' => 'purple',
                        'baptis_dewasa' => 'green',
                        'nikah' => 'pink',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
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
                TextColumn::make('official.display_name')
                    ->label('Pendeta/Majelis')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('certificate_number')
                    ->label('Nomor Sertifikat')
                    ->searchable()
                    ->toggleable(),
            ])
            ->filters([
                // H3 Vera / AC-UI-01: filter untuk menampilkan record yang di-soft-delete.
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                Action::make('cetak_dokumen')
                    ->label('Cetak Dokumen')
                    ->icon('heroicon-o-document-arrow-down')
                    ->visible(fn (MemberSacrament $record): bool => in_array($record->type, ['baptis_anak', 'sidi', 'baptis_dewasa'], true))
                    ->url(function (MemberSacrament $record): string {
                        if ($record->type === 'baptis_anak') {
                            return route('sakramen.baptis-anak.export-pdf', $record->id);
                        }

                        return route('sakramen.sidi.export-pdf', $record->id);
                    })
                    ->openUrlInNewTab(),
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
