<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // no-op: legacy user_type backfill is no longer needed.
    }

    public function down(): void
    {
        // no-op
    }
};
