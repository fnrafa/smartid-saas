<?php

namespace App\Modules\Tenant\Enums;

enum UserRole: string
{
    case STAFF = 'staff';
    case MANAGER = 'manager';
    case DIRECTOR = 'director';
    case HEAD = 'head';

    public function label(): string
    {
        return match($this) {
            self::STAFF => 'Staff',
            self::MANAGER => 'Manager',
            self::DIRECTOR => 'Director',
            self::HEAD => 'Head',
        };
    }

    public function canCreatePrivateDocuments(): bool
    {
        return match($this) {
            self::STAFF => false,
            self::MANAGER, self::DIRECTOR, self::HEAD => true,
        };
    }

    public function canApproveDocuments(): bool
    {
        return match($this) {
            self::DIRECTOR, self::HEAD => true,
            default => false,
        };
    }

    public function isSuperAdmin(): bool
    {
        return $this === self::HEAD;
    }

    public function level(): int
    {
        return match($this) {
            self::STAFF => 1,
            self::MANAGER => 2,
            self::DIRECTOR => 3,
            self::HEAD => 4,
        };
    }

    public function canManageUser(UserRole $targetRole): bool
    {
        return $this->level() > $targetRole->level();
    }

    public static function getOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
