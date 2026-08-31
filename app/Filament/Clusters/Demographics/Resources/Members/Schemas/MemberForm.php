<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Members\Schemas;

use App\Models\Church;
use App\Support\ChurchScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;

class MemberForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Jemaat')
                    ->description('Data pribadi dan status keanggotaan jemaat')
                    ->schema([
                        // AC-T3-14: church_id hanya untuk super_admin (create lintas gereja).
                        // Non-super_admin church_id diisi otomatis oleh BelongsToChurch.
                        Select::make('church_id')
                            ->label('Gereja')
                            ->options(fn (): array => Church::query()->pluck('name', 'id')->toArray())
                            ->searchable()
                            ->preload()
                            ->visible(fn (): bool => auth()->user()?->role === 'super_admin')
                            ->rules([Rule::exists('churches', 'id')])
                            ->nullable(),
                        Select::make('family_id')
                            ->label('Keluarga')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'family',
                                'name',
                                fn (Builder $query): Builder => ChurchScope::forActorSelect($query)
                            ),
                        TextInput::make('full_name')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('id_card_number')
                            ->label('Nomor KTP (NIK)')
                            ->unique(ignoreRecord: true)
                            ->placeholder('Contoh: 123.456.789-00')
                            ->nullable(),
                        Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->required()
                            ->options([
                                'm' => 'Laki-laki',
                                'f' => 'Perempuan',
                            ])
                            ->native(false),
                        TextInput::make('birth_place')
                            ->label('Tempat Lahir')
                            ->nullable()
                            ->maxLength(255),
                        DatePicker::make('birth_date')
                            ->label('Tanggal Lahir')
                            ->nullable(),
                    ])->columns(2),
                Section::make('Status Keanggotaan')
                    ->description('Informasi status dalam gereja')
                    ->schema([
                        Select::make('status')
                            ->label('Status Anggota')
                            ->required()
                            ->options([
                                'aktif' => 'Aktif',
                                'titipan' => 'Titipan',
                                'pindah' => 'Pindah',
                                'meninggal' => 'Meninggal',
                            ])
                            ->native(false),
                    ])->columns(1),
            ]);
    }
}
