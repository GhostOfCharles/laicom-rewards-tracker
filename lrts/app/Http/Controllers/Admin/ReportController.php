<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ReportTableExport;
use App\Http\Controllers\Controller;
use App\Models\EarnedReward;
use App\Models\Inventory;
use App\Models\PremiumProduct;
use App\Models\Promotion;
use App\Models\Receipt;
use App\Models\StockMovement;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Excel as ExcelWriter;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    private const REPORTS = [
        'promo_performance' => 'Promotion Performance',
        'premium_stock' => 'Premium Reward Stock Movement',
        'inventory_movement' => 'Inventory Stock Movement',
    ];

    private const RANGES = [
        'today', 'last_7_days', 'last_30_days', 'last_90_days',
        'last_12_months', 'year_to_date', 'all_time', 'custom',
    ];

    public function index(Request $request)
    {
        $validated = $request->validate([
            'report_type' => ['nullable', 'in:' . implode(',', array_keys(self::REPORTS))],
            'range' => ['nullable', 'in:' . implode(',', self::RANGES)],
            'start_date' => ['required_if:range,custom', 'nullable', 'date_format:Y-m-d', 'before_or_equal:end_date'],
            'end_date' => ['required_if:range,custom', 'nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'export_format' => 'nullable|in:xlsx,csv',
        ]);

        $reportType = $validated['report_type'] ?? 'promo_performance';
        $rangeKey = $validated['range'] ?? 'last_30_days';
        [$from, $to, $rangeLabel] = $this->resolveDateRange($rangeKey, $validated);
        $report = match ($reportType) {
            'premium_stock' => $this->premiumStockReport($from, $to),
            'inventory_movement' => $this->inventoryMovementReport($from, $to),
            default => $this->promotionPerformanceReport($from, $to),
        };

        if (isset($validated['export_format'])) {
            return $this->downloadReport($report, $rangeLabel, $validated['export_format']);
        }

        return view('admin.reports', [
            'reports' => self::REPORTS,
            'reportType' => $reportType,
            'rangeKey' => $rangeKey,
            'rangeLabel' => $rangeLabel,
            'fromDate' => $from->toDateString(),
            'toDate' => $to->toDateString(),
            'columns' => $report['columns'],
            'rows' => $report['rows'],
            'summaries' => $report['summaries'],
            'charts' => $report['charts'] ?? [],
        ]);
    }

    private function resolveDateRange(string $key, array $input): array
    {
        $now = CarbonImmutable::now();

        if ($key === 'custom') {
            $from = CarbonImmutable::createFromFormat('Y-m-d', $input['start_date'])->startOfDay();
            $to = CarbonImmutable::createFromFormat('Y-m-d', $input['end_date'])->endOfDay();
        } elseif ($key === 'all_time') {
            $firstDates = array_filter([
                Receipt::min('submitted_at'),
                EarnedReward::min('created_at'),
                StockMovement::min('created_at'),
            ]);
            $from = $firstDates
                ? CarbonImmutable::parse(min($firstDates))->startOfDay()
                : $now->startOfDay();
            $to = $now->endOfDay();
        } else {
            $from = match ($key) {
                'today' => $now->startOfDay(),
                'last_7_days' => $now->subDays(6)->startOfDay(),
                'last_90_days' => $now->subDays(89)->startOfDay(),
                'last_12_months' => $now->subMonthsNoOverflow(12)->startOfDay(),
                'year_to_date' => $now->startOfYear(),
                default => $now->subDays(29)->startOfDay(),
            };
            $to = $now->endOfDay();
        }

        return [$from, $to, $from->format('M d, Y') . ' to ' . $to->format('M d, Y')];
    }

    private function promotionPerformanceReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $qualificationRows = DB::table('promotions as p')
            ->joinSub(
                DB::table('receipt_items')
                    ->select('receipt_id', 'product_name')
                    ->selectRaw('SUM(quantity) as purchased_quantity')
                    ->groupBy('receipt_id', 'product_name'),
                'qualified_items',
                fn ($join) => $join->on('qualified_items.product_name', '=', 'p.buy_product_name')
            )
            ->join('receipts as r', 'r.id', '=', 'qualified_items.receipt_id')
            ->whereColumn('qualified_items.purchased_quantity', '>=', 'p.required_quantity')
            ->whereBetween('r.submitted_at', [$from, $to])
            ->select('p.id', 'r.status')
            ->selectRaw('COUNT(DISTINCT r.id) as receipt_count')
            ->groupBy('p.id', 'r.status')
            ->get()
            ->groupBy('id');

        $issuedByPromotion = EarnedReward::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('promotion_id')
            ->selectRaw('SUM(reward_quantity) as total')
            ->groupBy('promotion_id')
            ->pluck('total', 'promotion_id');

        $claimedByPromotion = EarnedReward::query()
            ->where('claim_status', 'claimed')
            ->whereBetween('created_at', [$from, $to])
            ->select('promotion_id')
            ->selectRaw('SUM(reward_quantity) as total')
            ->groupBy('promotion_id')
            ->pluck('total', 'promotion_id');

        $promotions = Promotion::with('premiumProduct')->orderBy('id')->get();
        $promotionChartRows = [];
        $rows = $promotions->map(function (Promotion $promotion) use ($qualificationRows, $issuedByPromotion, $claimedByPromotion, &$promotionChartRows) {
            $activity = $qualificationRows->get($promotion->id, collect());
            $qualified = (int) $activity->sum('receipt_count');
            $approved = (int) $activity->where('status', 'approved')->sum('receipt_count');
            $rejected = (int) $activity->where('status', 'rejected')->sum('receipt_count');
            $issued = (int) ($issuedByPromotion[$promotion->id] ?? 0);
            $claimed = (int) ($claimedByPromotion[$promotion->id] ?? 0);
            $rewardName = $promotion->premiumProduct?->name ?? 'Deleted reward product';
            $promotionChartRows[] = [
                'title' => $promotion->title ?: "BUY {$promotion->required_quantity} {$promotion->buy_product_name} GET {$promotion->reward_quantity} {$rewardName}",
                'issued' => $issued,
                'claimed' => $claimed,
            ];

            return [
                $promotion->title ?: "BUY {$promotion->required_quantity} {$promotion->buy_product_name} GET {$promotion->reward_quantity} {$rewardName}",
                $promotion->buy_product_name,
                (int) $promotion->required_quantity,
                $rewardName,
                (int) $promotion->reward_quantity,
                $qualified,
                $approved,
                $rejected,
                $issued,
                $claimed,
                $issued > 0 ? number_format(($claimed / $issued) * 100, 1) . '%' : '0.0%',
                (int) ($promotion->premiumProduct?->stock ?? 0),
            ];
        })->all();

        $submitted = Receipt::whereBetween('submitted_at', [$from, $to])->count();
        $approved = Receipt::whereBetween('submitted_at', [$from, $to])->where('status', 'approved')->count();
        $rejected = Receipt::whereBetween('submitted_at', [$from, $to])->where('status', 'rejected')->count();
        $pending = max(0, $submitted - $approved - $rejected);
        $rewardsIssued = (int) EarnedReward::whereBetween('created_at', [$from, $to])->sum('reward_quantity');
        $rewardsClaimed = (int) EarnedReward::where('claim_status', 'claimed')->whereBetween('created_at', [$from, $to])->sum('reward_quantity');

        $promotionChartRows = collect($promotionChartRows)
            ->sortByDesc('issued')
            ->take(5)
            ->values()
            ->all();

        return [
            'title' => self::REPORTS['promo_performance'],
            'columns' => ['Promotion Title', 'Product to Buy', 'Required Quantity', 'Reward Product', 'Reward Quantity', 'Receipts Qualified', 'Approved', 'Rejected', 'Rewards Issued', 'Rewards Claimed', 'Claim Rate', 'Stock Remaining'],
            'rows' => $rows,
            'summaries' => [
                ['Total Receipts Submitted', $submitted],
                ['Total Approved', $approved],
                ['Total Rejected', $rejected],
                ['Total Rewards Issued', $rewardsIssued],
                ['Total Rewards Claimed', $rewardsClaimed],
            ],
            'charts' => [
                'approval' => [
                    'total' => $submitted,
                    'segments' => [
                        ['label' => 'Approved', 'value' => $approved, 'color' => '#12284c'],
                        ['label' => 'Rejected', 'value' => $rejected, 'color' => '#e3343f'],
                        ['label' => 'Pending', 'value' => $pending, 'color' => '#8492a6'],
                    ],
                ],
                'bar_charts' => [[
                    'title' => 'REWARDS ISSUED VS CLAIMED',
                    'primary_label' => 'ISSUED',
                    'secondary_label' => 'CLAIMED',
                    'rows' => array_map(fn (array $promotion) => [
                        'title' => $promotion['title'],
                        'primary' => $promotion['issued'],
                        'secondary' => $promotion['claimed'],
                    ], $promotionChartRows),
                    'note' => count($promotionChartRows) === 5 ? 'Showing the five promotions with the most rewards issued in this period.' : null,
                ]],
            ],
        ];
    }

    private function premiumStockReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $products = PremiumProduct::query()
            ->withSum(['earnedRewards as range_rewards_issued' => fn ($query) => $query->whereBetween('created_at', [$from, $to])], 'reward_quantity')
            ->withSum(['earnedRewards as range_rewards_claimed' => fn ($query) => $query->where('claim_status', 'claimed')->whereBetween('claimed_at', [$from, $to])], 'reward_quantity')
            ->orderBy('name')
            ->get();

        $rows = $products->map(function (PremiumProduct $product) {
            $issued = (int) ($product->range_rewards_issued ?? 0);
            $claimed = (int) ($product->range_rewards_claimed ?? 0);
            $stock = (int) $product->stock;
            $status = $stock === 0 ? 'OUT OF STOCK' : ($stock <= 10 ? 'LOW' : ($stock <= 50 ? 'MID' : 'HIGH'));

            $daysUntilExpiry = $product->daysUntilExpiry();
            $expiry = match ($product->expiryStatus()) {
                'non_perishable' => 'N/A',
                'expired' => 'Expired ' . abs($daysUntilExpiry ?? 0) . ' days ago',
                'expiring_soon' => 'Expires in ' . ($daysUntilExpiry ?? 0) . ' days',
                default => 'Expires ' . ($product->expiry_date?->format('M d, Y') ?? 'date unavailable'),
            };

            return [$product->name, $product->item_code, $stock, $issued, $claimed, $issued, $status, $expiry];
        })->all();

        $stockChartRows = collect($rows)->sortByDesc(fn (array $row) => $row[2])->take(5)->map(fn (array $row) => ['title' => $row[0] . ' (' . $row[1] . ')', 'primary' => $row[2], 'secondary' => null])->values()->all();
        $rewardChartRows = collect($rows)->sortByDesc(fn (array $row) => $row[3])->take(5)->map(fn (array $row) => ['title' => $row[0] . ' (' . $row[1] . ')', 'primary' => $row[3], 'secondary' => $row[4]])->values()->all();

        return [
            'title' => self::REPORTS['premium_stock'],
            'columns' => ['Product Name', 'Item Code', 'Current Stock', 'Rewards Issued', 'Rewards Claimed', 'Net Outflow (Issued)', 'Stock Status', 'Expiry'],
            'rows' => $rows,
            'summaries' => [
                ['Total Rewards Issued', array_sum(array_column($rows, 3))],
                ['Total Rewards Claimed', array_sum(array_column($rows, 4))],
                ['Products Below Low-Stock Threshold (10 or Less)', $products->where('stock', '<=', 10)->count()],
                ['Premium Products Expiring Within 30 Days', $products->filter(fn (PremiumProduct $product) => $product->isExpiringSoon())->count()],
                ['Premium Products Already Expired', $products->filter(fn (PremiumProduct $product) => $product->isExpired())->count()],
            ],
            'charts' => [
                'bar_charts' => [
                    ['title' => 'CURRENT REWARD STOCK BY PRODUCT', 'primary_label' => 'IN STOCK', 'secondary_label' => null, 'rows' => $stockChartRows, 'note' => count($stockChartRows) === 5 ? 'Showing the five products with the most stock.' : null],
                    ['title' => 'REWARDS ISSUED VS CLAIMED', 'primary_label' => 'ISSUED', 'secondary_label' => 'CLAIMED', 'rows' => $rewardChartRows, 'note' => null],
                ],
            ],
        ];
    }

    private function inventoryMovementReport(CarbonImmutable $from, CarbonImmutable $to): array
    {
        $movementTotals = StockMovement::query()
            ->whereBetween('created_at', [$from, $to])
            ->select('inventory_id')
            ->selectRaw("SUM(CASE WHEN type = 'inflow' THEN quantity ELSE 0 END) as total_inflow")
            ->selectRaw("SUM(CASE WHEN type = 'outflow' THEN quantity ELSE 0 END) as total_outflow")
            ->groupBy('inventory_id')
            ->get()
            ->keyBy('inventory_id');

        $today = CarbonImmutable::today();
        $expiringLimit = $today->addDays(30);
        $inventory = Inventory::orderBy('name')->get();
        $rows = $inventory->map(function (Inventory $item) use ($movementTotals, $today, $expiringLimit) {
            $movement = $movementTotals->get($item->id);
            $inflow = (int) ($movement?->total_inflow ?? 0);
            $outflow = (int) ($movement?->total_outflow ?? 0);
            $expiryStatus = 'NORMAL';

            if ($item->expiry_date?->lt($today)) {
                $expiryStatus = 'EXPIRED';
            } elseif ($item->expiry_date && $item->expiry_date->lte($expiringLimit)) {
                $expiryStatus = 'EXPIRING SOON';
            }

            return [$item->name, $item->categoryLabel(), (int) $item->stock_balance, $inflow, $outflow, $inflow - $outflow, $expiryStatus];
        })->all();

        $movementChartRows = collect($rows)
            ->sortByDesc(fn (array $row) => $row[3] + $row[4])
            ->take(5)
            ->map(fn (array $row) => ['title' => $row[0], 'primary' => $row[3], 'secondary' => $row[4]])
            ->values()
            ->all();

        return [
            'title' => self::REPORTS['inventory_movement'],
            'columns' => ['Product Name', 'Category', 'Current Stock', 'Total Inflow', 'Total Outflow', 'Net Change', 'Expiry Status'],
            'rows' => $rows,
            'summaries' => [
                ['Total Inflow Quantity', array_sum(array_column($rows, 3))],
                ['Total Outflow Quantity', array_sum(array_column($rows, 4))],
                ['Items Currently Expired', $inventory->filter(fn (Inventory $item) => $item->expiry_date?->lt($today))->count()],
                ['Items Expiring Within 30 Days', $inventory->filter(fn (Inventory $item) => $item->expiry_date && $item->expiry_date->betweenIncluded($today, $expiringLimit))->count()],
            ],
            'charts' => [
                'bar_charts' => [[
                    'title' => 'INVENTORY INFLOW VS OUTFLOW',
                    'primary_label' => 'INFLOW',
                    'secondary_label' => 'OUTFLOW',
                    'rows' => $movementChartRows,
                    'note' => count($movementChartRows) === 5 ? 'Showing the five products with the most stock movement.' : null,
                ]],
            ],
        ];
    }

    private function downloadReport(array $report, string $rangeLabel, string $format)
    {
        $exportRows = [
            [$report['title']],
            ['Range: ' . $rangeLabel],
            ['Generated: ' . now()->format('M d, Y g:i A')],
            [],
            $report['columns'],
            ...$report['rows'],
            [],
            ['SUMMARY TOTALS'],
            ...array_map(fn (array $summary) => $summary, $report['summaries']),
        ];

        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writer = $format === 'csv' ? ExcelWriter::CSV : ExcelWriter::XLSX;
        $filename = str($report['title'])->slug() . '-' . now()->format('Ymd-His') . '.' . $extension;

        return Excel::download(new ReportTableExport($exportRows), $filename, $writer);
    }
}
