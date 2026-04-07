<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('content')->nullable();
            $table->string('category', 100)->nullable();
            $table->enum('visibility', ['private', 'public'])->default('private');
            $table->softDeletes();
            $table->timestamps();
            
            $table->index('tenant_id');
            $table->index(['tenant_id', 'user_id']);
            $table->index('owner_id');
            $table->index('visibility');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
