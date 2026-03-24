<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Countries table
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('code', 2)->unique();
            $table->string('phone_code', 10)->nullable();
            $table->boolean('active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
        
        // Country translations table
        Schema::create('country_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('locale', 10)->index();
            $table->string('name');
            $table->string('native_name')->nullable();
            $table->unique(['country_id', 'locale']);
        });
        
        // Provinces table
        Schema::create('provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('code', 20)->nullable();
            $table->string('type', 20)->nullable(); // city, province, state
            $table->boolean('active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['country_id', 'code']);
        });
        
        // Province translations table
        Schema::create('province_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
            $table->string('locale', 10)->index();
            $table->string('name');
            $table->unique(['province_id', 'locale']);
        });
        
        // Districts table
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('code', 20)->nullable();
            $table->string('type', 20)->nullable(); // district, county, city
            $table->boolean('active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['province_id', 'code']);
        });
        
        // District translations table
        Schema::create('district_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            $table->string('locale', 10)->index();
            $table->string('name');
            $table->unique(['district_id', 'locale']);
        });
        
        // Wards table
        Schema::create('wards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->constrained()->onDelete('cascade');
            $table->foreignId('province_id')->constrained()->onDelete('cascade');
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('code', 20)->nullable();
            $table->string('type', 20)->nullable(); // ward, commune, town
            $table->boolean('active')->default(true);
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index(['district_id', 'code']);
        });
        
        // Ward translations table
        Schema::create('ward_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ward_id')->constrained()->onDelete('cascade');
            $table->string('locale', 10)->index();
            $table->string('name');
            $table->unique(['ward_id', 'locale']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('ward_translations');
        Schema::dropIfExists('wards');
        Schema::dropIfExists('district_translations');
        Schema::dropIfExists('districts');
        Schema::dropIfExists('province_translations');
        Schema::dropIfExists('provinces');
        Schema::dropIfExists('country_translations');
        Schema::dropIfExists('countries');
    }
};