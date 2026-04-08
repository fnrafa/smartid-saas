<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Enums\DocumentVisibility;
use App\Modules\Tenant\Enums\UserRole;
use App\Modules\Tenant\Models\Subscription;
use App\Modules\Tenant\Models\SubscriptionTier;
use App\Modules\Tenant\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $basicTier = SubscriptionTier::where('name', 'basic')->first();
        $premiumTier = SubscriptionTier::where('name', 'premium')->first();

        $tenant1 = Tenant::create([
            'name' => 'Pemkot Malang',
            'slug' => 'pemkot-malang',
        ]);

        Subscription::create([
            'tenant_id' => $tenant1->id,
            'subscription_tier_id' => $basicTier->id,
            'is_active' => true,
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $walikota = User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Walikota Malang',
            'email' => 'walikota@malang.go.id',
            'password' => Hash::make('password'),
            'role' => UserRole::HEAD,
        ]);

        $sekda1 = User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Sekda Malang',
            'email' => 'sekda@malang.go.id',
            'password' => Hash::make('password'),
            'role' => UserRole::DIRECTOR,
        ]);

        $dinas = User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Kepala Dinas',
            'email' => 'dinas@malang.go.id',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
        ]);

        $staff1 = User::create([
            'tenant_id' => $tenant1->id,
            'name' => 'Staff Pemkot',
            'email' => 'staff@malang.go.id',
            'password' => Hash::make('password'),
            'role' => UserRole::STAFF,
        ]);

        $doc1 = Document::create([
            'tenant_id' => $tenant1->id,
            'owner_id' => $walikota->id,
            'user_id' => $walikota->id,
            'title' => 'Anggaran 2026',
            'content' => 'Total anggaran Pemkot Malang tahun 2026 sebesar Rp 150M. Dialokasikan untuk pembangunan infrastruktur, pendidikan, dan kesehatan.',
            'category' => 'Keuangan',
            'visibility' => DocumentVisibility::PRIVATE,
        ]);

        sleep(1);
        $doc1->update([
            'user_id' => $sekda1->id,
            'content' => 'Total anggaran Pemkot Malang tahun 2026 sebesar Rp 180M. Dialokasikan untuk pembangunan infrastruktur, pendidikan, dan kesehatan.',
        ]);

        sleep(1);
        $doc1->update([
            'user_id' => $walikota->id,
            'content' => 'Total anggaran Pemkot Malang tahun 2026 sebesar Rp 200M. Dialokasikan untuk pembangunan infrastruktur, pendidikan, dan kesehatan.',
        ]);

        Document::create([
            'tenant_id' => $tenant1->id,
            'owner_id' => $dinas->id,
            'user_id' => $dinas->id,
            'title' => 'Proposal Proyek Infrastruktur',
            'content' => 'Proposal pembangunan jalan raya Ring Road Malang dengan estimasi biaya Rp 50M.',
            'category' => 'Infrastruktur',
            'visibility' => DocumentVisibility::PRIVATE,
        ]);

        Document::create([
            'tenant_id' => $tenant1->id,
            'owner_id' => $staff1->id,
            'user_id' => $staff1->id,
            'title' => 'Draft Memo Rapat',
            'content' => 'Memo rapat koordinasi bulanan untuk seluruh kepala dinas. Agenda: evaluasi kinerja Q1 2026.',
            'category' => 'Administrasi',
            'visibility' => DocumentVisibility::PUBLIC,
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Pemprov Jatim',
            'slug' => 'pemprov-jatim',
        ]);

        Subscription::create([
            'tenant_id' => $tenant2->id,
            'subscription_tier_id' => $premiumTier->id,
            'is_active' => true,
            'start_date' => now(),
            'end_date' => null,
        ]);

        $gubernur = User::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Gubernur Jawa Timur',
            'email' => 'gubernur@jatim.go.id',
            'password' => Hash::make('password'),
            'role' => UserRole::HEAD,
        ]);

        $wagub = User::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Wakil Gubernur Jatim',
            'email' => 'wagub@jatim.go.id',
            'password' => Hash::make('password'),
            'role' => UserRole::DIRECTOR,
        ]);

        User::create([
            'tenant_id' => $tenant2->id,
            'name' => 'Kepala Bagian Perencanaan',
            'email' => 'kabag@jatim.go.id',
            'password' => Hash::make('password'),
            'role' => UserRole::MANAGER,
        ]);

        $categories = ['Strategis', 'Keuangan', 'Infrastruktur', 'Kesehatan', 'Pendidikan', 'Sosial', 'Ekonomi', 'Hukum'];
        $titles = [
            'RPJMD Jatim 2024-2029',
            'Perda APBD 2026',
            'Master Plan Infrastruktur',
            'Program Kesehatan Masyarakat',
            'Roadmap Pendidikan Digital',
            'Kebijakan Pemberdayaan UMKM',
            'Strategi Investasi Daerah',
            'Rancangan Peraturan Gubernur',
            'Laporan Kinerja Triwulan I',
            'Evaluasi Program Bantuan Sosial',
        ];

        foreach ($titles as $index => $title) {
            Document::create([
                'tenant_id' => $tenant2->id,
                'owner_id' => $index % 2 === 0 ? $gubernur->id : $wagub->id,
                'user_id' => $index % 2 === 0 ? $gubernur->id : $wagub->id,
                'title' => $title,
                'content' => "Dokumen $title untuk Provinsi Jawa Timur tahun 2026. Berisi detail program dan kebijakan strategis.",
                'category' => $categories[$index % count($categories)],
                'visibility' => $index % 3 === 0 ? DocumentVisibility::PUBLIC : DocumentVisibility::PRIVATE,
            ]);
        }

        $tenant3 = Tenant::create([
            'name' => 'Pemkab Sidoarjo',
            'slug' => 'pemkab-sidoarjo',
        ]);

        Subscription::create([
            'tenant_id' => $tenant3->id,
            'subscription_tier_id' => $basicTier->id,
            'is_active' => true,
            'start_date' => now(),
            'end_date' => now()->addYear(),
        ]);

        $bupati = User::create([
            'tenant_id' => $tenant3->id,
            'name' => 'Bupati Sidoarjo',
            'email' => 'bupati@sidoarjo.go.id',
            'password' => Hash::make('password'),
            'role' => UserRole::HEAD,
        ]);

        $sekda3 = User::create([
            'tenant_id' => $tenant3->id,
            'name' => 'Sekda Sidoarjo',
            'email' => 'sekda@sidoarjo.go.id',
            'password' => Hash::make('password'),
            'role' => UserRole::DIRECTOR,
        ]);

        $sidoarjoTitles = [
            'Rencana Strategis 2026',
            'APBD Perubahan 2026',
            'Program Pembangunan Desa',
            'Kebijakan Penanggulangan Banjir',
            'Evaluasi Kinerja ASN',
        ];

        foreach ($sidoarjoTitles as $index => $title) {
            Document::create([
                'tenant_id' => $tenant3->id,
                'owner_id' => $index % 2 === 0 ? $bupati->id : $sekda3->id,
                'user_id' => $index % 2 === 0 ? $bupati->id : $sekda3->id,
                'title' => $title,
                'content' => "Dokumen $title Kabupaten Sidoarjo. Detail program dan implementasi strategis.",
                'category' => $index % 2 === 0 ? 'Perencanaan' : 'Keuangan',
                'visibility' => DocumentVisibility::PRIVATE,
            ]);
        }
    }
}
