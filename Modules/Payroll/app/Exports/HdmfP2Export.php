<?php

namespace Modules\Payroll\Exports;

use Modules\Payroll\Models\PayrollEntry;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;

class HdmfP2Export implements FromCollection, WithTitle, WithEvents
{
    private const EMPLOYER_ID   = '206587760004';
    private const EMPLOYER_NAME = 'DEPARTMENT OF LABOR & EMPLOYMENT - Regional Office IX';
    private const ADDRESS       = 'Sta. Catalina ZC';
    private const PROGRAM_CODE  = 'M2-Modified Pag-IBIG 2';
    private const CODE = 'HDMF_P2';
    private const LAST_COL      = 'K';

    private int    $year;
    private int    $month;
    private string $cutoff;
    private array  $rows = [];

    public function __construct(int $year, int $month, string $cutoff = 'both')
    {
        $this->year   = $year;
        $this->month  = $month;
        $this->cutoff = $cutoff;
        $this->compute();
    }

    private function compute(): void
    {
        $entries = PayrollEntry::query()
            ->with(['employee', 'deductions' => fn($q) => $q->where('code', self::CODE)])
            ->whereHas('batch', function ($q) {
                $q->whereYear('period_start', $this->year)
                  ->whereMonth('period_start', $this->month);
                if ($this->cutoff === '1st') $q->where('cutoff', '1st');
                elseif ($this->cutoff === '2nd') $q->where('cutoff', '2nd');
            })
            ->whereHas('deductions', fn($q) => $q->where('code', self::CODE)->where('amount', '>', 0))
            ->get();

        foreach ($entries as $entry) {
            $ded = $entry->deductions->first();
            $emp = $entry->employee;
            $percov = sprintf('%04d%02d', $this->year, $this->month);
            $this->rows[] = [
                $emp->pagibig_mid_no  ?? $emp->pagibig_no ?? '',   // col A
                $emp->mp2_account_no  ?? '',   // col B
                // col C - MEMBERSHIP PROGRAM filled directly
                $emp->last_name       ?? '',   // col D
                $emp->first_name      ?? '',   // col E
                $emp->name_extension  ?? '',   // col F
                $emp->middle_name     ?? '',   // col G
                $percov,                        // col H - PERCOV (YYYYMM)
                round((float)($ded->amount ?? 0), 2), // col I - EE SHARE
                0,                              // col J - ER SHARE
                '',                             // col K - REMARKS
            ];
        }
    }

    public function collection(): Collection { return collect([]); }

    public function title(): string { return 'HDMF P2'; }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => fn(AfterSheet $e) => $this->buildSheet($e->sheet->getDelegate()),
        ];
    }

    private function thinBorder(): array
    {
        return [
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
        ];
    }

    private function buildSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $ws): void
    {
        // ── Rows 1-3: Employer info ──────────────────────────────────────
        $ws->setCellValue('A1', 'Employer ID');
        $ws->getCell('B1')->setValueExplicit(self::EMPLOYER_ID, DataType::TYPE_STRING);
        $ws->getStyle('B1')->getFont()->setBold(true);
        $ws->setCellValue('A2', 'Employer Name');
        $ws->setCellValue('B2', self::EMPLOYER_NAME);
        $ws->setCellValue('A3', 'Address');
        $ws->setCellValue('B3', self::ADDRESS);
        // Row 4 intentionally blank

        // ── Row 5: Column headers ─────────────────────────────────────────
        $headers = [
            'A5' => "Pag-IBIG\nMID NO.",
            'B5' => "MP2\nACCOUNT NO.",
            'C5' => "MEMBERSHIP\nPROGRAM",
            'D5' => "LAST\nNAME",
            'E5' => "FIRST\nNAME",
            'F5' => "NAME\nEXTENSION",
            'G5' => "MIDDLE\nNAME",
            'H5' => 'PERCOV',
            'I5' => "EE\nSHARE",
            'J5' => "ER\nSHARE",
            'K5' => 'REMARKS',
        ];
        foreach ($headers as $coord => $label) {
            $ws->setCellValue($coord, $label);
        }
        $ws->getStyle('A5:' . self::LAST_COL . '5')->applyFromArray([
            'font'      => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical'   => Alignment::VERTICAL_CENTER,
                            'wrapText'   => true],
        ]);
        $ws->getStyle('I5:J5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        $ws->getStyle('A5:' . self::LAST_COL . '5')->applyFromArray($this->thinBorder());
        $ws->getRowDimension(5)->setRowHeight(39.95);

        // ── Data rows starting at row 6 ───────────────────────────────────
        $r = 6;
        foreach ($this->rows as $row) {
            $ws->setCellValueByColumnAndRow(1,  $r, $row[0]);
            $ws->setCellValueByColumnAndRow(2,  $r, $row[1]);
            $ws->setCellValueByColumnAndRow(3,  $r, self::PROGRAM_CODE);
            $ws->setCellValueByColumnAndRow(4,  $r, $row[2]);
            $ws->setCellValueByColumnAndRow(5,  $r, $row[3]);
            $ws->setCellValueByColumnAndRow(6,  $r, $row[4]);
            $ws->setCellValueByColumnAndRow(7,  $r, $row[5]);
            $ws->setCellValueByColumnAndRow(8,  $r, $row[6]);
            $ws->setCellValueByColumnAndRow(9,  $r, $row[7]);
            $ws->setCellValueByColumnAndRow(10, $r, $row[8]);
            $ws->setCellValueByColumnAndRow(11, $r, $row[9]);
            $ws->getStyleByColumnAndRow(9,  $r)->getNumberFormat()->setFormatCode('#,##0.00');
            $ws->getStyleByColumnAndRow(10, $r)->getNumberFormat()->setFormatCode('#,##0.00');
            $ws->getStyle('A' . $r . ':' . self::LAST_COL . $r)->applyFromArray($this->thinBorder());
            $ws->getRowDimension($r)->setRowHeight(23.1);
            $r++;
        }

        // ── Total row ─────────────────────────────────────────────────────
        $dataStart = 6;
        $dataEnd   = $r - 1;
        $ws->setCellValue("A{$r}", 'TOTAL REMITTANCE');
        $ws->getStyle("A{$r}")->getFont()->setBold(true);
        if ($dataEnd >= $dataStart) {
            $ws->setCellValue("I{$r}", "=SUM(I{$dataStart}:I{$dataEnd})");
            $ws->setCellValue("J{$r}", "=SUM(J{$dataStart}:J{$dataEnd})");
        } else {
            $ws->setCellValue("I{$r}", 0);
            $ws->setCellValue("J{$r}", 0);
        }
        $ws->getStyle("I{$r}:J{$r}")->getFont()->setBold(true);
        $ws->getStyle("I{$r}:J{$r}")->getNumberFormat()->setFormatCode('#,##0.00');
        $ws->getRowDimension($r)->setRowHeight(23.1);
        $r += 2;

        // ── Signature block ───────────────────────────────────────────────
        $ws->setCellValue("A{$r}", 'Prepared by:');
        $ws->setCellValue("F{$r}", 'Certified By:');
        $r += 2;
        $ws->setCellValue("A{$r}", 'NAME');
        $ws->setCellValue("F{$r}", 'NAME');
        $r++;
        $ws->setCellValue("A{$r}", 'HRMO / HRMO Designate');
        $ws->setCellValue("F{$r}", 'IMSD Head');
        $r++;
        $ws->setCellValue("F{$r}", 'Internal Management Service Division');

        // ── Column widths (from DOLE template) ────────────────────────────
        foreach (['A' => 18.71, 'B' => 15.71, 'C' => 22.71, 'D' => 20.71, 'E' => 21.71,
                  'F' => 12.71, 'G' => 14.71, 'H' => 10.71, 'I' => 11.71, 'J' => 9.71,
                  'K' => 11.71] as $col => $w) {
            $ws->getColumnDimension($col)->setWidth($w);
        }
    }

    public function getRows(): array  { return $this->rows; }
    public function getTotal(): float { return array_sum(array_column($this->rows, 7)); }
    public function getCount(): int   { return count($this->rows); }
}
