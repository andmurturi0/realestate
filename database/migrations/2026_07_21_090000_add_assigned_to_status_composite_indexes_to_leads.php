<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Faza 10 §12: the agent-scoped inbox ("my open items") filters by
     * assigned_to AND status together on every one of these tables; the
     * existing single-column indexes don't cover that combined lookup.
     */
    public function up(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->index(['assigned_to', 'status']);
        });

        Schema::table('property_offers', function (Blueprint $table) {
            $table->index(['assigned_to', 'status']);
        });

        Schema::table('property_requests', function (Blueprint $table) {
            $table->index(['assigned_to', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropIndex(['assigned_to', 'status']);
        });

        Schema::table('property_offers', function (Blueprint $table) {
            $table->dropIndex(['assigned_to', 'status']);
        });

        Schema::table('property_requests', function (Blueprint $table) {
            $table->dropIndex(['assigned_to', 'status']);
        });
    }
};
