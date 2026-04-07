<?php

namespace App\Modules\Document\Enums;

enum DocumentVisibility: string
{
    case PRIVATE = 'private';
    case PUBLIC = 'public';

    public function label(): string
    {
        return match($this) {
            self::PRIVATE => 'Private',
            self::PUBLIC => 'Public',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::PRIVATE => 'Only visible to owner and shared users',
            self::PUBLIC => 'Visible to all users in tenant',
        };
    }

    public function icon(): string
    {
        return match($this) {
            self::PRIVATE => 'heroicon-o-lock-closed',
            self::PUBLIC => 'heroicon-o-globe-alt',
        };
    }

    public static function getOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
            'description' => $case->description(),
        ], self::cases());
    }
}
