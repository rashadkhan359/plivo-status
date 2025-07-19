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
        Schema::table('incidents', function (Blueprint $table) {
            // Add user attribution
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete()->after('severity');
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete()->after('created_by');
            
            // Add indexes for performance
            $table->index('created_by');
            $table->index('resolved_by');
            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'severity']);
            $table->index('resolved_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex(['resolved_at']);
            $table->dropIndex(['organization_id', 'severity']);
            $table->dropIndex(['organization_id', 'status']);
            $table->dropIndex(['resolved_by']);
            $table->dropIndex(['created_by']);
            $table->dropForeign(['resolved_by']);
            $table->dropForeign(['created_by']);
            $table->dropColumn(['created_by', 'resolved_by']);
        });
    }
};
