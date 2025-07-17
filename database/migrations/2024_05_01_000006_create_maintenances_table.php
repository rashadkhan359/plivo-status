<?php

use App\Enums\MaintenanceStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('scheduled_start');
            $table->timestamp('scheduled_end');
            $table->string('status')->default(MaintenanceStatus::SCHEDULED->value);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['organization_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
}; 