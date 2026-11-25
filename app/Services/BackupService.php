<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackupService
{
    public function createJsonSnapshot(): array
    {
        $tables = [
            'users',
            'membership_plans',
            'members',
            'subscriptions',
            'payments',
            'attendances',
            'settings',
            'activity_logs',
        ];

        $data = [
            'generated_at' => now()->toIso8601String(),
            'app_name' => config('app.name'),
            'database_driver' => Schema::getConnection()->getDriverName(),
            'tables' => [],
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                $rows = DB::table($table)->get()->map(function ($row) use ($table) {
                    $arr = (array) $row;
                    if ($table === 'users') {
                        unset($arr['password'], $arr['remember_token']);
                    }
                    return $arr;
                })->toArray();

                $data['tables'][$table] = $rows;
            }
        }

        ActivityLog::record('system.backup', 'Generated database JSON backup snapshot.');

        return $data;
    }
}
