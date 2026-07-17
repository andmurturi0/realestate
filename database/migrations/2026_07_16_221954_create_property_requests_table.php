<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_requests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->enum('request_type', ['buying', 'renting']);
            $table->enum('category', ['house', 'apartment', 'office', 'store', 'land', 'warehouse', 'object']);
            $table->foreignId('location_id')->constrained('locations');
            $table->bigInteger('budget_max');

            // Normalized to m² in the FormRequest (1 ari = 100 m², 1 ha = 10000 m²);
            // surface_unit only remembers what the visitor typed, for display.
            $table->decimal('surface_min_m2', 12, 2)->nullable();
            $table->decimal('surface_max_m2', 12, 2)->nullable();
            $table->enum('surface_unit', ['m2', 'ari', 'ha'])->default('m2');

            $table->text('details')->nullable();
            $table->enum('status', ['new', 'contacted', 'in_progress', 'closed'])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_requests');
    }
};
