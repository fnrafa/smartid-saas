<?php

declare(strict_types=1);

namespace App\Modules\Document\Filament\Resources\DocumentResource\Tables;

use App\Modules\Document\Enums\DocumentVisibility;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('category')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),

                BadgeColumn::make('visibility')
                    ->colors([
                        'success' => DocumentVisibility::PUBLIC->value,
                        'warning' => DocumentVisibility::PRIVATE->value,
                    ])
                    ->sortable(),

                TextColumn::make('owner.name')
                    ->label('Owner')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('lastEditor.name')
                    ->label('Last Editor')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('visibility')
                    ->options([
                        DocumentVisibility::PUBLIC->value => 'Public',
                        DocumentVisibility::PRIVATE->value => 'Private',
                    ]),

                SelectFilter::make('owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordAction(ViewAction::class)
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->modifyQueryUsing(function (Builder $query) {
                $user = auth()->user();
                
                return $query->where('tenant_id', $user->tenant_id)
                    ->where(function ($q) use ($user) {
                        $q->where('owner_id', $user->id)
                            ->orWhere('visibility', DocumentVisibility::PUBLIC)
                            ->orWhereHas('sharedAccess', fn($q) => $q->where('user_id', $user->id));
                    });
            });
    }
}
