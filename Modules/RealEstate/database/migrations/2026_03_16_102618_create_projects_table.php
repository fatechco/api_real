<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->string('slug')->unique();
            $table->json('description');
            $table->string('developer')->nullable();
            $table->foreignId('agency_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('developer_info')->nullable();
            
            // Location
            $table->string('address');
            $table->string('city');
            $table->string('district');
            $table->string('ward')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            
            // Project details
            $table->decimal('total_area', 10, 2)->nullable();
            $table->integer('total_units')->nullable();
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->enum('status', ['planning', 'ongoing', 'completed'])->default('planning');
            
            // Media
            $table->json('images')->nullable();
            $table->json('virtual_tour')->nullable();
            $table->string('brochure_url')->nullable();
            $table->string('video_url')->nullable();
            
            // Flags
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            
            // SEO
            $table->json('meta_title')->nullable();
            $table->json('meta_description')->nullable();
            $table->json('meta_keywords')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['city', 'district', 'status']);
            $table->index('is_featured');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};