<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources;

use App\Filament\Clusters\Lifecycle\LifecycleCluster;
use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgram\Pages;
use App\Filament\Clusters\Lifecycle\Resources\GuidanceProgram\RelationManagers\SessionsRelationManager;
use App\Models\GuidanceProgram;
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
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class GuidanceProgramResource extends Resource
{
    protected static ?string $model = GuidanceProgram::class;

    protected static ?string $modelLabel = 'Program Bimbingan';

    protected static ?string $pluralModelLabel = 'Program Bimbingan';

    protected static ?string $cluster = LifecycleCluster::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $slug = 'lifecycle/bimbingan';

    public static function getNavigationLabel(): string
    {
        return 'Bimbingan (Pra-Sidi/Pra-Nikah)';
    }

    public static function form(Schema $schema): Schema
    {
        $user = auth()->user();

        return $schema
            ->schema([
                Section::make('Program Bimbingan')
                    ->schema([
                        Select::make('type')
                            ->label('Jenis Bimbingan')
                            ->required()
                            ->options([
                                'pra_sidi' => 'Pra-Sidi (Katakisasi)',
                                'pra_nikah' => 'Pra-Nikah',
                            ])
                            ->live()
                            ->native(false),
                        TextInput::make('title')
                            ->label('Nama Program')
                            ->required()
                            ->maxLength(255),
                        Select::make('status')
                            ->label('Status')
                            ->required()
                            ->default('draft')
                            ->options([
                                'draft' => 'Draft',
                                'berjalan' => 'Berjalan',
                                'selesai' => 'Selesai',
                                'batal' => 'Batal',
                            ])
                            ->native(false),
                        Select::make('template_id')
                            ->label('Template Topik')
                            ->placeholder('Pilih template (opsional) — sesi dibuat otomatis 1..N')
                            ->searchable()
                            ->preload()
                            ->relationship(
                                'template',
                                'name',
                                fn (Builder $query): Builder => $query->where('church_id', $user->church_id)
                            )
                            ->helperText('Pilih template lalu simpan: sistem membuat sesi otomatis sesuai topik template.')
                            ->native(false),
                        DatePicker::make('start_date')
                            ->label('Tanggal Mulai')
                            ->nullable(),
                        DatePicker::make('end_date')
                            ->label('Tanggal Selesai')
                            ->nullable(),
                        \Filament\Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->nullable()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Nama Program')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Jenis')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'pra_sidi' ? 'Pra-Sidi' : 'Pra-Nikah')
                    ->color(fn (string $state): string => $state === 'pra_sidi' ? 'primary' : 'info'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'berjalan' => 'info',
                        'selesai' => 'success',
                        'batal' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('sessions_count')
                    ->label('Jumlah Pertemuan')
                    ->counts('sessions'),
                TextColumn::make('template.name')
                    ->label('Template')
                    ->placeholder('-'),
                TextColumn::make('start_date')
                    ->label('Mulai')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
                TextColumn::make('end_date')
                    ->label('Selesai')
                    ->date()
                    ->sortable()
                    ->placeholder('-'),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            SessionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGuidancePrograms::route('/'),
            'create' => Pages\CreateGuidanceProgram::route('/create'),
            'edit' => Pages\EditGuidanceProgram::route('/{record}/edit'),
        ];
    }
}
