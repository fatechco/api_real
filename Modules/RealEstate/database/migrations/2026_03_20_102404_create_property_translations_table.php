<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->string('locale', 10)->index();
            
            // Translated fields
            $table->string('title');
            $table->text('description');
            $table->timestamps();
            
            $table->unique(['property_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_translations');
    }
};