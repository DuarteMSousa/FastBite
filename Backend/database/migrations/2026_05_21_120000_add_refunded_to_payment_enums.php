<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('status', [
                'PENDING',
                'COMPLETED',
                'FAILED',
                'CANCELLED',
                'REFUNDED',
            ])->change();
        });

        Schema::table('payment_events', function (Blueprint $table): void {
            $table->enum('event_type', [
                'PAYMENT_CREATED',
                'PAYMENT_COMPLETED',
                'PAYMENT_FAILED',
                'PAYMENT_EXPIRED',
                'PAYMENT_CANCELLED',
                'PAYMENT_REFUNDED',
            ])->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->enum('status', [
                'PENDING',
                'COMPLETED',
                'FAILED',
                'CANCELLED',
            ])->change();
        });

        Schema::table('payment_events', function (Blueprint $table): void {
            $table->enum('event_type', [
                'PAYMENT_CREATED',
                'PAYMENT_COMPLETED',
                'PAYMENT_FAILED',
                'PAYMENT_EXPIRED',
                'PAYMENT_CANCELLED',
            ])->change();
        });
    }
};
