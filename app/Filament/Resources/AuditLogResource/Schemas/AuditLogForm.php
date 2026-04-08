<?php

namespace App\Filament\Resources\AuditLogResource\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AuditLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Audit Information')
                    ->components([
                        TextInput::make('event')
                            ->disabled(),

                        TextInput::make('user.name')
                            ->label('User')
                            ->disabled(),

                        TextInput::make('auditable_type')
                            ->label('Model')
                            ->disabled(),

                        KeyValue::make('old_values')
                            ->label('Old Values')
                            ->disabled(),

                        KeyValue::make('new_values')
                            ->label('New Values')
                            ->disabled(),

                        TextInput::make('ip_address')
                            ->disabled(),

                        Textarea::make('user_agent')
                            ->disabled()
                            ->rows(2),
                    ])->columns(),
            ]);
    }
}
