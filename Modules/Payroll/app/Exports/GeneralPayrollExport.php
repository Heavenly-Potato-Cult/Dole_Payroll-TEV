<?php

namespace Modules\Payroll\Exports;

use Modules\Payroll\Models\PayrollBatch;
use Modules\Payroll\Models\PayrollEntry;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;

/**
 * General Payroll Register (DOLE RO9 — "01A-General-Payroll-Monthly" equivalent).
 *
 * One row per employee: No. | Name | Position | SG-Step | Basic Salary | PERA |
 * RATA | Gross Income | <dynamic deduction columns> | Total Deductions | Net Amount.
 *
 * Deduction columns are discovered dynamically from whichever deduction_types
 * actually have amount > 0 in the filtered period, ordered by display_order —
 * this avoids hardcoding the ~40 legacy columns from the original manual
 * template and keeps the sheet in sync with whatever DeductionType CMS
 * currently has active.
 */
class GeneralPayrollExport implements FromCollection, WithTitle, WithColumnWidths, WithEvents
{
    protected int $year;
    protected int $month;
    protected string $cutoff; // '1st' | '2nd' | 'both'

    /** @var Collection<int, PayrollEntry> */
    protected Collection $entries;

    /** Deduction columns present in this period: [id => ['name' => ..., 'order' => ...]] */
    protected array $deductionColumns = [];

    protected float $totalBasic = 0;
    protected float $totalPera = 0;
    protected float $totalRata = 0;
    protected float $totalGross = 0;
    protected array $totalPerDeduction = [];
    protected float $totalDeductions = 0;
    protected float $totalNet = 0;

    public function __construct(int $year, int $month, string $cutoff = 'both')
    {
        $this->year   = $year;
        $this->month  = $month;
        $this->cutoff = $cutoff;

        $this->compute();
    }

    protected function compute(): void
    {
        $batchIds = PayrollBatch::query()
            ->whereYear('period_start', $this->year)
            ->whereMonth('period_start', $this->month)
            ->when($this->cutoff === '1st', fn ($q) => $q->where('cutoff', '1st'))
            ->when($this->cutoff === '2nd', fn ($q) => $q->where('cutoff', '2nd'))
            ->pluck('id');

        $this->entries = PayrollEntry::query()
            ->whereIn('payroll_batch_id', $batchIds)
            ->with([
                'employee',
                'deductions.deductionType',
                'allowances.allowanceType',
            ])
            ->get()
            ->sortBy(fn (PayrollEntry $e) => $e->employee->last_name . $e->employee->first_name)
            ->values();

        // Discover which deduction types actually appear (amount > 0) this period.
        foreach ($this->entries as $entry) {
            foreach ($entry->deductions as $ded) {
                if ((float) $ded->amount <= 0 || !$ded->deductionType) {
                    continue;
                }
                $typeId = $ded->deductionType->id;
                if (!isset($this->deductionColumns[$typeId])) {
                    $this->deductionColumns[$typeId] = [
                        'name'  => $ded->deductionType->name,
                        'order' => $ded->deductionType->display_order ?? 999,
                    ];
                }
            }
        }
        uasort($this->deductionColumns, fn ($a, $b) => $a['order'] <=> $b['order']);

        foreach (array_keys($this->deductionColumns) as $typeId) {
            $this->totalPerDeduction[$typeId] = 0.0;
        }

        foreach ($this->entries as $entry) {
            $this->totalBasic += (float) $entry->basic_salary;
            $this->totalPera  += $this->allowanceAmount($entry, 'PERA');
            $this->totalRata  += $this->allowanceAmount($entry, 'RATA');
            $this->totalGross += (float) $entry->gross_income;
            $this->totalDeductions += (float) $entry->total_deductions;
            $this->totalNet   += (float) $entry->net_amount;

            foreach ($entry->deductions as $ded) {
                if (!$ded->deductionType || !isset($this->totalPerDeduction[$ded->deductionType->id])) {
                    continue;
                }
                $this->totalPerDeduction[$ded->deductionType->id] += (float) $ded->amount;
            }
        }
    }

    /**
     * Resolve an allowance amount (PERA / RATA) for an entry.
     * Prefers the payroll_entry_allowances relation (joined via allowance_types.code);
     * falls back to a denormalized column on the entry if the relation yields nothing,
     * since some setups may keep PERA/RATA flattened onto payroll_entries directly.
     */
    protected function allowanceAmount(PayrollEntry $entry, string $code): float
    {
        $fromRelation = $entry->allowances
            ->filter(fn ($a) => $a->allowanceType && $a->allowanceType->code === $code)
            ->sum('amount');

        if ($fromRelation > 0) {
            return (float) $fromRelation;
        }

        $column = strtolower($code); // 'pera' / 'rata'
        return (float) ($entry->{$column} ?? 0);
    }

    public function collection(): Collection
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'General Payroll Register';
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 5, 'B' => 26, 'C' => 20, 'D' => 10, 'E' => 13, 'F' => 10, 'G' => 10, 'H' => 13];
        foreach (array_keys($this->deductionColumns) as $i => $typeId) {
            $col = Coordinate::stringFromColumnIndex(9 + $i);
            $widths[$col] = 12;
        }
        $tailStart = 9 + count($this->deductionColumns);
        $widths[Coordinate::stringFromColumnIndex($tailStart)]     = 14; // Total Deductions
        $widths[Coordinate::stringFromColumnIndex($tailStart + 1)] = 14; // Net Amount

        return $widths;
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
        $monthName  = date('F', mktime(0, 0, 0, $this->month, 1));
        $cutoffText = match ($this->cutoff) {
            '1st' => '1st Cut-off (1–15)',
            '2nd' => '2nd Cut-off (16–31)',
            default => 'Full Month',
        };

        $deductionTypeIds = array_keys($this->deductionColumns);
        $lastCol   = 8 + count($deductionTypeIds) + 2; // + Total Deductions + Net Amount
        $lastColLetter = Coordinate::stringFromColumnIndex($lastCol);

        // ── Row heights ────────────────────────────────────────────────
        $sheet->getRowDimension(1)->setRowHeight(32);
        foreach (range(2, 6) as $r) $sheet->getRowDimension($r)->setRowHeight(15);

        // ── Logos ──────────────────────────────────────────────────────
        $logoLeft = new Drawing();
        $logoLeft->setName('DOLE Logo');
        $logoLeft->setDescription('DOLE Logo');
        $logoLeft->setPath(public_path('assets/img/dole_logo.png'));
        $logoLeft->setHeight(60);
        $logoLeft->setCoordinates('A1');
        $logoLeft->setOffsetX(2);
        $logoLeft->setOffsetY(2);
        $logoLeft->setWorksheet($sheet);

        $logoRight = new Drawing();
        $logoRight->setName('Bagong Pilipinas');
        $logoRight->setDescription('Bagong Pilipinas Logo');
        $logoRight->setPath(public_path('assets/img/bagong_pilipinas_logo.png'));
        $logoRight->setHeight(60);
        $logoRight->setCoordinates($lastColLetter . '1');
        $logoRight->setOffsetX(2);
        $logoRight->setOffsetY(2);
        $logoRight->setWorksheet($sheet);

        // ── Agency header (rows 1-5) ───────────────────────────────────
        $headerLines = [
            1 => ['Republic of the Philippines', false, 11],
            2 => ['DEPARTMENT OF LABOR AND EMPLOYMENT', true, 13],
            3 => ['Regional Office No. IX', false, 11],
            4 => ['Cortez Building, Dr. Evangelista Street', false, 10],
            5 => ['Barangay Sta. Catalina, Zamboanga City', false, 10],
        ];
        foreach ($headerLines as $r => [$text, $bold, $sz]) {
            $sheet->mergeCells("B{$r}:" . Coordinate::stringFromColumnIndex($lastCol - 1) . $r);
            $sheet->setCellValue("B{$r}", $text);
            $sheet->getStyle("B{$r}")->applyFromArray([
                'font'      => ['bold' => $bold, 'name' => 'Arial', 'size' => $sz],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
        }

        // ── Title (row 7-8) ────────────────────────────────────────────
        $sheet->mergeCells("A7:{$lastColLetter}7");
        $sheet->setCellValue('A7', 'GENERAL PAYROLL REGISTER');
        $sheet->getStyle('A7')->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 13],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->mergeCells("A8:{$lastColLetter}8");
        $sheet->setCellValue('A8', strtoupper($monthName) . ' ' . $this->year . ' — ' . $cutoffText);
        $sheet->getStyle('A8')->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 11],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
        $sheet->getRowDimension(7)->setRowHeight(20);
        $sheet->getRowDimension(8)->setRowHeight(18);

        // ── Column headers (row 10) ────────────────────────────────────
        $headerRow = 10;
        $fixedHeaders = ['No.', 'Name', 'Position', 'SG-Step', 'Basic Salary', 'PERA', 'RATA', 'Gross Income'];
        foreach ($fixedHeaders as $i => $label) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$headerRow}", $label);
        }
        $c = count($fixedHeaders) + 1;
        foreach ($this->deductionColumns as $meta) {
            $col = Coordinate::stringFromColumnIndex($c);
            $sheet->setCellValue("{$col}{$headerRow}", $meta['name']);
            $c++;
        }
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $headerRow, 'Total Deductions');
        $c++;
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $headerRow, 'Net Amount');

        $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(32);

        // ── Data rows ──────────────────────────────────────────────────
        $numFmt = '#,##0.00';
        $row = $headerRow + 1;
        $no  = 1;
        foreach ($this->entries as $entry) {
            $emp = $entry->employee;
            $sgStep = trim(($emp->salary_grade ?? '') . '-' . ($emp->step ?? ''), '-');

            $sheet->setCellValue("A{$row}", $no);
            $sheet->setCellValue("B{$row}", strtoupper($emp->full_name ?? trim($emp->last_name . ', ' . $emp->first_name)));
            $sheet->setCellValue("C{$row}", $emp->position_title ?? '');
            $sheet->setCellValue("D{$row}", $sgStep);
            $sheet->setCellValue("E{$row}", (float) $entry->basic_salary);
            $sheet->setCellValue("F{$row}", $this->allowanceAmount($entry, 'PERA'));
            $sheet->setCellValue("G{$row}", $this->allowanceAmount($entry, 'RATA'));
            $sheet->setCellValue("H{$row}", (float) $entry->gross_income);

            $dedByType = [];
            foreach ($entry->deductions as $ded) {
                if ($ded->deductionType) {
                    $dedByType[$ded->deductionType->id] = ($dedByType[$ded->deductionType->id] ?? 0) + (float) $ded->amount;
                }
            }

            $c = count($fixedHeaders) + 1;
            foreach (array_keys($this->deductionColumns) as $typeId) {
                $col = Coordinate::stringFromColumnIndex($c);
                $sheet->setCellValue("{$col}{$row}", $dedByType[$typeId] ?? 0);
                $c++;
            }
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $row, (float) $entry->total_deductions);
            $c++;
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $row, (float) $entry->net_amount);

            // Numeric formatting E..lastCol
            $sheet->getStyle("E{$row}:{$lastColLetter}{$row}")->getNumberFormat()->setFormatCode($numFmt);
            $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->applyFromArray([
                'font'    => ['name' => 'Arial', 'size' => 9],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            if ($no % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F2F2F2');
            }

            $row++;
            $no++;
        }
        $lastDataRow = $row - 1;

        // ── Grand total row ────────────────────────────────────────────
        $totalRow = $row;
        $sheet->mergeCells("A{$totalRow}:D{$totalRow}");
        $sheet->setCellValue("A{$totalRow}", 'GRAND TOTAL');
        $sheet->setCellValue("E{$totalRow}", "=SUM(E" . ($headerRow + 1) . ":E{$lastDataRow})");
        $sheet->setCellValue("F{$totalRow}", "=SUM(F" . ($headerRow + 1) . ":F{$lastDataRow})");
        $sheet->setCellValue("G{$totalRow}", "=SUM(G" . ($headerRow + 1) . ":G{$lastDataRow})");
        $sheet->setCellValue("H{$totalRow}", "=SUM(H" . ($headerRow + 1) . ":H{$lastDataRow})");

        $c = count($fixedHeaders) + 1;
        foreach (array_keys($this->deductionColumns) as $typeId) {
            $col = Coordinate::stringFromColumnIndex($c);
            $sheet->setCellValue("{$col}{$totalRow}", "=SUM({$col}" . ($headerRow + 1) . ":{$col}{$lastDataRow})");
            $c++;
        }
        $totalDedCol = Coordinate::stringFromColumnIndex($c);
        $sheet->setCellValue("{$totalDedCol}{$totalRow}", "=SUM({$totalDedCol}" . ($headerRow + 1) . ":{$totalDedCol}{$lastDataRow})");
        $c++;
        $netCol = Coordinate::stringFromColumnIndex($c);
        $sheet->setCellValue("{$netCol}{$totalRow}", "=SUM({$netCol}" . ($headerRow + 1) . ":{$netCol}{$lastDataRow})");

        $sheet->getStyle("A{$totalRow}:{$lastColLetter}{$totalRow}")->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 9],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("E{$totalRow}:{$lastColLetter}{$totalRow}")->getNumberFormat()->setFormatCode($numFmt);
        $sheet->getStyle("A{$totalRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // ── Signature block ────────────────────────────────────────────
        $sigLabelRow = $totalRow + 3;
        $sigLineRow  = $sigLabelRow + 4;

        $sigCols = [
            'A' => 'Prepared by:',
            (Coordinate::stringFromColumnIndex((int) ceil($lastCol / 3) + 1)) => 'Certified by:',
            (Coordinate::stringFromColumnIndex((int) ceil($lastCol * 2 / 3) + 1)) => 'Approved by:',
        ];
        foreach ($sigCols as $col => $label) {
            $sheet->setCellValue("{$col}{$sigLabelRow}", $label);
            $sheet->getStyle("{$col}{$sigLabelRow}")->getFont()->setName('Arial')->setSize(10);

            $endCol = Coordinate::stringFromColumnIndex(Coordinate::columnIndexFromString($col) + 3);
            $sheet->getStyle("{$col}{$sigLineRow}:{$endCol}{$sigLineRow}")
                ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN);

            $sheet->setCellValue("{$col}" . ($sigLineRow + 1), 'Signature over Printed Name');
            $sheet->getStyle("{$col}" . ($sigLineRow + 1))->getFont()->setName('Arial')->setSize(9)->setItalic(true);
        }

        // ── Page setup ─────────────────────────────────────────────────
        $sheet->getPageSetup()
            ->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)
            ->setPaperSize(PageSetup::PAPERSIZE_LEGAL)
            ->setFitToPage(true)
            ->setFitToWidth(1)
            ->setFitToHeight(0);
        $sheet->getPageMargins()->setTop(0.4)->setBottom(0.4)->setLeft(0.4)->setRight(0.4);
        $sheet->freezePane("A" . ($headerRow + 1));

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
    }

    public function getEmployeeCount(): int
    {
        return $this->entries->count();
    }

    public function getGrandTotalNet(): float
    {
        return $this->totalNet;
    }

    public function getGrandTotalGross(): float
    {
        return $this->totalGross;
    }

    public function getDeductionColumns(): array
    {
        return $this->deductionColumns;
    }
}
