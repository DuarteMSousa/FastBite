<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'user_type')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('user_type');
            });
        }

        if (Schema::hasColumn('chat_participants', 'user_type')) {
            Schema::table('chat_participants', function (Blueprint $table): void {
                $table->dropColumn('user_type');
            });
        }
    }

    public function down(): void
    {
        // no-op
    }
};
