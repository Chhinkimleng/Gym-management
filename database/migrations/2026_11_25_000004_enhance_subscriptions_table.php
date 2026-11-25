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
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->dropForeign(['membership_plan_id']);
            });
            DB::statement("ALTER TABLE subscriptions MODIFY status VARCHAR(30) NOT NULL DEFAULT 'active'");
        } else {
            Schema::table('subscriptions', function (Blueprint $table) {
                $table->string('status', 30)->default('active')->change();
            });
        }

        Schema::table('subscriptions', function (Blueprint $table) use ($driver) {
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $table->foreign('membership_plan_id')
                    ->references('id')
                    ->on('membership_plans')
                    ->restrictOnDelete();
            }

            $table->string('plan_name')->nullable()->after('membership_plan_id');
            $table->decimal('plan_price', 10, 2)->nullable()->after('plan_name');
            $table->integer('duration_months')->nullable()->after('plan_price');
            $table->unsignedInteger('duration_days')->nullable()->after('duration_months');
            $table->text('notes')->nullable()->after('status');
            $table->timestamp('cancelled_at')->nullable()->after('notes');
            $table->foreignId('created_by')->nullable()->after('cancelled_at')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index(['member_id', 'status']);
            $table->index(['status', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['member_id', 'status']);
            $table->dropIndex(['status', 'end_date']);
            $table->dropSoftDeletes();
            $table->dropColumn([
                'plan_name',
                'plan_price',
                'duration_months',
                'duration_days',
                'notes',
                'cancelled_at',
                'created_by',
            ]);
        });
    }
};
