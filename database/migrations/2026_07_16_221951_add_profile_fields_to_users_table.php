<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['admin', 'agent'])->default('agent')->after('password');
            $table->string('phone')->nullable()->after('role');
            $table->string('whatsapp')->nullable()->after('phone');
            $table->string('avatar_path')->nullable()->after('whatsapp');
            $table->json('bio')->nullable()->after('avatar_path');
            $table->boolean('is_active')->default(true)->after('bio');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'whatsapp', 'avatar_path', 'bio', 'is_active']);
        });
    }
};
