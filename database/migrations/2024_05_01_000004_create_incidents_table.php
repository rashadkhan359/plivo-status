<?php

use App\Enums\IncidentStatus;
use App\Enums\IncidentSeverity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default(IncidentStatus::INVESTIGATING->value);
            $table->string('severity')->default(IncidentSeverity::LOW->value);
            $table->timestamps();
            $table->timestamp('resolved_at')->nullable();
            $table->softDeletes();
            $table->index(['organization_id', 'service_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
}; 