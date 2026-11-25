<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('emergency_contact_name')->nullable()->after('address');
            $table->string('emergency_contact_phone', 30)->nullable()->after('emergency_contact_name');
            $table->text('notes')->nullable()->after('emergency_contact_phone');
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
            $table->softDeletes();

            $table->index('status');
            $table->index('joining_date');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['joining_date']);
            $table->dropIndex(['name']);
            $table->dropSoftDeletes();
            $table->dropColumn(['emergency_contact_name', 'emergency_contact_phone', 'notes', 'created_by']);
        });
    }
};
