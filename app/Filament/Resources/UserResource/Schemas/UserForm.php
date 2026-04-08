<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Schemas;

use App\Modules\Tenant\Enums\UserRole;
use App\Modules\Tenant\Models\Tenant;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        $user = Auth::user();
        $isSuperAdmin = $user && $user->isSuperAdmin();

        return $schema
            ->components([
                Section::make('User Information')
                    ->columns()
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        Select::make('tenant_id')
                            ->relationship('tenant', 'name')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->default(fn () => $isSuperAdmin ? null : $user?->tenant_id)
                            ->disabled(!$user?->canTransferUserBetweenTenants())
                            ->dehydrated()
                            ->helperText($user?->canTransferUserBetweenTenants() ? 'SuperAdmin can assign users to any tenant' : 'Users will be created in your tenant'),

                        Select::make('role')
                            ->options(function (Get $get) use ($user) {
                                $roles = collect(UserRole::cases());
                                $selectedTenantId = $get('tenant_id');

                                if ($user && $user->isSuperAdmin()) {
                                    if (!$selectedTenantId) {
                                        return $roles->mapWithKeys(fn($role) => [
                                            $role->value => $role->label()
                                        ]);
                                    }

                                    $selectedTenant = Tenant::find($selectedTenantId);

                                    if ($selectedTenant && $selectedTenant->isSystemTenant()) {
                                        return $roles->mapWithKeys(fn($role) => [
                                            $role->value => $role->label()
                                        ]);
                                    }

                                    $roles = $roles->filter(fn($role) => $role !== UserRole::SUPERADMIN);
                                    return $roles->mapWithKeys(fn($role) => [
                                        $role->value => $role->label()
                                    ]);
                                }

                                if ($user && $user->isHead()) {
                                    $roles = $roles->filter(fn($role) =>
                                        in_array($role, [UserRole::STAFF, UserRole::MANAGER, UserRole::DIRECTOR])
                                    );
                                }

                                return $roles->mapWithKeys(fn($role) => [
                                    $role->value => $role->label()
                                ]);
                            })
                            ->required()
                            ->native(false)
                            ->live()
                            ->helperText(function (Get $get) use ($user) {
                                if ($user && $user->isSuperAdmin()) {
                                    $selectedTenantId = $get('tenant_id');
                                    if (!$selectedTenantId) {
                                        return 'Select a tenant first';
                                    }

                                    $selectedTenant = Tenant::find($selectedTenantId);
                                    if ($selectedTenant && $selectedTenant->isSystemTenant()) {
                                        return 'System tenant: can assign any role including SuperAdmin';
                                    }

                                    return 'Client tenant: can assign up to Head role';
                                }
                                return 'You can only assign Staff, Manager, or Director roles';
                            }),

                        TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->revealable()
                            ->helperText('Leave blank to keep current password (on edit)'),
                    ]),
            ]);
    }
}
