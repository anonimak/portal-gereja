<?php

declare(strict_types=1);

namespace App\Filament\Clusters\Demographics\Resources\Members;

use App\Filament\Clusters\Demographics\DemographicsCluster;
use App\Filament\Clusters\Demographics\Resources\Members\Pages\CreateMember;
use App\Filament\Clusters\Demographics\Resources\Members\Pages\EditMember;
use App\Filament\Clusters\Demographics\Resources\Members\Pages\ListMembers;
use App\Filament\Clusters\Demographics\Resources\Members\RelationManagers\SacramentsRelationManager;
use App\Filament\Clusters\Demographics\Resources\Members\Schemas\MemberForm;
use App\Filament\Clusters\Demographics\Resources\Members\Tables\MembersTable;
use App\Models\Member;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MemberResource extends Resource
{
    protected static ?string $model = Member::class;

    protected static ?string $modelLabel = 'Anggota Jemaat';

    protected static ?string $pluralModelLabel = 'Anggota Jemaat';

    protected static ?int $navigationSort = 3;

    // protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $cluster = DemographicsCluster::class;


    public static function form(Schema $schema): Schema
    {
        return MemberForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MembersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SacramentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMembers::route('/'),
            'create' => CreateMember::route('/create'),
            'edit' => EditMember::route('/{record}/edit'),
        ];
    }
}
