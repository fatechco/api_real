<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('permissions', function (Blueprint $table) {
            // Add module column for grouping permissions
            if (!Schema::hasColumn('permissions', 'module')) {
                $table->string('module')->nullable()->after('name');
            }
            
            // Add display_name column for human readable name
            if (!Schema::hasColumn('permissions', 'display_name')) {
                $table->string('display_name')->nullable()->after('module');
            }
            
            // Add description column
            if (!Schema::hasColumn('permissions', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
            
            // Add is_core column (for permissions that cannot be deleted)
            if (!Schema::hasColumn('permissions', 'is_core')) {
                $table->boolean('is_core')->default(false)->after('description');
            }
            
            // Add index on module for faster filtering
            $table->index('module');
        });
    }

    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropColumn('module');
            $table->dropColumn('display_name');
            $table->dropColumn('description');
            $table->dropColumn('is_core');
        });
    }
};