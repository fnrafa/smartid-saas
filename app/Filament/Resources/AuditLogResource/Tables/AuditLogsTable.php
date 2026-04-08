<?php

namespace App\Filament\Resources\AuditLogResource\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AuditLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                BadgeColumn::make('event')
                    ->colors([
                        'success' => 'created',
                        'warning' => 'updated',
                        'danger' => 'deleted',
                    ])
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->default('System'),

                TextColumn::make('auditable_type')
                    ->label('Model')
                    ->formatStateUsing(fn (string $state): string =>
                        class_basename($state)
                    )
                    ->sortable(),

                TextColumn::make('auditable_id')
                    ->label('ID')
                    ->sortable(),

                TextColumn::make('ip_address')
                    ->toggleable()
                    ->searchable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Timestamp'),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->options([
                        'created' => 'Created',
                        'updated' => 'Updated',
                        'deleted' => 'Deleted',
                    ]),

                SelectFilter::make('user')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordAction(ViewAction::class)
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(function (Builder $query) {
                $user = Auth::user();

                if (!$user) {
                    return $query->whereRaw('1 = 0');
                }

                if ($user->canViewAllAuditLogs()) {
                    return $query;
                }

                if ($user->canViewTenantAuditLogs()) {
                    return $query->where('tenant_id', $user->tenant_id);
                }

                return $query->where('user_id', $user->id);
            });
    }
}
