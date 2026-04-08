# Bug Fixes - User Management & Audit Logs

## Bug Yang Diperbaiki:

### ✅ Bug 1: SuperAdmin Bisa Pilih Role SuperAdmin di Tenant Client
**Masalah**: SuperAdmin bisa assign role SuperAdmin ke user di tenant client (bukan SmartID System)

**Solusi**: 
- Tambah logic reactive di dropdown role
- Cek `tenant_id` yang dipilih
- Jika tenant **bukan** SmartID System → filter role SuperAdmin
- Hanya tenant SmartID System yang boleh punya SuperAdmin

**File**: `app/Filament/Resources/UserResource/Schemas/UserForm.php`

**Logic**:
```
IF SuperAdmin editing user:
  IF tenant == SmartID System:
    ✅ Show all roles (termasuk SuperAdmin)
  ELSE (tenant == client):
    ❌ Hide SuperAdmin role
    ✅ Show: Staff, Manager, Director, Head
```

---

### ✅ Bug 2: Audit Logs Tidak Tercatat Saat User/Tenant Berubah
**Masalah**: Tidak ada log saat create/update/delete user atau tenant

**Solusi**: 
- Buat `UserObserver.php` untuk track perubahan user
- Buat `TenantObserver.php` untuk track perubahan tenant
- Register observer di `AppServiceProvider`
- Auto log ke tabel `audit_logs` saat:
  - User created/updated/deleted
  - Tenant created/updated/deleted

**File Baru**:
- `app/Observers/UserObserver.php`
- `app/Observers/TenantObserver.php`

**Update**: `app/Providers/AppServiceProvider.php`

**Data Yang Dicatat**:
- Event: created/updated/deleted
- Old values & new values (JSON)
- User yang melakukan aksi
- IP address & user agent
- Password di-filter jadi `[REDACTED]`

---

### ✅ Bug 3: Head Tidak Bisa Create User (Tenant Field Kosong)
**Masalah**: Saat Head create user, field `tenant_id` disabled tapi tidak ada default value

**Solusi**:
- Tambah `->default()` di field tenant_id
- Auto-fill dengan tenant_id user yang login (jika bukan SuperAdmin)
- Tambah `->dehydrated()` agar tetap tersimpan meski disabled
- Update helper text lebih jelas

**File**: `app/Filament/Resources/UserResource/Schemas/UserForm.php`

**Behavior**:
- **SuperAdmin**: Field enabled, bisa pilih tenant manapun
- **Head**: Field disabled, auto-fill tenant sendiri, tetap tersimpan

---

## Perubahan Detail:

### 1. UserForm.php - Dynamic Role Dropdown
```php
->options(function (Get $get) use ($user) {
    $selectedTenantId = $get('tenant_id');
    
    if (SuperAdmin) {
        $tenant = Tenant::find($selectedTenantId);
        
        if ($tenant->isSystemTenant()) {
            return ALL_ROLES; // termasuk SuperAdmin
        }
        
        return ALL_EXCEPT_SUPERADMIN; // filter SuperAdmin
    }
    
    if (Head) {
        return [STAFF, MANAGER, DIRECTOR]; // fixed
    }
})
->live() // reactive saat tenant berubah
```

### 2. UserForm.php - Auto-fill Tenant untuk Head
```php
->default(fn () => $isSuperAdmin ? null : $user?->tenant_id)
->disabled(!$isSuperAdmin)
->dehydrated() // PENTING: tetap simpan meski disabled
```

### 3. UserObserver.php - Auto Logging
```php
public function created(User $user): void
{
    AuditLog::create([
        'tenant_id' => $user->tenant_id,
        'user_id' => Auth::id(),
        'event' => 'created',
        'auditable_type' => User::class,
        'auditable_id' => $user->id,
        'old_values' => null,
        'new_values' => json_encode($user->getAttributes()),
        'ip_address' => request()->ip(),
    ]);
}
```

---

## Testing Checklist:

### Test 1: Role Dropdown Dynamic
- [ ] Login SuperAdmin
- [ ] Buka Create User
- [ ] Pilih tenant SmartID System → Role SuperAdmin **MUNCUL**
- [ ] Pilih tenant Pemkot Malang → Role SuperAdmin **HILANG**
- [ ] Helper text berubah sesuai tenant yang dipilih

### Test 2: Head Create User
- [ ] Login sebagai Head (walikota@malang.go.id)
- [ ] Buka Create User
- [ ] Field Tenant sudah terisi otomatis (Pemkot Malang)
- [ ] Field Tenant disabled (tidak bisa diubah)
- [ ] Bisa create user berhasil
- [ ] User tersimpan dengan tenant_id yang benar

### Test 3: Audit Logs User
- [ ] Login SuperAdmin
- [ ] Create user baru → Cek audit logs ada log "created"
- [ ] Edit user → Cek audit logs ada log "updated" dengan old/new values
- [ ] Delete user → Cek audit logs ada log "deleted"

### Test 4: Audit Logs Tenant
- [ ] Login SuperAdmin
- [ ] Create tenant baru → Cek audit logs ada log "created"
- [ ] Edit tenant (ubah nama) → Cek audit logs ada log "updated"
- [ ] Delete tenant → Cek audit logs ada log "deleted"

### Test 5: Filter Audit Logs
- [ ] Login SuperAdmin → Lihat semua logs
- [ ] Login Head → Lihat logs tenant sendiri saja
- [ ] Login Staff → Lihat logs aktivitas sendiri saja

---

## File Yang Diubah:

1. ✅ `app/Filament/Resources/UserResource/Schemas/UserForm.php`
   - Tambah reactive role dropdown
   - Auto-fill tenant untuk Head
   - Helper text dynamic

2. ✅ `app/Observers/UserObserver.php` (BARU)
   - Observer untuk User model
   - Auto log created/updated/deleted

3. ✅ `app/Observers/TenantObserver.php` (BARU)
   - Observer untuk Tenant model
   - Auto log created/updated/deleted

4. ✅ `app/Providers/AppServiceProvider.php`
   - Register UserObserver
   - Register TenantObserver

---

**Status**: ✅ SIAP TESTING

**Command**:
```bash
php artisan config:clear
php artisan cache:clear
php artisan serve
```

Tidak perlu migrate ulang, cukup clear cache!
