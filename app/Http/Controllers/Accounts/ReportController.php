<?php

declare(strict_types=1);

namespace App\Http\Controllers\Accounts;

use App\Http\Controllers\Controller;
use App\Models\Accounts\Expense;
use App\Models\Accounts\Payment;
use App\Services\Accounts\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,yearly,custom'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $period = $this->reports->resolvePeriod(
            $validated['period'] ?? 'monthly',
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        return view('accounts.reports.index', [
            'period' => $period,
            'preset' => $validated['period'] ?? 'monthly',
            'report' => $this->reports->build($period['from'], $period['to']),
            'serviceRevenue' => $this->reports->serviceRevenue(),
            'methods' => $this->reports->methodOptions(),
            'categories' => $this->reports->categoryOptions(),
        ]);
    }

    public function export(Request $request, string $type): StreamedResponse|\Symfony\Component\HttpFoundation\Response
    {
        $validated = $request->validate([
            'period' => ['nullable', 'in:daily,weekly,monthly,yearly,custom'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $period = $this->reports->resolvePeriod($validated['period'] ?? 'monthly', $validated['from'] ?? null, $validated['to'] ?? null);
        $report = $this->reports->build($period['from'], $period['to']);
        $summary = $report['summary'];

        return match ($type) {
            'csv' => $this->exportCsv($report, $period),
            'excel' => $this->exportExcel($report, $period),
            'pdf' => $this->exportPdf($report, $period),
            default => abort(404),
        };
    }

    private function exportCsv(array $report, array $period): StreamedResponse
    {
        $summary = $report['summary'];

        return response()->streamDownload(function () use ($report, $summary, $period) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Accounts Report', $period['label'] . " ({$period['from']} to {$period['to']})"]);
            fputcsv($handle, []);
            fputcsv($handle, ['Total Income (BDT)', number_format($summary->income, 2, '.', '')]);
            fputcsv($handle, ['Total Expense (BDT)', number_format($summary->expense, 2, '.', '')]);
            fputcsv($handle, ['Net Profit/Loss (BDT)', number_format($summary->netProfit, 2, '.', '')]);
            fputcsv($handle, ['Receivable Due (BDT)', number_format($summary->receivableDue, 2, '.', '')]);
            fputcsv($handle, ['Payable (BDT)', number_format($summary->payable, 2, '.', '')]);
            fputcsv($handle, []);
            fputcsv($handle, ['Income by Service']);
            foreach ($summary->incomeByService as $service => $total) {
                fputcsv($handle, [$service, number_format((float) $total, 2, '.', '')]);
            }
            fputcsv($handle, []);
            fputcsv($handle, ['Expense by Category']);
            foreach ($summary->expenseByCategory as $category => $total) {
                fputcsv($handle, [$category, number_format((float) $total, 2, '.', '')]);
            }
            fclose($handle);
        }, 'accounts-report-' . $period['from'] . '-to-' . $period['to'] . '.csv', ['Content-Type' => 'text/csv']);
    }

    private function exportExcel(array $report, array $period): StreamedResponse
    {
        $summary = $report['summary'];
        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->setTitle('Accounts Report');

        $row = 1;
        $sheet->setCellValue([1, $row], 'Accounts Report');
        $sheet->setCellValue([2, $row], $period['label'] . " ({$period['from']} to {$period['to']})");
        $row += 2;
        foreach (['Total Income' => $summary->income, 'Total Expense' => $summary->expense, 'Net Profit/Loss' => $summary->netProfit, 'Receivable Due' => $summary->receivableDue, 'Payable' => $summary->payable] as $label => $value) {
            $sheet->setCellValue([1, $row], $label . ' (BDT)');
            $sheet->setCellValue([2, $row], round($value, 2));
            $row++;
        }
        $row++;
        $sheet->setCellValue([1, $row++], 'Income by Service');
        foreach ($summary->incomeByService as $service => $total) {
            $sheet->setCellValue([1, $row], $service);
            $sheet->setCellValue([2, $row], round((float) $total, 2));
            $row++;
        }
        $row++;
        $sheet->setCellValue([1, $row++], 'Expense by Category');
        foreach ($summary->expenseByCategory as $category => $total) {
            $sheet->setCellValue([1, $row], $category);
            $sheet->setCellValue([2, $row], round((float) $total, 2));
            $row++;
        }
        foreach ([1, 2] as $col) {
            $sheet->getColumnDimensionByColumn($col)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($sheet) {
            (new Xlsx($sheet->getParent()))->save('php://output');
        }, 'accounts-report-' . $period['from'] . '-to-' . $period['to'] . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function exportPdf(array $report, array $period): \Symfony\Component\HttpFoundation\Response
    {
        $pdf = Pdf::loadView('accounts.reports.pdf', ['report' => $report, 'period' => $period])
            ->setPaper('a4', 'landscape');

        return $pdf->download('accounts-report-' . $period['from'] . '-to-' . $period['to'] . '.pdf');
    }
}
