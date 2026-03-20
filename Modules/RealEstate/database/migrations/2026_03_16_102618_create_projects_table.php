<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
          // Projects table
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('slug')->unique();
            
            // Relations
            $table->foreignId('agency_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('developer_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Location hierarchy (standard)
            $table->foreignId('country_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('province_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('district_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('ward_id')->nullable()->constrained()->nullOnDelete();
            
            // Detailed address
            $table->string('address')->nullable();
            $table->string('street')->nullable();
            $table->string('street_number')->nullable();
            $table->string('building_name')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Project details
            $table->decimal('total_area', 10, 2)->nullable();
            $table->decimal('built_area', 10, 2)->nullable();
            $table->integer('total_units')->nullable();
            $table->integer('available_units')->nullable();
            $table->integer('total_floors')->nullable();
            $table->integer('basement_floors')->nullable();
            
            // Pricing
            $table->decimal('min_price', 15, 2)->nullable();
            $table->decimal('max_price', 15, 2)->nullable();
            $table->decimal('price_per_m2', 10, 2)->nullable();
            
            // Dates
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->date('handover_date')->nullable();
            
            // Status
            $table->enum('status', [
                'planning', 'ongoing', 'completed', 'sold_out', 'paused', 'cancelled'
            ])->default('planning');
            
            // Flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_hot')->default(false);
            $table->boolean('is_active')->default(true);
            
            // Statistics
            $table->integer('views')->default(0);
            $table->integer('unique_views')->default(0);
            $table->integer('favorites_count')->default(0);
            $table->integer('inquiries_count')->default(0);
            
                  
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['country_id', 'province_id', 'district_id']);
            $table->index('status');
            $table->index('is_featured');
            $table->index('is_hot');
            $table->index('created_at');
        });
        
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};