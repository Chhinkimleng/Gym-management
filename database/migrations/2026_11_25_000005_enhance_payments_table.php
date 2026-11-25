<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['subscription_id']);
            });
            DB::statement("ALTER TABLE payments MODIFY subscription_id BIGINT UNSIGNED NULL");
            DB::statement("ALTER TABLE payments MODIFY payment_method VARCHAR(50) NOT NULL DEFAULT 'cash'");
            DB::statement("ALTER TABLE payments MODIFY status VARCHAR(30) NOT NULL DEFAULT 'paid'");
        } else {
            Schema::table('payments', function (Blueprint $table) {
                $table->unsignedBigInteger('subscription_id')->nullable()->change();
                $table->string('payment_method', 50)->default('cash')->change();
                $table->string('status', 30)->default('paid')->change();
            });
        }

        Schema::table('payments', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $table->foreign('subscription_id')
                    ->references('id')
                    ->on('subscriptions')
                    ->nullOnDelete();
            }

            $table->string('payment_reference', 60)->nullable()->unique()->after('id');
            $table->decimal('total_amount', 10, 2)->default(0)->after('subscription_id');
            $table->decimal('amount_paid', 10, 2)->default(0)->after('total_amount');
            $table->decimal('remaining_balance', 10, 2)->default(0)->after('amount_paid');
            $table->decimal('refunded_amount', 10, 2)->default(0)->after('remaining_balance');
            $table->timestamp('refunded_at')->nullable()->after('refunded_amount');
            $table->string('refund_reason')->nullable()->after('refunded_at');
            $table->foreignId('recorded_by')->nullable()->after('notes')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index('payment_date');
            $table->index('status');
            $table->index('payment_method');
            $table->index(['member_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['member_id', 'status']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'payment_reference',
                'total_amount',
                'amount_paid',
                'remaining_balance',
                'refunded_amount',
                'refunded_at',
                'refund_reason',
                'recorded_by',
            ]);
        });
    }
};
