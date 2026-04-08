<?php

namespace App\Modules\Tenant\Enums;

enum UserRole: string
{
    case SUPERADMIN = 'superadmin';
    case STAFF = 'staff';
    case MANAGER = 'manager';
    case DIRECTOR = 'director';
    case HEAD = 'head';

    public function label(): string
    {
        return match($this) {
            self::SUPERADMIN => 'Super Admin',
            self::STAFF => 'Staff',
            self::MANAGER => 'Manager',
            self::DIRECTOR => 'Director',
            self::HEAD => 'Head (Tenant Admin)',
        };
    }

    public function canCreatePrivateDocuments(): bool
    {
        return match($this) {
            self::STAFF, self::SUPERADMIN => false,
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
        return $this === self::SUPERADMIN;
    }

    public function isHead(): bool
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
            self::SUPERADMIN => 5,
        };
    }

    public function canManageUser(UserRole $targetRole): bool
    {
        if ($this === self::SUPERADMIN) {
            return true;
        }

        if ($this === self::HEAD) {
            return in_array($targetRole, [self::STAFF, self::MANAGER, self::DIRECTOR]);
        }

        return false;
    }

    public function canManageTenants(): bool
    {
        return $this === self::SUPERADMIN;
    }

    public function canManageOwnTenant(): bool
    {
        return in_array($this, [self::SUPERADMIN, self::HEAD]);
    }

    public function canViewAllAuditLogs(): bool
    {
        return $this === self::SUPERADMIN;
    }

    public function canViewTenantAuditLogs(): bool
    {
        return in_array($this, [self::SUPERADMIN, self::HEAD]);
    }

    public function canTransferUserBetweenTenants(): bool
    {
        return $this === self::SUPERADMIN;
    }

    public static function getOptions(): array
    {
        return array_map(fn($case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
