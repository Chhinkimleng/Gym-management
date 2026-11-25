<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->string('status', 30)->default('checked_in')->after('check_out');
            $table->foreignId('recorded_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->string('notes')->nullable()->after('recorded_by');

            $table->index('check_in');
            $table->index(['member_id', 'check_in']);
            $table->index('check_out');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['recorded_by']);
            $table->dropIndex(['check_in']);
            $table->dropIndex(['member_id', 'check_in']);
            $table->dropIndex(['check_out']);
            $table->dropColumn(['status', 'recorded_by', 'notes']);
        });
    }
};
