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
            $table->uuid('uuid')->unique();
            
            // User relations
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            
            // Category relations
            $table->foreignId('category_id')->nullable()->constrained('property_categories')->nullOnDelete();
            $table->foreignId('type_id')->nullable()->constrained('property_types')->nullOnDelete();
            
            // Basic info (translatable)
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('description');
            $table->json('content')->nullable();
            
            // Pricing
            $table->decimal('price', 15, 2);
            $table->decimal('price_per_m2', 10, 2)->nullable();
            $table->boolean('is_negotiable')->default(false);
            
            // Area
            $table->decimal('area', 10, 2);
            $table->decimal('land_area', 10, 2)->nullable();
            $table->decimal('built_area', 10, 2)->nullable();
            
            // Details
            $table->integer('bedrooms')->nullable();
            $table->integer('bathrooms')->nullable();
            $table->integer('floors')->nullable();
            $table->integer('garages')->nullable();
            $table->integer('year_built')->nullable();
            
            $table->string('furnishing')->nullable();
            $table->string('legal_status')->nullable();
            $table->string('ownership_type')->nullable();
            
            // Location
            $table->string('address');
            $table->string('city');
            $table->string('district');
            $table->string('ward')->nullable();
            $table->string('street')->nullable();
            $table->string('project_name')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('map_url')->nullable();
            
            // Status
            $table->enum('status', [
                'pending', 'available', 'sold', 'rented', 
                'expired', 'hidden', 'rejected'
            ])->default('pending');
            
            $table->string('transaction_type')->default('sell');
            
            // VIP and Featured
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_vip')->default(false);
            $table->timestamp('vip_expires_at')->nullable();
            $table->boolean('is_urgent')->default(false);
            $table->boolean('is_top')->default(false);
            $table->timestamp('top_expires_at')->nullable();
            
            // Stats
            $table->integer('views')->default(0);
            $table->integer('unique_views')->default(0);
            $table->integer('contact_views')->default(0);
            $table->integer('favorites_count')->default(0);
            
            // SEO (translatable)
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            
            // Timestamps
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['user_id', 'status']);
            $table->index(['city', 'district', 'ward']);
            $table->index(['price', 'area']);
            $table->index(['transaction_type', 'status']);
            $table->index('is_featured');
            $table->index('is_vip');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};