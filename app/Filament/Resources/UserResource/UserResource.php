<?php

namespace App\Filament\Resources\UserResource;

use App\Filament\Resources\UserResource\Schemas\UserForm;
use App\Filament\Resources\UserResource\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|null|UnitEnum $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        $user = Auth::user();
        return $user && ($user->isSuperAdmin() || $user->isHead());
    }

    public static function canCreate(): bool
    {
        $user = Auth::user();
        return $user && ($user->isSuperAdmin() || $user->isHead());
    }

    public static function canEdit($record): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        return $user->canManageUser($record);
    }

    public static function canDelete($record): bool
    {
        $user = Auth::user();
        if (!$user) {
            return false;
        }

        if ($user->id === $record->id) {
            return false;
        }

        return $user->canManageUser($record);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['tenant']);

        $user = Auth::user();

        if ($user && $user->isSuperAdmin()) {
            return $query;
        }

        if ($user && $user->isHead()) {
            return $query->where('tenant_id', $user->tenant_id);
        }

        return $query->whereRaw('1 = 0');
    }
}
