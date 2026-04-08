<?php

namespace App\Modules\Document\Filament\Resources\DocumentResource\Schemas;

use App\Modules\Document\Enums\DocumentVisibility;
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
                    ->components([
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
            ]);
    }
}
