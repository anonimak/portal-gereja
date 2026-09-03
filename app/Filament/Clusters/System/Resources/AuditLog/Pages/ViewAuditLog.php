<?php

declare(strict_types=1);

namespace App\Filament\Clusters\System\Resources\AuditLog\Pages;

use App\Filament\Clusters\System\Resources\AuditLog\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    /**
     * Detail perubahan before/after — diff lama vs baru.
     */
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Audit')
                    ->schema([
                        \Filament\Forms\Components\TextInput::make('id')->disabled(),
                        \Filament\Forms\Components\TextInput::make('created_at')->disabled(),
                        \Filament\Forms\Components\TextInput::make('action')->disabled(),
                        \Filament\Forms\Components\TextInput::make('auditable_type')->disabled(),
                        \Filament\Forms\Components\TextInput::make('auditable_id')->disabled(),
                        \Filament\Forms\Components\TextInput::make('user.name')->disabled(),
                        \Filament\Forms\Components\TextInput::make('church.name')->disabled(),
                        \Filament\Forms\Components\TextInput::make('ip_address')->disabled(),
                    ])->columns(2),
                Section::make('Perubahan (before / after)')
                    ->schema([
                        \Filament\Forms\Components\KeyValue::make('old_values')
                            ->label('Nilai Lama')
                            ->disabled(),
                        \Filament\Forms\Components\KeyValue::make('new_values')
                            ->label('Nilai Baru')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }
}
