<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('outbox_events', 'event_name') && ! Schema::hasColumn('outbox_events', 'event_type')) {
            Schema::table('outbox_events', function (Blueprint $table): void {
                $table->renameColumn('event_name', 'event_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('outbox_events', 'event_type') && ! Schema::hasColumn('outbox_events', 'event_name')) {
            Schema::table('outbox_events', function (Blueprint $table): void {
                $table->renameColumn('event_type', 'event_name');
            });
        }
    }
};
