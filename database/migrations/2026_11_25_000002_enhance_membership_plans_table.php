<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->string('billing_cycle', 30)->default('monthly')->after('description');
            $table->integer('duration_months')->nullable()->change();
            $table->unsignedInteger('duration_days')->nullable()->after('duration_months');
            $table->json('benefits')->nullable()->after('duration_days');
            $table->softDeletes();

            $table->index('is_active');
            $table->index('billing_cycle');
        });
    }

    public function down(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
            $table->dropIndex(['billing_cycle']);
            $table->dropSoftDeletes();
            $table->dropColumn(['billing_cycle', 'duration_days', 'benefits']);
            $table->integer('duration_months')->nullable(false)->change();
        });
    }
};
