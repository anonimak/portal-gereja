<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Lifecycle\Resources\GuidanceTemplate\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

/**
 * Daftar topik template (session_number 1..N) — admin per gereja dapat mengedit
 * topik default (A14): nilai awal bisa diedit, template bukan dokumen mati.
 */
class TemplateSessionsRelationManager extends RelationManager
{
    protected static string $relationship = 'sessions';

    protected static ?string $recordTitleAttribute = 'topic';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Topik Pertemuan')
                    ->schema([
                        TextInput::make('session_number')
                            ->label('Nomor Urut (1..N)')
                            ->required()
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('topic')
                            ->label('Topik')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('session_number')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('topic')
                    ->label('Topik')
                    ->searchable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                CreateAction::make(),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
