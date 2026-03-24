<?php
// database/migrations/2024_01_01_000005_create_properties_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            
            // User relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('property_categories')->nullOnDelete();
            
            // Slug
            $table->string('slug')->unique();
            
            // Default values (fallback when translation missing)
            $table->string('default_title')->nullable();
            $table->text('default_description')->nullable();
            
            // Pricing
            $table->decimal('price', 15, 2);
            $table->decimal('price_per_m2', 10, 2)->nullable();
            $table->boolean('is_negotiable')->default(false);
            
            // Area
            $table->decimal('area', 10, 2);
            $table->decimal('land_area', 10, 2)->nullable();
            $table->decimal('built_area', 10, 2)->nullable();
            
            // Property details
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->integer('floors')->nullable();
            $table->integer('garages')->nullable();
            $table->integer('year_built')->nullable();
            $table->string('furnishing')->nullable();
            $table->string('legal_status')->nullable();
            $table->string('ownership_type')->nullable();
            
            // Location - using hierarchy IDs
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            
            // Detailed address (street name, building number)
            $table->string('street')->nullable();
            $table->string('street_number')->nullable();
            $table->string('building_name')->nullable();
            $table->string('full_address')->nullable();
            
            // Coordinates
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
    
            
            // Project info
            $table->string('project_name')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'available', 'sold', 'rented', 
                'expired', 'hidden', 'rejected'
            ])->default('pending');
            
            // Transaction type (sale/rent)
            $table->enum('type', ['sale', 'rent'])->default('sale');
            
            // Premium features
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_vip')->default(false);
            $table->timestamp('vip_expires_at')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_top')->default(false);
            $table->timestamp('top_expires_at')->nullable();
            
            // Statistics
            $table->integer('views')->default(0);
            $table->integer('unique_views')->default(0);
            $table->integer('contact_views')->default(0);
            $table->integer('favorites_count')->default(0);
            
            // Timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['country_id', 'province_id', 'district_id']);
            $table->index(['price', 'area']);
            $table->index(['type', 'status']);
            $table->index('is_featured');
            $table->index('is_vip');
            $table->index('published_at');
            $table->index('status');
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};