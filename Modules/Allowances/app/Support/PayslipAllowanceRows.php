<?php

namespace Modules\Allowances\Support;

use Modules\Payroll\Models\PayrollEntry;

class PayslipAllowanceRows
{
    /**
     * Build earnings section rows with one line per allowance on the entry.
     *
     * @return array<int, array{type: string, label: string, code: string|null}>
     */
    public static function earningsSection(?PayrollEntry $entry = null): array
    {
        $rows = [
            ['type' => 'spacer', 'label' => 'EARNINGS', 'code' => null],
            ['type' => 'income', 'label' => 'BASIC',    'code' => null],
        ];

        $allowances = $entry?->relationLoaded('allowances')
            ? $entry->allowances
            : collect();

        if ($allowances->isNotEmpty()) {
            foreach ($allowances as $allowance) {
                $rows[] = [
                    'type'  => 'allowance',
                    'label' => strtoupper($allowance->name),
                    'code'  => $allowance->code,
                ];
            }
        } else {
            $rows[] = ['type' => 'income', 'label' => 'ALLOWANCE', 'code' => null];
        }

        return $rows;
    }

    /**
     * Merge dynamic earnings rows with the static deduction/total rows.
     */
    public static function merge(array $deductionAndTotalRows, ?PayrollEntry $entry = null): array
    {
        return array_merge(self::earningsSection($entry), $deductionAndTotalRows);
    }
}
