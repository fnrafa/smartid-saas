# Revisi RBAC - Tenant SmartID System

## Konsep Arsitektur Baru

### 1. **Tenant SmartID System** (is_system = true)
- Tenant khusus untuk admin aplikasi
- **TIDAK DAPAT DIHAPUS** (protected by policy)
- Semua SuperAdmin berada di tenant ini
- Premium subscription (unlimited)

### 2. **Tenant Client** (is_system = false)
- Pemkot Malang, Pemprov Jatim, dll
- Dapat dibuat/dihapus oleh SuperAdmin
- Head hanya bisa manage user sampai Director/Manager/Staff

---

## Hierarki Role & Permission

### SuperAdmin (Tenant SmartID)
✅ CRUD semua tenant
✅ CRUD semua user di semua tenant
✅ Assign role SuperAdmin & Head
✅ Transfer user antar tenant
✅ Manage subscription
✅ Lihat semua audit logs
✅ Delete tenant (kecuali SmartID System)

### Head (Tenant Client)
✅ Lihat & edit nama tenant sendiri
❌ TIDAK bisa edit subscription
✅ CRUD user di tenant sendiri
✅ Assign role: Staff, Manager, Director SAJA
❌ TIDAK bisa assign role Head atau SuperAdmin
❌ TIDAK bisa transfer user ke tenant lain
✅ Lihat audit logs tenant sendiri

### Director/Manager/Staff
❌ Tidak ada akses Tenant Management
❌ Tidak ada akses User Management
✅ Akses Documents sesuai role
✅ Lihat audit logs aktivitas sendiri

---

## File Yang Diubah

### 1. Migration
- `2026_04_08_110000_add_is_system_to_tenants.php` - Tambah field is_system

### 2. Models
- `Tenant.php` - Tambah is_system, isSystemTenant(), scopes
- `User.php` - Sudah OK (tidak perlu diubah)

### 3. Enums
- `UserRole.php` - Sudah OK (canManageUser logic benar)

### 4. Policies
- `TenantPolicy.php` - Protect system tenant dari delete
- `UserPolicy.php` - Sudah OK

### 5. Resources
- `UserResource/Schemas/UserForm.php` - Batasi role dropdown untuk Head
- `TenantResource/Tables/TenantsTable.php` - Badge system tenant

### 6. Seeders
- `SuperAdminSeeder.php` - Create SmartID System tenant + SuperAdmin
- `DatabaseSeeder.php` - Update order (SuperAdmin sebelum DemoData)

---

## Cara Testing

### 1. Fresh Migration
```bash
php artisan migrate:fresh --seed
```

### 2. Login SuperAdmin
- Email: `superadmin@smartid.co.id`
- Password: `superadmin123`
- Tenant: SmartID System

### 3. Cek Tenant Management
✅ Lihat semua tenant (SmartID System + clients)
✅ SmartID System ada badge "🔒 System Tenant"
✅ Coba delete SmartID System → HARUS ERROR
✅ Bisa delete tenant client

### 4. Cek User Management
✅ Lihat semua user dari semua tenant
✅ Create user baru di tenant manapun
✅ Dropdown role ada semua (termasuk SuperAdmin & Head)
✅ Bisa transfer user antar tenant (via edit tenant_id field)

### 5. Login Head (Client Tenant)
- Email: `walikota@malang.go.id`
- Password: `password`
- Tenant: Pemkot Malang

### 6. Cek User Management
✅ Hanya lihat user di Pemkot Malang
✅ Dropdown role HANYA: Staff, Manager, Director
❌ TIDAK ada SuperAdmin & Head di dropdown
✅ Tenant_id field disabled (tidak bisa transfer)

---

## Data Setelah Migration

### Tenant 1: SmartID System (is_system = true)
- SuperAdmin: superadmin@smartid.co.id

### Tenant 2: Pemkot Malang (is_system = false)
- Head: walikota@malang.go.id
- Director: sekda@malang.go.id
- Manager: dinas@malang.go.id
- Staff: staff@malang.go.id

### Tenant 3: Pemprov Jatim (is_system = false)
- Head: gubernur@jatim.go.id
- Director: wagub@jatim.go.id
- Manager: kabag@jatim.go.id

### Tenant 4: Pemkab Sidoarjo (is_system = false)
- Head: bupati@sidoarjo.go.id
- Director: sekda@sidoarjo.go.id

---

## Summary Perubahan

✅ Tenant SmartID System terpisah & protected
✅ SuperAdmin di tenant khusus, bukan di tenant client
✅ Head tidak bisa create SuperAdmin/Head baru
✅ Head tidak bisa transfer user ke tenant lain
✅ System tenant tidak bisa dihapus
✅ Role dropdown dynamic berdasarkan user yang login
✅ Clean separation: System Admin vs Tenant Admin

---

**Status**: ✅ SIAP UNTUK TESTING
