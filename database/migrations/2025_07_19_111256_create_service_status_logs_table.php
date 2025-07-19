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
        Schema::create('service_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('status_from')->nullable(); // Previous status
            $table->string('status_to'); // New status
            $table->timestamp('changed_at');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable(); // Optional reason for status change
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['service_id', 'changed_at']);
            $table->index(['service_id', 'status_to', 'changed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_status_logs');
    }
};
