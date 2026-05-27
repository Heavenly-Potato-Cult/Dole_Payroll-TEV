<?php

namespace Modules\Tev\Services;

use Modules\Tev\Models\PerDiemRate;
use Modules\Tev\Models\TevRequest;

/**
 * TevComputationService
 *
 * Handles per-diem rate lookups, TEV totals computation,
 * and TEV number generation.
 */
class TevComputationService
{
    /**
     * Recompute and persist totals for a TEV request from its itinerary lines.
     *
     * @param  TevRequest $tev  Must have itineraryLines already loadable.
     * @return array{
     *   total_days:           int,
     *   total_transportation: float,
     *   total_per_diem:       float,
     *   grand_total:          float,
     * }
     */
    public function computeTotals(TevRequest $tev): array
    {
        $lines = $tev->itineraryLines()->get();

        $totalTransportation = 0.0;
        $totalPerDiem        = 0.0;
        $distinctDates       = [];

        foreach ($lines as $line) {
            $totalTransportation += (float) $line->transportation_cost;
            $totalPerDiem        += (float) $line->per_diem_amount;

            $dateKey = $line->travel_date instanceof \Carbon\Carbon
                ? $line->travel_date->toDateString()
                : (string) $line->travel_date;

            $distinctDates[$dateKey] = true;
        }

        $totalTransportation = round($totalTransportation, 2);
        $totalPerDiem        = round($totalPerDiem, 2);
        $totalDays           = count($distinctDates);
        $grandTotal          = round(
            $totalTransportation + $totalPerDiem + (float) $tev->total_other_expenses,
            2
        );

        $tev->update([
            'total_days'           => $totalDays,
            'total_transportation' => $totalTransportation,
            'total_per_diem'       => $totalPerDiem,
            'grand_total'          => $grandTotal,
        ]);

        return [
            'total_days'           => $totalDays,
            'total_transportation' => $totalTransportation,
            'total_per_diem'       => $totalPerDiem,
            'grand_total'          => $grandTotal,
        ];
    }

    /**
     * Look up the per diem rate for a given travel type and day type.
     *
     * Uses the latest seeded year available.
     *
     * @param  string $travelType  'local' | 'regional' | 'national'
     * @param  bool   $isHalfDay
     * @return float
     */
    public function getPerDiemRate(string $travelType, bool $isHalfDay = false): float
    {
        $rate = PerDiemRate::where('travel_type', $travelType)
            ->orderByDesc('year')
            ->first();

        if (!$rate) {
            return 0.0;
        }

        if ($isHalfDay && $rate->half_day_rate !== null) {
            return (float) $rate->half_day_rate;
        }

        return (float) $rate->daily_rate;
    }

    /**
     * Generate a unique TEV number in the format TEV-YYYY-NNNN.
     *
     * Increments based on the count of all TEV records created this year.
     *
     * @return string  e.g. 'TEV-2025-0001'
     */
    public function generateTevNo(): string
    {
        $year  = now()->year;
        $count = \Modules\Tev\Models\TevRequest::withTrashed()
            ->whereYear('created_at', $year)
            ->count();

        $sequence = str_pad($count + 1, 4, '0', STR_PAD_LEFT);

        return "TEV-{$year}-{$sequence}";
    }

    /**
     * Generate a unique TEV series number.
     *
     * This is a separate series that increments independently for all TEV requests,
     * providing a continuous sequence number regardless of year.
     *
     * @return int  e.g. 1234
     */
    public function generateTevSeriesNo(): int
    {
        $lastSeries = \Modules\Tev\Models\TevRequest::withTrashed()
            ->max('tev_series_no');

        return ($lastSeries ?? 0) + 1;
    }
}
