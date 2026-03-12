<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name', 300);
            $table->string('description', 400)->nullable();
            $table->longText('content')->nullable();
            $table->string('images')->nullable();
            $table->string('location')->nullable();
            $table->string('latitude', 25)->nullable();
            $table->string('longitude', 25)->nullable();
            $table->foreignId('investor_id');
            $table->integer('number_block')->nullable();
            $table->smallInteger('number_floor')->nullable();
            $table->smallInteger('number_flat')->nullable();
            $table->boolean('is_featured')->default(0);
            $table->date('date_finish')->nullable();
            $table->date('date_sell')->nullable();
            $table->decimal('price_from', 15, 0)->nullable();
            $table->decimal('price_to', 15, 0)->nullable();
            $table->foreignId('currency_id')->nullable();
            $table->foreignId('city_id')->nullable();
            $table->foreignId('category_id')->nullable();
            $table->string('status', 60)->default('selling');
            $table->foreignId('author_id')->nullable();
            $table->string('author_type')->default(addslashes(User::class));
            $table->integer('views')->unsigned()->default(0);
            $table->timestamps();
        });

        if (! Schema::hasTable('projects_translations')) {
            Schema::create('projects_translations', function (Blueprint $table): void {
                $table->string('locale');
                $table->foreignId('projects_id');
                $table->string('name')->nullable();
                $table->string('description', 400)->nullable();
                $table->longText('content')->nullable();
                $table->primary(['locale', 'projects_id'], 'projects_translations_primary');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
        Schema::dropIfExists('projects_translations');
    }
};
