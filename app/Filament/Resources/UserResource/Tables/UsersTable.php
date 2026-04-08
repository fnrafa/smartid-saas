<?php

namespace App\Filament\Resources\UserResource\Tables;

use App\Modules\Tenant\Enums\UserRole;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        $user = Auth::user();

        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tenant.name')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->visible($user && $user->isSuperAdmin()),

                BadgeColumn::make('role')
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->colors([
                        'danger' => UserRole::SUPERADMIN->value,
                        'success' => UserRole::HEAD->value,
                        'warning' => UserRole::DIRECTOR->value,
                        'primary' => UserRole::MANAGER->value,
                        'gray' => UserRole::STAFF->value,
                    ])
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options(function () use ($user) {
                        $roles = collect(UserRole::cases());

                        if (!$user || !$user->isSuperAdmin()) {
                            $roles = $roles->filter(fn($role) => $role !== UserRole::SUPERADMIN);
                        }

                        return $roles->mapWithKeys(fn($role) => [
                            $role->value => $role->label()
                        ]);
                    })
                    ->native(false),

                SelectFilter::make('tenant')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload()
                    ->visible($user && $user->isSuperAdmin()),
            ])
            ->recordAction(EditAction::class)
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
