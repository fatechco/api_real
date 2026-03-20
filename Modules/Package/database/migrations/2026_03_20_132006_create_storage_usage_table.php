<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // User storage usage tracking
        Schema::create('user_storage_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_package_id')->constrained('user_packages')->onDelete('cascade');
            
            // Usage tracking
            $table->bigInteger('total_used_bytes')->default(0);
            $table->bigInteger('images_bytes')->default(0);
            $table->bigInteger('videos_bytes')->default(0);
            $table->bigInteger('documents_bytes')->default(0);
            $table->bigInteger('other_bytes')->default(0);
            
            // File counts
            $table->integer('total_files_count')->default(0);
            $table->integer('images_count')->default(0);
            $table->integer('videos_count')->default(0);
            $table->integer('documents_count')->default(0);
            
            // Per listing tracking (để kiểm tra storagePerListing)
            $table->json('listing_storage_usage')->nullable(); // {property_id: bytes_used}
            
            // Monthly reset tracking
            $table->timestamp('last_reset_at');
            $table->timestamp('last_calculated_at');
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('user_package_id');
        });
        
        // File upload logs
        Schema::create('file_upload_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->morphs('fileable'); // property, project, user_avatar
            $table->string('file_type'); // image, video, document
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('original_name');
            $table->string('storage_path');
            $table->boolean('is_optimized')->default(false);
            $table->string('optimization_status')->default('pending'); // pending, processing, completed, failed
            $table->json('optimization_metadata')->nullable(); // {original_size, optimized_size, ratio}
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
        });
        
        // Storage overage charges (if users exceed limit)
        Schema::create('storage_overage_charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_package_id')->constrained('user_packages')->onDelete('cascade');
            $table->bigInteger('excess_bytes');
            $table->decimal('charge_amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            $table->timestamp('billing_cycle_start');
            $table->timestamp('billing_cycle_end');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('storage_overage_charges');
        Schema::dropIfExists('file_upload_logs');
        Schema::dropIfExists('user_storage_usage');
    }
};