<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_offers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone');
            $table->enum('listing_type', ['sale', 'rent']);
            $table->enum('category', ['house', 'apartment', 'office', 'store', 'land', 'warehouse', 'object']);
            $table->foreignId('location_id')->constrained('locations');
            $table->decimal('surface_m2', 10, 2);
            $table->bigInteger('asking_price');
            $table->enum('status', ['new', 'contacted', 'in_progress', 'converted', 'rejected'])->default('new');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('converted_property_id')->nullable()->constrained('properties')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('assigned_to');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_offers');
    }
};
