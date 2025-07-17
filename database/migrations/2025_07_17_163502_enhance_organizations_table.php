<?php

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
        Schema::table('organizations', function (Blueprint $table) {
            // Add new fields for enhanced organization management
            $table->string('logo')->nullable()->after('domain');
            $table->json('settings')->nullable()->after('logo');
            $table->string('timezone')->default('UTC')->after('settings');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('timezone');
            
            // Make domain nullable (might already be nullable)
            $table->string('domain')->nullable()->change();
            
            // Add indexes for performance
            $table->index('created_by');
            $table->index('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropIndex(['created_by']);
            $table->dropIndex(['slug']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['logo', 'settings', 'timezone', 'created_by']);
        });
    }
};
