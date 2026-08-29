<?php

namespace Modules\Payroll\Exports;

use App\SharedKernel\Models\PsipopOffice;
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
 * "General Payroll Register" sheet — one row per employee, grouped into
 * PSIPOP office sections (psipop_offices.sort_order), each followed by a
 * subtotal row, then a "TOTAL PER DIVISION" recap block and a grand total.
 *
 * Layout: No. | Plantilla Item No. | Name | Position | SG-Step |
 * Basic Salary | PERA | RATA | Gross Income | <dynamic deduction columns> |
 * Total Deductions | Net Amount | [1st Cutoff Net Pay] | [2nd Cutoff Net Pay]
 * — the two cutoff columns are included per the ?cutoff= filter: both shown
 * for 'both', only the matching one for '1st'/'2nd'.
 *
 * Header styling mirrors the DOLE sample workbook's actual technique: each
 * deduction column's header text gets its own distinct bold color (cycling
 * through the same bright palette the sample uses across its ~25 loan/
 * contribution columns), which is how that very wide sheet keeps adjacent
 * columns visually distinguishable. Identity/earnings columns and the
 * total/net columns get a plain, distinct treatment of their own instead,
 * matching how the sample also leaves ITS identity/earnings columns
 * (SEQ, NAME, DESIGNATION, RATE PER MONTH, PERA, TOTAL COMP.) uncolored.
 */
class GeneralPayrollRegisterSheet implements FromCollection, WithTitle, WithColumnWidths, WithEvents
{
    protected const FIXED_HEADERS = ['No.', 'Plantilla Item No.', 'Name', 'Position', 'SG-Step', 'Basic Salary', 'PERA', 'RATA', 'Gross Income'];

    /** First column (1-based) holding a numeric, summable value — Basic Salary. */
    protected const FIRST_NUMERIC_COL = 6;

    /** Same bright palette the DOLE sample cycles through across its HDMF/GSIS/loan columns. */
    protected const DOLE_PALETTE = ['FF0000', 'FF00FF', '339966', 'FF6600', '0066CC', '993300', '333399', '800000', '008000', '800080', '3366FF', 'FF99CC', '993366'];

    /**
     * @param array $groups [['office' => PsipopOffice|null, 'rows' => [['entry' => PayrollEntry, 'amounts' => array], ...]], ...]
     * @param array $deductionColumns [id => ['name' => ..., 'order' => ...]]
     */
    public function __construct(
        protected array $groups,
        protected array $deductionColumns,
        protected int $year,
        protected int $month,
        protected string $cutoff,
        protected bool $hasEstimatedSplit = false
    ) {}

    public function collection(): Collection
    {
        return collect([]);
    }

    public function title(): string
    {
        return 'General Payroll Register';
    }

    /** Which cutoff net-pay columns to show, in order, per the ?cutoff= filter. */
    protected function cutoffColumns(): array
    {
        return match ($this->cutoff) {
            '1st'   => ['first_cutoff_net' => '1st Cutoff Net Pay'],
            '2nd'   => ['second_cutoff_net' => '2nd Cutoff Net Pay'],
            default => ['first_cutoff_net' => '1st Cutoff Net Pay', 'second_cutoff_net' => '2nd Cutoff Net Pay'],
        };
    }

    protected function lastCol(): int
    {
        // FIXED_HEADERS + dynamic deduction columns + Total Deductions + Net Amount + cutoff column(s)
        return count(self::FIXED_HEADERS) + count($this->deductionColumns) + 2 + count($this->cutoffColumns());
    }

    public function columnWidths(): array
    {
        $widths = ['A' => 5, 'B' => 20, 'C' => 26, 'D' => 20, 'E' => 10, 'F' => 12, 'G' => 10, 'H' => 10, 'I' => 13];
        foreach (array_keys($this->deductionColumns) as $i => $typeId) {
            $col = Coordinate::stringFromColumnIndex(count(self::FIXED_HEADERS) + 1 + $i);
            $widths[$col] = 12;
        }
        $tailStart = count(self::FIXED_HEADERS) + 1 + count($this->deductionColumns);
        $widths[Coordinate::stringFromColumnIndex($tailStart)]     = 14; // Total Deductions
        $widths[Coordinate::stringFromColumnIndex($tailStart + 1)] = 14; // Net Amount
        foreach (array_keys($this->cutoffColumns()) as $i => $key) {
            $widths[Coordinate::stringFromColumnIndex($tailStart + 2 + $i)] = 15;
        }

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
        $monthName    = date('F', mktime(0, 0, 0, $this->month, 1));
        $cutoffCols   = $this->cutoffColumns();
        $cutoffText   = match ($this->cutoff) {
            '1st'   => '1st Cutoff Net Pay shown',
            '2nd'   => '2nd Cutoff Net Pay shown',
            default => 'Both Cutoffs Shown',
        };

        $lastCol       = $this->lastCol();
        $lastColLetter = Coordinate::stringFromColumnIndex($lastCol);
        $numFmt        = '#,##0.00';

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

        // ── Column headers (row 10) — DOLE-style color coding ───────────
        $headerRow = 10;

        // Identity columns (A–E): plain, dark navy fill, matching the
        // sample leaving its own SEQ/NAME/DESIGNATION columns uncolored.
        foreach (array_slice(self::FIXED_HEADERS, 0, 5) as $i => $label) {
            $col = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$col}{$headerRow}", $label);
            $sheet->getStyle("{$col}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
            ]);
        }
        // Earnings columns (F–I): plain, dark green fill.
        foreach (array_slice(self::FIXED_HEADERS, 5, 4) as $i => $label) {
            $col = Coordinate::stringFromColumnIndex(6 + $i);
            $sheet->setCellValue("{$col}{$headerRow}", $label);
            $sheet->getStyle("{$col}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '385723']],
            ]);
        }
        // Deduction columns: white fill, each header's TEXT in a distinct
        // color cycling through the DOLE palette — this is the sample's
        // actual technique for telling 20+ similar-looking columns apart.
        $c = count(self::FIXED_HEADERS) + 1;
        foreach (array_values($this->deductionColumns) as $i => $meta) {
            $col   = Coordinate::stringFromColumnIndex($c);
            $color = self::DOLE_PALETTE[$i % count(self::DOLE_PALETTE)];
            $sheet->setCellValue("{$col}{$headerRow}", $meta['name']);
            $sheet->getStyle("{$col}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9, 'color' => ['rgb' => $color]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            ]);
            $c++;
        }
        // Total Deductions — bold red fill, white text (draws the eye as a subtotal).
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $headerRow, 'Total Deductions');
        $sheet->getStyle(Coordinate::stringFromColumnIndex($c) . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C00000']],
        ]);
        $c++;
        // Net Amount — gold fill (the app's accent color), navy text.
        $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $headerRow, 'Net Amount');
        $sheet->getStyle(Coordinate::stringFromColumnIndex($c) . $headerRow)->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9, 'color' => ['rgb' => '1F3864']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
        ]);
        $c++;
        // 1st/2nd Cutoff Net Pay — lighter gold, same "net pay" family as Net Amount.
        foreach ($cutoffCols as $label) {
            $col = Coordinate::stringFromColumnIndex($c);
            $sheet->setCellValue("{$col}{$headerRow}", $label);
            $sheet->getStyle("{$col}{$headerRow}")->applyFromArray([
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9, 'color' => ['rgb' => '1F3864']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFE699']],
            ]);
            $c++;
        }

        $sheet->getStyle("A{$headerRow}:{$lastColLetter}{$headerRow}")->applyFromArray([
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(32);

        // ── Grouped data rows ────────────────────────────────────────────
        $row = $headerRow + 1;
        $no  = 1;
        /** @var array<int, array{name: string, row: int, employees: int}> $subtotals One per group, in order. */
        $subtotals = [];

        foreach ($this->groups as $group) {
            $officeName = $group['office']->name ?? PsipopOffice::NAME_UNASSIGNED;

            // Office section header row
            $sheet->mergeCells("A{$row}:{$lastColLetter}{$row}");
            $sheet->setCellValue("A{$row}", strtoupper($officeName));
            $sheet->getStyle("A{$row}")->applyFromArray([
                'font' => ['bold' => true, 'name' => 'Arial', 'size' => 9.5],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DCE6F1']],
            ]);
            $row++;

            $groupStartRow = $row;

            foreach ($group['rows'] as $item) {
                $entry  = $item['entry'];
                $amt    = $item['amounts'];
                $emp    = $entry->employee;
                $sgStep = trim(($emp->salary_grade ?? '') . '-' . ($emp->step ?? ''), '-');

                $sheet->setCellValue("A{$row}", $no);
                $sheet->setCellValue("B{$row}", $emp->plantilla_item_no ?: '—');
                $sheet->setCellValue("C{$row}", strtoupper($emp->full_name ?? trim($emp->last_name . ', ' . $emp->first_name)));
                $sheet->setCellValue("D{$row}", $emp->position_title ?? '');
                $sheet->setCellValue("E{$row}", $sgStep);
                $sheet->setCellValue("F{$row}", $amt['basic_salary']);
                $sheet->setCellValue("G{$row}", $amt['pera']);
                $sheet->setCellValue("H{$row}", $amt['rata']);
                $sheet->setCellValue("I{$row}", $amt['gross_income']);

                $c = count(self::FIXED_HEADERS) + 1;
                foreach (array_keys($this->deductionColumns) as $typeId) {
                    $col = Coordinate::stringFromColumnIndex($c);
                    $sheet->setCellValue("{$col}{$row}", $amt['dedByType'][$typeId] ?? 0);
                    $c++;
                }
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $row, $amt['total_deductions']);
                $c++;
                $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $row, $amt['net_amount']);
                $c++;
                foreach (array_keys($cutoffCols) as $key) {
                    $sheet->setCellValue(Coordinate::stringFromColumnIndex($c) . $row, $amt[$key]);
                    $c++;
                }

                $sheet->getStyle(Coordinate::stringFromColumnIndex(self::FIRST_NUMERIC_COL) . "{$row}:{$lastColLetter}{$row}")
                    ->getNumberFormat()->setFormatCode($numFmt);
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

            $groupEndRow = $row - 1;

            // Subtotal row — SUM only the numeric columns for THIS group's range.
            $sheet->mergeCells("A{$row}:E{$row}");
            $sheet->setCellValue("A{$row}", 'TOTAL — ' . strtoupper($officeName));
            for ($col = self::FIRST_NUMERIC_COL; $col <= $lastCol; $col++) {
                $colLetter = Coordinate::stringFromColumnIndex($col);
                $sheet->setCellValue("{$colLetter}{$row}", "=SUM({$colLetter}{$groupStartRow}:{$colLetter}{$groupEndRow})");
            }
            $sheet->getStyle("A{$row}:{$lastColLetter}{$row}")->applyFromArray([
                'font'    => ['bold' => true, 'name' => 'Arial', 'size' => 9],
                'fill'    => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EDEDED']],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getStyle(Coordinate::stringFromColumnIndex(self::FIRST_NUMERIC_COL) . "{$row}:{$lastColLetter}{$row}")
                ->getNumberFormat()->setFormatCode($numFmt);

            $subtotals[] = ['name' => $officeName, 'row' => $row, 'employees' => count($group['rows'])];
            $row++;
        }

        // ── "TOTAL PER DIVISION" recap block ─────────────────────────────
        $row++; // spacer
        $sheet->mergeCells("A{$row}:{$lastColLetter}{$row}");
        $sheet->setCellValue("A{$row}", 'TOTAL PER DIVISION');
        $sheet->getStyle("A{$row}")->applyFromArray([
            'font' => ['bold' => true, 'name' => 'Arial', 'size' => 11],
        ]);
        $row++;

        $recapHeaderRow = $row;
        $sheet->mergeCells("A{$recapHeaderRow}:B{$recapHeaderRow}");
        $sheet->setCellValue("A{$recapHeaderRow}", 'PSIPOP Office');
        $sheet->setCellValue("C{$recapHeaderRow}", 'Employees');
        $sheet->setCellValue("D{$recapHeaderRow}", 'Gross Income');
        $sheet->setCellValue("E{$recapHeaderRow}", 'Total Deductions');
        $sheet->setCellValue("F{$recapHeaderRow}", 'Net Amount');
        $sheet->getStyle("A{$recapHeaderRow}:F{$recapHeaderRow}")->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 9, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F3864']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ]);
        $row++;

        $recapStartRow      = $row;
        $grossColLetter     = Coordinate::stringFromColumnIndex(count(self::FIXED_HEADERS)); // Gross Income column in the data rows above
        $totalDedColIndex   = count(self::FIXED_HEADERS) + count($this->deductionColumns) + 1;
        $netColIndex        = $totalDedColIndex + 1;
        $totalDedColLetter  = Coordinate::stringFromColumnIndex($totalDedColIndex);
        $netColLetter       = Coordinate::stringFromColumnIndex($netColIndex);

        foreach ($subtotals as $sub) {
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue("A{$row}", $sub['name']);
            $sheet->setCellValue("C{$row}", $sub['employees']);
            $sheet->setCellValue("D{$row}", "={$grossColLetter}{$sub['row']}");
            $sheet->setCellValue("E{$row}", "={$totalDedColLetter}{$sub['row']}");
            $sheet->setCellValue("F{$row}", "={$netColLetter}{$sub['row']}");
            $sheet->getStyle("A{$row}:F{$row}")->applyFromArray([
                'font'    => ['name' => 'Arial', 'size' => 9],
                'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
            ]);
            $sheet->getStyle("D{$row}:F{$row}")->getNumberFormat()->setFormatCode($numFmt);
            $sheet->getStyle("C{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $row++;
        }
        $recapEndRow = $row - 1;

        // ── Grand total row (sums the recap block, not the raw data rows,
        //    so office header/subtotal rows are never double-counted) ────
        $grandRow = $row;
        $sheet->mergeCells("A{$grandRow}:B{$grandRow}");
        $sheet->setCellValue("A{$grandRow}", 'GRAND TOTAL');
        $sheet->setCellValue("C{$grandRow}", "=SUM(C{$recapStartRow}:C{$recapEndRow})");
        $sheet->setCellValue("D{$grandRow}", "=SUM(D{$recapStartRow}:D{$recapEndRow})");
        $sheet->setCellValue("E{$grandRow}", "=SUM(E{$recapStartRow}:E{$recapEndRow})");
        $sheet->setCellValue("F{$grandRow}", "=SUM(F{$recapStartRow}:F{$recapEndRow})");
        $sheet->getStyle("A{$grandRow}:F{$grandRow}")->applyFromArray([
            'font'      => ['bold' => true, 'name' => 'Arial', 'size' => 10, 'color' => ['rgb' => '1F3864']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFC000']],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_MEDIUM]],
        ]);
        $sheet->getStyle("D{$grandRow}:F{$grandRow}")->getNumberFormat()->setFormatCode($numFmt);
        $sheet->getStyle("C{$grandRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $footnoteRow = $grandRow;
        if ($this->hasEstimatedSplit) {
            $footnoteRow = $grandRow + 2;
            $sheet->mergeCells("A{$footnoteRow}:{$lastColLetter}{$footnoteRow}");
            $sheet->setCellValue("A{$footnoteRow}", '* One or more employees have no attendance snapshot on file for this period — their 1st/2nd Cutoff Net Pay above shows an even 50/50 split rather than the actual attendance-based computation.');
            $sheet->getStyle("A{$footnoteRow}")->applyFromArray([
                'font' => ['italic' => true, 'name' => 'Arial', 'size' => 8, 'color' => ['rgb' => '7F7F7F']],
            ]);
        }

        // ── Signature block ────────────────────────────────────────────
        $sigLabelRow = $footnoteRow + 3;
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
        $sheet->freezePane('A' . ($headerRow + 1));

        $sheet->getParent()->getDefaultStyle()->getFont()->setName('Arial')->setSize(9);
    }
}
