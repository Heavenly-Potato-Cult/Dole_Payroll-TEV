<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private array $duplicateCodes = [
        'HDMF_CAL',
        'GSIS_REAL_ESTATE',
        'GSIS_EMERGENCY',
        'PROVIDENT_FUND',
        'CARESS_CARES',
        'SMART_PLAN_GOLD',
        'REFUND_VARIOUS',
    ];

    public function up(): void
    {
        DB::table('deduction_types')
            ->whereIn('code', $this->duplicateCodes)
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        DB::table('deduction_types')
            ->whereIn('code', $this->duplicateCodes)
            ->update(['is_active' => true]);
    }
};
