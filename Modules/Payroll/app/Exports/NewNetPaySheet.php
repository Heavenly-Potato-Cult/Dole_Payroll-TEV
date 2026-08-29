<?php

namespace Modules\Payroll\Exports;

use Modules\Payroll\Models\AttendanceSnapshot;
use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\PayrollEntry;
use Modules\Payroll\Services\PayrollComputationService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * "New Net Pay" sheet — mirrors the sample workbook's sheet of the same
 * name: Name | Position | Plantilla Item No. | 1st-cutoff net | 2nd-cutoff
 * net | Total | Difference.
 *
 * Always shows BOTH cutoffs for the full month, regardless of the
 * ?cutoff= filter applied to the main register sheet — the sample sheet
 * has no cutoff selector of its own, since its whole purpose IS the
 * cutoff comparison.
 *
 * Net pay per cutoff comes straight from
 * PayrollComputationService::computeCutoffSplit() (attendance-day ratio,
 * or the employee's salary_split_override_pct when set) — the real
 * per-employee computation, not the proportional approximation used for
 * the register sheet's dynamic deduction columns.
 */
class NewNetPaySheet implements FromCollection, WithTitle, WithColumnWidths, WithEvents
{
    /** @var array<int, array{name: string, position: string, plantilla: string, first: float, second: float, estimated: bool}> */
    protected array $rows = [];

    public function __construct(protected int $year, protected int $month)
    {
        $this->compute();
    }

    protected function compute(): void
    {
        $batchIds = PayrollBatch::query()
            ->where('period_year', $this->year)
            ->where('period_month', $this->month)
            ->pluck('id');

        $entries = PayrollEntry::query()
            ->whereIn('payroll_batch_id', $batchIds)
            ->with('employee')
            ->get()
            ->sortBy(fn (PayrollEntry $e) => $e->employee->last_name . $e->employee->first_name)
            ->values();

        $service = app(PayrollComputationService::class);

        foreach ($entries as $entry) {
            $emp = $entry->employee;

            $snapshot = AttendanceSnapshot::query()
                ->where('payroll_batch_id', $entry->payroll_batch_id)
                ->where('employee_id', $entry->employee_id)
                ->first();

            if ($snapshot) {
                $split      = $service->computeCutoffSplit($entry, $snapshot);
                $firstNet   = $split['first_cutoff']['net_amount'];
                $secondNet  = $split['second_cutoff']['net_amount'];
                $estimated  = false;
            } else {
                // No attendance snapshot — can't compute a real split.
                // Show an even 50/50 share rather than fail the export,
                // flagged in the Remarks column.
                $firstNet  = round((float) $entry->net_amount / 2, 2);
                $secondNet = round((float) $entry->net_amount - $firstNet, 2);
                $estimated = true;
            }

            $this->rows[] = [
                'name'      => strtoupper($emp->full_name ?? trim($emp->last_name . ', ' . $emp->first_name)),
                'position'  => $emp->position_title ?? '',
                'plantilla' => $emp->plantilla_item_no ?: '—',
                'first'     => $firstNet,
                'second'    => $secondNet,
                'estimated' => $estimated,
            ];
        }
    }

    public function collection(): Collection
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'New Net Pay';
    }

    public function columnWidths(): array
    {
        return [
            'A' => 26, // Name
            'B' => 22, // Position
            'C' => 22, // Plantilla Item No.
            'D' => 14, // 1st cutoff net
            'E' => 14, // 2nd cutoff net
            'F' => 14, // Total
            'G' => 12, // Difference
            'H' => 22, // Remarks
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $this->buildSheet($event->sheet->getDelegate());
            },
        ];
    }

    private function buildSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $monthName = date('F', mktime(0, 0, 0, $this->month, 1));
        $numFmt    = '#,##0.00';

        $sheet->mergeCells('A1:H1');
        $sheet->setCellValue('A1', 'NET TAKE HOME PAY');
        $sheet->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $daysInMonth = (int) date('t', mktime(0, 0, 0, $this->month, 1, $this->year));
        $sheet->mergeCells('A2:H2');
        $sheet->setCellValue('A2', $monthName . ' 1–' . $daysInMonth . ', ' . $this->year);
        $sheet->getStyle('A2')->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 10],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);

        $headerRow = 4;
        $headers   = ['NAME', 'POSITION', 'PLANTILLA ITEM NO.', '1-15', '16-31', 'TOTAL', 'DIFFERENCE', 'REMARKS'];
        foreach ($headers as $i => $label) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$headerRow}", $label);
        }
        $sheet->getStyle("A{$headerRow}:H{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(24);

        $row = $headerRow + 1;
        foreach ($this->rows as $r) {
            $sheet->setCellValue("A{$row}", $r['name']);
            $sheet->setCellValue("B{$row}", $r['position']);
            $sheet->setCellValue("C{$row}", $r['plantilla']);
            $sheet->setCellValue("D{$row}", $r['first']);
            $sheet->setCellValue("E{$row}", $r['second']);
            $sheet->setCellValue("F{$row}", "=D{$row}+E{$row}");
            $sheet->setCellValue("G{$row}", "=ABS(D{$row}-E{$row})");
            $sheet->setCellValue("H{$row}", $r['estimated'] ? 'Estimated — no attendance snapshot' : '');

            $sheet->getStyle("D{$row}:G{$row}")->getNumberFormat()->setFormatCode($numFmt);
            $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
                'font'    => ['name' => 'Arial', 'size' => 9],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            if ($r['estimated']) {
                $sheet->getStyle("H{$row}")->getFont()->setItalic(true)->setColor(new \PhpOffice\PhpSpreadsheet\Style\Color('FFB45309'));
            }
            $row++;
        }
        $lastDataRow = $row - 1;

        // ── Total row ─────────────────────────────────────────────────
        $sheet->mergeCells("A{$row}:C{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL');
        $sheet->setCellValue("D{$row}", "=SUM(D" . ($headerRow + 1) . ":D{$lastDataRow})");
        $sheet->setCellValue("E{$row}", "=SUM(E" . ($headerRow + 1) . ":E{$lastDataRow})");
        $sheet->setCellValue("F{$row}", "=SUM(F" . ($headerRow + 1) . ":F{$lastDataRow})");
        $sheet->getStyle("A{$row}:H{$row}")->applyFromArray([
            'font'    => ['bold' => true, 'name' => 'Arial', 'size' => 9],
            'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $sheet->getStyle("D{$row}:F{$row}")->getNumberFormat()->setFormatCode($numFmt);

        $sheet->freezePane('A' . ($headerRow + 1));
        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
    }
}
