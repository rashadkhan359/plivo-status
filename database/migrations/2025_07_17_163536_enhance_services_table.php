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
        Schema::table('services', function (Blueprint $table) {
            // Add team assignment (nullable for organization-wide services)
            $table->foreignId('team_id')->nullable()->constrained('teams')->nullOnDelete()->after('organization_id');
            
            // Add visibility control
            $table->enum('visibility', ['public', 'private'])->default('public')->after('status');
            
            // Add ordering for display priority
            $table->integer('order')->default(0)->after('visibility');
            
            // Add user attribution
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete()->after('order');
            
            // Add indexes for performance
            $table->index('team_id');
            $table->index('created_by');
            $table->index(['organization_id', 'order']);
            $table->index(['organization_id', 'visibility']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropIndex(['organization_id', 'visibility']);
            $table->dropIndex(['organization_id', 'order']);
            $table->dropIndex(['created_by']);
            $table->dropIndex(['team_id']);
            $table->dropForeign(['team_id']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['team_id', 'visibility', 'order', 'created_by']);
        });
    }
};
