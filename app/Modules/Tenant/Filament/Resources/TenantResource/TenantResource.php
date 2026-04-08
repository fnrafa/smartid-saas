<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Filament\Resources\TenantResource;

use App\Modules\Tenant\Filament\Resources\TenantResource\Schemas\TenantForm;
use App\Modules\Tenant\Filament\Resources\TenantResource\Tables\TenantsTable;
use App\Modules\Tenant\Models\Tenant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TenantResource extends Resource
{
    protected static ?string $model = Tenant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|null|UnitEnum $navigationGroup = 'Tenant Management';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return TenantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TenantsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenants::route('/'),
            'create' => Pages\CreateTenant::route('/create'),
            'edit' => Pages\EditTenant::route('/{record}/edit'),
            'view' => Pages\ViewTenant::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['activeSubscription.tier'])
            ->withCount(['users', 'documents']);
    }
}
