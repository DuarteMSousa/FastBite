<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // no-op: user capabilities are inferred from related tables.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // no-op
    }
};
