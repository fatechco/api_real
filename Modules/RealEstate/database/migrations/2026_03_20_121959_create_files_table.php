<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Main files table (centralized file management)
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_package_id')->nullable()->constrained('user_packages')->nullOnDelete();
            
            // Polymorphic relation
            $table->morphs('fileable'); // property, project, user_avatar
            
            // File type and category
            $table->enum('file_category', [
                'image', 'video', 'document', 'floor_plan', 
                'virtual_tour', 'legal_document', 'marketing', 'other'
            ])->default('image');
            
            $table->string('file_type'); // jpg, png, mp4, pdf, etc.
            $table->string('mime_type');
            
            // Storage info
            $table->string('disk')->default('public');
            $table->string('path');
            $table->string('thumbnail_path')->nullable();
            $table->string('watermark_path')->nullable();
            $table->string('original_name');
            $table->string('file_name');
            $table->bigInteger('size_bytes');
            $table->bigInteger('optimized_size_bytes')->nullable();
            
            // Media metadata
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->integer('duration')->nullable(); // for videos
            $table->json('exif_data')->nullable(); // camera, GPS, etc.
            
            // Optimization
            $table->boolean('is_optimized')->default(false);
            $table->float('optimization_ratio')->nullable(); // percentage saved
            $table->enum('optimization_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            
            // Watermark
            $table->boolean('has_watermark')->default(false);
            
            // Status and visibility
            $table->enum('status', ['active', 'inactive', 'deleted'])->default('active');
            $table->enum('visibility', ['public', 'private', 'protected'])->default('public');
            
            // Usage tracking
            $table->integer('download_count')->default(0);
            $table->integer('view_count')->default(0);
            $table->timestamp('last_downloaded_at')->nullable();
            $table->timestamp('last_viewed_at')->nullable();
            
            // Security
            $table->string('hash')->nullable(); // MD5 for duplicate detection
            $table->string('access_token')->nullable(); // for private files
            
            // Expiry
            $table->timestamp('expires_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['fileable_type', 'fileable_id']);
            $table->index(['user_id', 'file_category']);
            $table->index('status');
            $table->index('hash');
            $table->index('created_at');
        });
        
        // Property specific file relations (to allow multiple files per property)
        Schema::create('property_file_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->onDelete('cascade');
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            
            // Position and display
            $table->integer('order')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_featured')->default(false);
            
            // File usage within property
            $table->enum('usage_type', [
                'gallery', 'floor_plan', 'legal', 'video_tour', 
                'virtual_tour', 'marketing', 'certificate'
            ])->default('gallery');
            
            // Additional metadata
            $table->string('caption')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable(); // room info, floor info, etc.
            
            $table->timestamps();
            
            $table->unique(['property_id', 'file_id']);
            $table->index(['property_id', 'usage_type', 'order']);
            $table->index('is_primary');
            $table->index('is_featured');
        });
        
        // Project files
        Schema::create('project_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            
            // Project specific
            $table->integer('order')->default(0);
            $table->enum('usage_type', [
                'master_plan', 'architecture', 'interior', 'construction', 
                'legal', 'marketing', 'certificate', 'other'
            ])->default('other');
            
            $table->string('caption')->nullable();
            $table->json('metadata')->nullable(); // phase, building, etc.
            
            $table->timestamps();
            
            $table->unique(['project_id', 'file_id']);
            $table->index(['project_id', 'usage_type']);
        });
        
        // File versions (for history and rollback)
        Schema::create('file_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->integer('version_number');
            $table->string('path');
            $table->bigInteger('size_bytes');
            $table->string('changes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['file_id', 'version_number']);
        });
        
        // File usage logs
        Schema::create('file_usage_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->enum('action', ['view', 'download', 'share', 'embed'])->default('view');
            $table->json('metadata')->nullable();
            $table->timestamps();
            
            $table->index(['file_id', 'action', 'created_at']);
            $table->index('user_id');
        });
        
        // File conversions (for different formats)
        Schema::create('file_conversions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->string('format'); // webp, thumbnail_small, thumbnail_medium, etc.
            $table->string('path');
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->bigInteger('size_bytes');
            $table->timestamps();
            
            $table->unique(['file_id', 'format']);
        });
        
        // File compression jobs queue
        Schema::create('file_compression_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('attempts')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('file_compression_jobs');
        Schema::dropIfExists('file_conversions');
        Schema::dropIfExists('file_usage_logs');
        Schema::dropIfExists('file_versions');
        Schema::dropIfExists('project_files');
        Schema::dropIfExists('property_file_relations');
        Schema::dropIfExists('files');
    }
};