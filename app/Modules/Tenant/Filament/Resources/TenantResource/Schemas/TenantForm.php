<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Filament\Resources\TenantResource\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class TenantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tenant Information')
                    ->components([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('slug', Str::slug($state))
                            ),

                        TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->disabled()
                            ->dehydrated(),
                    ])->columns(),

                Section::make('Subscription')
                    ->components([
                        Select::make('activeSubscription.subscription_tier_id')
                            ->relationship('activeSubscription.tier', 'name')
                            ->label('Subscription Tier')
                            ->required(),
                    ])
                    ->visibleOn('edit'),
            ]);
    }
}
