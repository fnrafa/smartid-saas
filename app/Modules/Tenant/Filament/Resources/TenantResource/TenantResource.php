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
use Illuminate\Support\Facades\Auth;
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

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && ($user->canManageTenants() || $user->isHead());
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && $user->canManageTenants();
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->canManageTenants()) {
            return true;
        }

        if ($user->isHead() && $user->tenant_id === $record->id) {
            return true;
        }

        return false;
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        
        if (!$user || !$user->canManageTenants()) {
            return false;
        }

        if ($record->isSystemTenant()) {
            return false;
        }

        return true;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        $query = parent::getEloquentQuery()
            ->with(['activeSubscription.tier']);

        if ($user && $user->canManageTenants()) {
            $query->withCount([
                'users',
                'documents' => function ($query) {
                    $query->withoutGlobalScopes();
                }
            ]);
            return $query;
        }

        if ($user && $user->isHead()) {
            $query->withCount(['users', 'documents']);
            return $query->where('id', $user->tenant_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
