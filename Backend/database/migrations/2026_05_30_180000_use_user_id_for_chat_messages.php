<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(
            'CREATE UNIQUE INDEX IF NOT EXISTS chat_participants_chat_id_user_id_unique ON chat_participants (chat_id, user_id)'
        );

        if (! Schema::hasColumn('messages', 'sender_participant_id')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table): void {
            if (! Schema::hasColumn('messages', 'user_id')) {
                $table->foreignUuid('user_id')->nullable()->after('chat_id')->constrained('users')->cascadeOnDelete();
            }
        });

        DB::table('messages')
            ->join('chat_participants', 'messages.sender_participant_id', '=', 'chat_participants.id')
            ->update(['messages.user_id' => DB::raw('chat_participants.user_id')]);

        Schema::table('messages', function (Blueprint $table): void {
            if (Schema::hasColumn('messages', 'sender_participant_id')) {
                $table->dropForeign(['sender_participant_id']);
                $table->dropColumn('sender_participant_id');
            }
        });

        DB::statement('ALTER TABLE messages ALTER COLUMN user_id SET NOT NULL');

        Schema::table('messages', function (Blueprint $table): void {
            $table->foreign(['chat_id', 'user_id'], 'messages_chat_id_user_id_foreign')
                ->references(['chat_id', 'user_id'])
                ->on('chat_participants')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('messages', 'user_id') || Schema::hasColumn('messages', 'sender_participant_id')) {
            return;
        }

        Schema::table('messages', function (Blueprint $table): void {
            $table->dropForeign('messages_chat_id_user_id_foreign');
            $table->foreignUuid('sender_participant_id')->nullable()->after('chat_id');
        });

        DB::table('messages')
            ->join('chat_participants', function ($join): void {
                $join->on('messages.chat_id', '=', 'chat_participants.chat_id')
                    ->on('messages.user_id', '=', 'chat_participants.user_id');
            })
            ->update(['messages.sender_participant_id' => DB::raw('chat_participants.id')]);

        Schema::table('messages', function (Blueprint $table): void {
            $table->foreign('sender_participant_id')->references('id')->on('chat_participants')->cascadeOnDelete();
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        DB::statement('DROP INDEX IF EXISTS chat_participants_chat_id_user_id_unique');
    }
};
