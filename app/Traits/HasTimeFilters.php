<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait HasTimeFilters
{
    /**
     * Apply time-based filters to a query.
     * 
     * Supported parameters:
     * - period_type: 'week', 'month', 'quarter', 'year'
     * - period_value: the specific value (format depends on type)
     */
    public function scopeApplyTimeFilters($query, Request $request)
    {
        if (!$request->filled('period_type')) {
            return $query;
        }

        $type = $request->period_type;
        $value = $request->period_value;

        switch ($type) {
            case 'week':
                if ($request->filled('period_week')) {
                    $query->where('week', $request->period_week);
                }
                break;

            case 'month':
                if ($request->filled('period_month')) {
                    $date = Carbon::parse($request->period_month);
                    $query->whereMonth($query->getModel()->getTable() . '.created_at', $date->month)
                          ->whereYear($query->getModel()->getTable() . '.created_at', $date->year);
                }
                break;

            case 'quarter':
                if ($request->filled('period_quarter')) {
                    // Expect format YYYY-QX
                    $parts = explode('-Q', $request->period_quarter);
                    if (count($parts) === 2) {
                        $query->whereYear($query->getModel()->getTable() . '.created_at', $parts[0])
                              ->where(DB::raw("QUARTER(" . $query->getModel()->getTable() . ".created_at)"), $parts[1]);
                    }
                }
                break;

            case 'year':
                if ($request->filled('period_year')) {
                    $query->whereYear($query->getModel()->getTable() . '.created_at', $request->period_year);
                }
                break;
        }

        return $query;
    }

    /**
     * Get available periods for selectors.
     */
    public static function getAvailableTimePeriods()
    {
        $years = DB::table('reimbursements')
            ->select(DB::raw('YEAR(created_at) as year'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->pluck('year');

        // If no data, use current year
        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        $weeks = DB::table('reimbursements')
            ->select('week')
            ->whereNotNull('week')
            ->distinct()
            ->orderBy('week', 'desc')
            ->pluck('week');

        // Fetch dynamic quarters that HAVE data
        $dynamicQuarters = DB::table('reimbursements')
            ->select(DB::raw('YEAR(created_at) as year'), DB::raw('QUARTER(created_at) as quarter'))
            ->distinct()
            ->orderBy('year', 'desc')
            ->orderBy('quarter', 'desc')
            ->get()
            ->map(function($row) {
                $labels = [
                    1 => 'Trimestre 1 (Ene-Mar)',
                    2 => 'Trimestre 2 (Abr-Jun)',
                    3 => 'Trimestre 3 (Jul-Sep)',
                    4 => 'Trimestre 4 (Oct-Dic)',
                ];
                return [
                    'value' => $row->year . '-Q' . $row->quarter,
                    'label' => $row->year . ' - ' . ($labels[$row->quarter] ?? "Trimestre {$row->quarter}")
                ];
            });

        return [
            'years' => $years,
            'weeks' => $weeks,
            'months' => collect(range(1, 12))->map(function($m) {
                return [
                    'value' => str_pad($m, 2, '0', STR_PAD_LEFT),
                    'label' => Carbon::create(null, $m)->translatedFormat('F')
                ];
            }),
            'quarters' => $dynamicQuarters
        ];
    }
}
