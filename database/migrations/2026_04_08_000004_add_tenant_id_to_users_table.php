<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            $table->enum('role', ['staff', 'manager', 'director', 'head'])->default('staff')->after('tenant_id');
        });

        $hasExistingUsers = DB::table('users')->exists();
        
        if ($hasExistingUsers) {
            $defaultTenant = DB::table('tenants')->first();
            
            if (!$defaultTenant) {
                DB::table('tenants')->insert([
                    'name' => 'Default Tenant',
                    'slug' => 'default-tenant',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $defaultTenant = DB::table('tenants')->first();
            }
            
            DB::table('users')
                ->whereNull('tenant_id')
                ->update([
                    'tenant_id' => $defaultTenant->id,
                    'role' => 'head'
                ]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('tenant_id')->nullable(false)->change();
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            
            $table->index('tenant_id');
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropIndex(['role']);
            $table->dropColumn(['tenant_id', 'role']);
        });
    }
};
