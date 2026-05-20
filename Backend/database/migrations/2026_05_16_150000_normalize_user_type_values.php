<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // no-op: user capabilities are inferred from related tables.
    }

    public function down(): void
    {
        // no-op
    }
};
