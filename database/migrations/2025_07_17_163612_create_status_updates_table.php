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
        Schema::create('status_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            
            // Polymorphic relationships for different entity types
            $table->foreignId('service_id')->nullable()->constrained('services')->cascadeOnDelete();
            $table->foreignId('incident_id')->nullable()->constrained('incidents')->cascadeOnDelete();
            $table->foreignId('maintenance_id')->nullable()->constrained('maintenances')->cascadeOnDelete();
            
            // Update metadata
            $table->enum('type', ['service_status', 'incident', 'maintenance'])->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            
            // User attribution
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            $table->timestamps();
            
            // Add indexes for performance
            $table->index('organization_id');
            $table->index(['organization_id', 'type']);
            $table->index(['organization_id', 'created_at']);
            $table->index('service_id');
            $table->index('incident_id');
            $table->index('maintenance_id');
            $table->index('created_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('status_updates');
    }
};
