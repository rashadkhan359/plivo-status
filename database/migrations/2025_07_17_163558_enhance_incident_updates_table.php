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
        Schema::table('incident_updates', function (Blueprint $table) {
            // Add title field for update summaries
            $table->string('title')->nullable()->after('incident_id');
            
            // Rename message to description for consistency
            $table->renameColumn('message', 'description');
            
            // Add user attribution
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete()->after('status');
            
            // Add indexes for performance
            $table->index('created_by');
            $table->index(['incident_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incident_updates', function (Blueprint $table) {
            $table->dropIndex(['incident_id', 'created_at']);
            $table->dropIndex(['created_by']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['title', 'created_by']);
            $table->renameColumn('description', 'message');
        });
    }
};
