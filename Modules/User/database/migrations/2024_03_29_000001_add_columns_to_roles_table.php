<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('roles', function (Blueprint $table) {
            // Add display_name column for human readable name
            if (!Schema::hasColumn('roles', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }
            
            // Add description column
            if (!Schema::hasColumn('roles', 'description')) {
                $table->text('description')->nullable()->after('display_name');
            }
            
            // Add package_type column (member, agent, agency)
            if (!Schema::hasColumn('roles', 'package_type')) {
                $table->enum('package_type', ['member', 'agent', 'agency'])->nullable()->after('description');
            }
            
            // Add is_core column (for roles that cannot be deleted)
            if (!Schema::hasColumn('roles', 'is_core')) {
                $table->boolean('is_core')->default(false)->after('package_type');
            }
            
            // Add is_default column (default role for new users)
            if (!Schema::hasColumn('roles', 'is_default')) {
                $table->boolean('is_default')->default(false)->after('is_core');
            }
        });
    }

    public function down()
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn('display_name');
            $table->dropColumn('description');
            $table->dropColumn('package_type');
            $table->dropColumn('is_core');
            $table->dropColumn('is_default');
        });
    }
};