<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->index('phone');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->index('start_date');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['payment_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropIndex(['phone']);
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex(['start_date']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['payment_date', 'status']);
        });
    }
};
