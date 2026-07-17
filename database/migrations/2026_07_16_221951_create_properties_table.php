<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('reference_code')->nullable()->unique();
            $table->foreignId('agent_id')->constrained('users');
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('description');

            $table->enum('listing_type', ['sale', 'rent']);
            $table->boolean('is_exclusive')->default(false);
            $table->enum('category', ['house', 'apartment', 'office', 'store', 'land', 'warehouse', 'object']);

            $table->bigInteger('price');
            $table->boolean('price_negotiable')->default(false);
            $table->decimal('surface_m2', 10, 2);
            $table->decimal('land_surface_m2', 10, 2)->nullable();
            $table->smallInteger('bedrooms')->nullable();
            $table->smallInteger('bathrooms')->nullable();
            $table->smallInteger('floor')->nullable();
            $table->smallInteger('total_floors')->nullable();
            $table->smallInteger('year_built')->nullable();
            $table->smallInteger('parking_spaces')->nullable();

            $table->foreignId('location_id')->constrained('locations');
            $table->string('address_line')->nullable();
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);

            $table->boolean('has_possession_sheet')->nullable();
            $table->enum('document_type', ['notary', 'lawyer'])->nullable();

            $table->enum('status', ['draft', 'published', 'reserved', 'sold', 'rented', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'listing_type', 'category']);
            $table->index('location_id');
            $table->index('price');
            $table->index('surface_m2');
            $table->index('bedrooms');
            $table->index('is_exclusive');
            $table->index('agent_id');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
