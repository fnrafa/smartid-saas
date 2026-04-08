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
use Filament\Tables\Filters\Filter;
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
                    ->limit(50)
                    ->description(fn ($record) => $record->category ? "Category: {$record->category}" : null),

                BadgeColumn::make('visibility')
                    ->label('Visibility')
                    ->formatStateUsing(fn ($state) => $state->label())
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
                    
                TextColumn::make('shared_count')
                    ->label('Shared')
                    ->getStateUsing(fn ($record) => $record->sharedAccess->count())
                    ->badge()
                    ->color('gray')
                    ->visible(fn () => true)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Last Modified')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('visibility')
                    ->options([
                        DocumentVisibility::PUBLIC->value => DocumentVisibility::PUBLIC->label(),
                        DocumentVisibility::PRIVATE->value => DocumentVisibility::PRIVATE->label(),
                    ])
                    ->label('Visibility'),

                SelectFilter::make('owner')
                    ->relationship('owner', 'name')
                    ->searchable()
                    ->preload()
                    ->label('Owner'),
                    
                Filter::make('owned_by_me')
                    ->query(fn (Builder $query) => $query->where('owner_id', auth()->id()))
                    ->label('My Documents'),
                    
                Filter::make('shared_with_me')
                    ->query(fn (Builder $query) => 
                        $query->whereHas('sharedAccess', fn ($q) => 
                            $q->where('user_id', auth()->id())
                        )
                    )
                    ->label('Shared With Me'),
            ])
            ->recordAction(ViewAction::class)
            ->recordActions([
                ViewAction::make(),
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
                            ->orWhereHas('sharedAccess', fn($subQ) => $subQ->where('user_id', $user->id));
                    })
                    ->with(['owner', 'lastEditor', 'sharedAccess']);
            })
            ->defaultSort('updated_at', 'desc');
    }
}
