<?php

namespace App\Modules\Document\Enums;

enum AccessLevel: string
{
    case READ = 'read';
    case EDIT = 'edit';
    case FULL = 'full';

    public function label(): string
    {
        return match($this) {
            self::READ => 'Read Only',
            self::EDIT => 'Can Edit',
            self::FULL => 'Full Control',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::READ => 'Can view document only',
            self::EDIT => 'Can view and modify content',
            self::FULL => 'Can view, edit, and delete',
        };
    }

    public function permissions(): array
    {
        return match($this) {
            self::READ => ['view'],
            self::EDIT => ['view', 'update'],
            self::FULL => ['view', 'update', 'delete', 'share'],
        };
    }

    public function canRead(): bool
    {
        return true;
    }

    public function canEdit(): bool
    {
        return in_array($this, [self::EDIT, self::FULL]);
    }

    public function canDelete(): bool
    {
        return $this === self::FULL;
    }

    public function canShare(): bool
    {
        return $this === self::FULL;
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
