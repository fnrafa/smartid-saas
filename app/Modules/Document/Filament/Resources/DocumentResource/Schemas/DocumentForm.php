<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Schemas;

use App\Modules\Document\Enums\DocumentVisibility;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Document Information')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        RichEditor::make('content')
                            ->required()
                            ->columnSpanFull(),

                        TextInput::make('category')
                            ->maxLength(100),

                        Select::make('visibility')
                            ->options([
                                DocumentVisibility::PUBLIC->value => 'Public',
                                DocumentVisibility::PRIVATE->value => 'Private',
                            ])
                            ->default(DocumentVisibility::PRIVATE->value)
                            ->required()
                            ->disabled(fn () => auth()->user()->isStaff())
                            ->helperText(fn () => auth()->user()->isStaff()
                                ? 'Staff can only create public documents'
                                : 'Private documents are only visible to you and users you share with'),
                    ])->columns(),
                    
                Section::make('Document Details')
                    ->schema([
                        Placeholder::make('owner_info')
                            ->label('Document Owner')
                            ->content(function ($record) {
                                if (!$record) return '-';
                                return $record->owner->name . ' (' . $record->owner->email . ')';
                            }),
                            
                        Placeholder::make('last_editor_info')
                            ->label('Last Edited By')
                            ->content(function ($record) {
                                if (!$record) return '-';
                                return $record->lastEditor->name . ' at ' . $record->updated_at->format('d M Y H:i');
                            }),
                            
                        Placeholder::make('created_info')
                            ->label('Created')
                            ->content(fn ($record) => $record ? $record->created_at->format('d M Y H:i') : '-'),
                            
                        Placeholder::make('shared_info')
                            ->label('Shared With')
                            ->content(function ($record) {
                                if (!$record) return 'Not shared';
                                
                                $count = $record->sharedAccess()->count();
                                
                                if ($count === 0) {
                                    return 'Not shared';
                                }
                                
                                $users = $record->sharedAccess()
                                    ->with('user')
                                    ->get()
                                    ->pluck('user.name')
                                    ->take(3)
                                    ->implode(', ');
                                    
                                if ($count > 3) {
                                    $users .= ' and ' . ($count - 3) . ' more';
                                }
                                
                                return $users;
                            })
                            ->visible(fn ($record) => $record && $record->visibility === DocumentVisibility::PRIVATE),
                    ])
                    ->columns(2)
                    ->visibleOn(['view', 'edit']),
            ]);
    }
}
