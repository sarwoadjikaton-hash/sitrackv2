<?php

namespace App\Exports;

use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use Illuminate\Support\Collection as SupportCollection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class DataSuratExport implements
    FromCollection,
    WithHeadings,
    WithMapping,
    WithEvents,
    WithColumnWidths,
    WithTitle
{
    protected string $lastColumn;
    protected int $rowCounter = 0;

    /**
     * @param SupportCollection<int, LetterNumberType> $types Semua type yang tergabung di workbook ini
     */
    public function __construct(
        protected string $workbookName,
        protected SupportCollection $types,
        protected int $year,
        protected string $search
    ) {
        $this->lastColumn = Coordinate::stringFromColumnIndex(count($this->headings()));
    }

    public function title(): string
    {
        $safeTitle = preg_replace('/[:\\\\\/\?\*\[\]]/', '-', $this->workbookName);
        return mb_substr($safeTitle, 0, 31);
    }

    public function collection()
    {
        $typeIds = $this->types->pluck('id')->all();

        $query = LetterNumber::with(['type', 'unit'])
            ->whereIn('type_id', $typeIds)
            ->where('number_year', $this->year)
            ->where('status', 'used');

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('number_text', 'ILIKE', "%{$this->search}%")
                    ->orWhere('subject', 'ILIKE', "%{$this->search}%")
                    ->orWhere('processing_unit_text', 'ILIKE', "%{$this->search}%")
                    ->orWhere('signatory', 'ILIKE', "%{$this->search}%")
                    ->orWhere('destination', 'ILIKE', "%{$this->search}%");
            });
        }

        return $query->orderBy('sequence_number', 'asc')->get();
    }

    public function headings(): array
    {
        return [
            'No Urut',
            'Tanggal Masuk',
            'Unit Pengolah Arsip',
            'Penandatangan Surat',
            'Permohonan',
            'Tujuan Surat',
            'Tanggal Surat',
            'Keamanan Akses',
            'Nomor Urut',
            'Kode Klas. Arsip',
            'Bulan',
            'Nomor Surat',
            'Perihal Surat',
            'Petugas Unit Teknis',
        ];
    }

    public function map($record): array
    {
        $this->rowCounter++;
        $padding = $record->type?->sequence_padding ?? 4;

        return [
            $this->rowCounter,
            $record->incoming_date ? \Carbon\Carbon::parse($record->incoming_date)->format('d/m/Y') : '',
            $record->processing_unit_text,
            $record->signatory,
            $record->request_type,
            $record->destination,
            $record->letter_date ? \Carbon\Carbon::parse($record->letter_date)->format('d M Y') : '',
            $record->security_access,
            str_pad((string) $record->sequence_number, $padding, '0', STR_PAD_LEFT),
            $record->classification_code,
            $record->month_number,
            $record->number_text,
            $record->subject,
            $record->technical_officer,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 8,
            'B' => 14,
            'C' => 20,
            'D' => 18,
            'E' => 16,
            'F' => 20,
            'G' => 14,
            'H' => 14,
            'I' => 10,
            'J' => 16,
            'K' => 8,
            'L' => 22,
            'M' => 42,
            'N' => 18,
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $lastCol = $this->lastColumn;

                $sheet->insertNewRowBefore(1, 4);

                $sheet->mergeCells("A1:{$lastCol}1");
                $sheet->setCellValue('A1', 'REKAP NOMOR SURAT KELUAR (' . $this->workbookName . ')');
                $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
                $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $sheet->mergeCells("A3:{$lastCol}3");
                $sheet->setCellValue('A3', 'TAHUN ' . $this->year);
                $sheet->getStyle('A3')->getFont()->setBold(true)->setSize(13);
                $sheet->getStyle('A3')->getFont()->getColor()->setRGB('FF0000');
                $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

                $headerRow = 5;
                $headerRange = "A{$headerRow}:{$lastCol}{$headerRow}";

                $sheet->getStyle($headerRange)->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '375623']],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                        'wrapText' => true,
                    ],
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '000000']]],
                ]);
                $sheet->getRowDimension($headerRow)->setRowHeight(32);

                $lastRow = $sheet->getHighestRow();
                $sheet->getStyle("A{$headerRow}:{$lastCol}{$lastRow}")->applyFromArray([
                    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'B7B7B7']]],
                ]);

                $sheet->getStyle('A' . ($headerRow + 1) . ":{$lastCol}{$lastRow}")
                    ->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

                $sheet->freezePane('A' . ($headerRow + 1));
                $sheet->setAutoFilter($headerRange);
            },
        ];
    }
}