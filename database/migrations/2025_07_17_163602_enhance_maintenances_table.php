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
        Schema::table('maintenances', function (Blueprint $table) {
            // Add actual start and end times for tracking
            $table->timestamp('actual_start')->nullable()->after('scheduled_end');
            $table->timestamp('actual_end')->nullable()->after('actual_start');
            
            // Add user attribution
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete()->after('actual_end');
            
            // Make service_id nullable to support organization-wide maintenance
            $table->foreignId('service_id')->nullable()->change();
            
            // Add indexes for performance
            $table->index('created_by');
            $table->index(['organization_id', 'status']);
            $table->index('scheduled_start');
            $table->index('actual_start');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('maintenances', function (Blueprint $table) {
            $table->dropIndex(['actual_start']);
            $table->dropIndex(['scheduled_start']);
            $table->dropIndex(['organization_id', 'status']);
            $table->dropIndex(['created_by']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['actual_start', 'actual_end', 'created_by']);
        });
    }
};
