<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportService $reportService)
    {
    }

    public function index(Request $request)
    {
        [$from, $to, $search, $tab] = $this->parseFilters($request);
        $report = $this->reportService->buildReportData($from, $to, $search);

        return view('reports.index', array_merge($report, [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'search' => $search,
            'tab' => $tab,
        ]));
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to, $search, $tab] = $this->parseFilters($request);
        $report = $this->reportService->buildReportData($from, $to, $search);

        $filename = "gym-report-{$tab}-{$from->toDateString()}-to-{$to->toDateString()}.csv";

        return response()->streamDownload(function () use ($report, $tab) {
            $out = fopen('php://output', 'w');

            if ($tab === 'memberships') {
                fputcsv($out, ['ID', 'Member', 'Member ID', 'Plan', 'Price', 'Start Date', 'End Date', 'Status']);
                foreach ($report['subscriptions'] as $sub) {
                    fputcsv($out, [
                        $sub->id,
                        $sub->member?->name,
                        $sub->member?->membership_id,
                        $sub->effectivePlanName(),
                        number_format($sub->effectivePrice(), 2, '.', ''),
                        $sub->start_date->toDateString(),
                        $sub->end_date->toDateString(),
                        $sub->computedStatus(),
                    ]);
                }
            } elseif ($tab === 'members') {
                fputcsv($out, ['Member ID', 'Name', 'Email', 'Phone', 'Gender', 'Joining Date', 'Status']);
                foreach ($report['newMembers'] as $m) {
                    fputcsv($out, [
                        $m->membership_id,
                        $m->name,
                        $m->email,
                        $m->phone,
                        $m->gender,
                        $m->joining_date->toDateString(),
                        $m->status,
                    ]);
                }
            } elseif ($tab === 'attendance') {
                fputcsv($out, ['Member ID', 'Member', 'Check In', 'Check Out', 'Duration', 'Status']);
                foreach ($report['attendances'] as $att) {
                    fputcsv($out, [
                        $att->member?->membership_id,
                        $att->member?->name,
                        $att->check_in->toDateTimeString(),
                        $att->check_out?->toDateTimeString() ?? 'Active',
                        $att->durationFormatted(),
                        $att->status,
                    ]);
                }
            } else {
                fputcsv($out, ['Reference', 'Member', 'Member ID', 'Plan', 'Total Amount', 'Amount Paid', 'Remaining', 'Refunded', 'Method', 'Status', 'Date']);
                foreach ($report['payments'] as $p) {
                    fputcsv($out, [
                        $p->payment_reference ?? ('#' . $p->id),
                        $p->member?->name,
                        $p->member?->membership_id,
                        $p->subscription?->effectivePlanName() ?? 'General',
                        number_format((float) $p->total_amount, 2, '.', ''),
                        number_format((float) $p->amount_paid, 2, '.', ''),
                        number_format((float) $p->remaining_balance, 2, '.', ''),
                        number_format((float) $p->refunded_amount, 2, '.', ''),
                        $p->payment_method,
                        $p->status,
                        $p->payment_date->toDateString(),
                    ]);
                }
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request)
    {
        [$from, $to, $search, $tab] = $this->parseFilters($request);
        $report = $this->reportService->buildReportData($from, $to, $search);

        $pdf = Pdf::loadView('reports.pdf', array_merge($report, [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'search' => $search,
            'tab' => $tab,
        ]))->setPaper('a4', 'landscape');

        return $pdf->download("gym-report-{$from->toDateString()}-to-{$to->toDateString()}.pdf");
    }

    private function parseFilters(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->input('from'))
            : now()->copy()->startOfMonth();

        $to = $request->filled('to')
            ? Carbon::parse($request->input('to'))
            : now()->copy()->endOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $search = trim((string) $request->input('search')) ?: null;
        $tab = $request->input('tab', 'revenue');
        if (!in_array($tab, ['revenue', 'payments', 'memberships', 'members', 'attendance'], true)) {
            $tab = 'revenue';
        }

        return [$from, $to, $search, $tab];
    }
}
