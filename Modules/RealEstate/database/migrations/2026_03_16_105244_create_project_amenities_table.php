<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_amenities', function (Blueprint $table) {
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('amenity_id')->constrained()->onDelete('cascade');
            $table->string('value')->nullable();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->boolean('is_highlight')->default(false);
            $table->integer('order')->default(0);
            $table->primary(['project_id', 'amenity_id']);
            $table->index(['project_id', 'is_highlight']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_amenities');
    }
};