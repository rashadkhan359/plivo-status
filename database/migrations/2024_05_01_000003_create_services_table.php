<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['operational', 'degraded', 'partial_outage', 'major_outage'])->default('operational');
            $table->timestamps();
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
}; 