<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Drop the existing unique constraint on domain
            $table->dropUnique(['domain']);
            
            // Make domain nullable
            $table->string('domain')->nullable()->change();
        });
        
        // Add a raw unique constraint that ignores null values (PostgreSQL/MySQL compatible)
        DB::statement('CREATE UNIQUE INDEX organizations_domain_unique_not_null ON organizations (domain) WHERE domain IS NOT NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the partial unique constraint
        DB::statement('DROP INDEX IF EXISTS organizations_domain_unique_not_null');
        
        Schema::table('organizations', function (Blueprint $table) {
            // Make domain not nullable again (this might fail if there are null values)
            $table->string('domain')->nullable(false)->change();
            
            // Restore the original unique constraint
            $table->unique('domain');
        });
    }
};
