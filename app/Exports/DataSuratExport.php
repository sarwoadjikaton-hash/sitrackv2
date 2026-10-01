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
        protected string $search = '',
        protected string $status = 'all'
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
        $items = collect();

        // 1. Ambil Data Surat Bernomor / Reservasi (LetterNumber)
        if (in_array($this->status, ['all', 'with_number', 'used', 'reserved'])) {
            $query = LetterNumber::with(['type', 'unit', 'letter'])
                ->whereIn('type_id', $typeIds)
                ->where('number_year', $this->year);

            if ($this->status === 'with_number' || $this->status === 'used') {
                $query->where('status', 'used');
            } elseif ($this->status === 'reserved') {
                $query->whereIn('status', ['reserved', 'preorder']);
            } else {
                $query->whereIn('status', ['used', 'reserved', 'preorder']);
            }

            if ($this->search !== '') {
                $query->where(function ($q) {
                    $q->where('number_text', 'ILIKE', "%{$this->search}%")
                        ->orWhere('subject', 'ILIKE', "%{$this->search}%")
                        ->orWhere('processing_unit_text', 'ILIKE', "%{$this->search}%")
                        ->orWhere('signatory', 'ILIKE', "%{$this->search}%")
                        ->orWhere('destination', 'ILIKE', "%{$this->search}%");
                });
            }

            $numberedItems = $query->orderBy('sequence_number', 'asc')->get();
            $items = $items->concat($numberedItems);
        }

        // 2. Ambil Data Surat Belum Bernomor (Letter)
        if (in_array($this->status, ['all', 'without_number', 'no_number'])) {
            $unQuery = \App\Models\Letter::with(['letterNumberType', 'recipientUnit'])
                ->where(function ($q) {
                    $q->whereNull('letter_number')->orWhere('letter_number', '');
                })
                ->where(function ($q) {
                    $q->whereYear('received_date', $this->year)
                      ->orWhere(function ($sub) {
                          $sub->whereNull('received_date')->whereYear('created_at', $this->year);
                      });
                })
                ->where(function ($q) use ($typeIds) {
                    $q->whereIn('letter_number_type_id', $typeIds)
                      ->orWhereNull('letter_number_type_id');
                });

            if ($this->search !== '') {
                $unQuery->where(function ($q) {
                    $q->where('subject', 'ILIKE', "%{$this->search}%")
                        ->orWhere('sender_unit', 'ILIKE', "%{$this->search}%")
                        ->orWhere('sender_name', 'ILIKE', "%{$this->search}%")
                        ->orWhere('destination', 'ILIKE', "%{$this->search}%")
                        ->orWhere('agenda_number', 'ILIKE', "%{$this->search}%")
                        ->orWhere('tracking_code', 'ILIKE', "%{$this->search}%");
                });
            }

            $unnumberedItems = $unQuery->orderBy('received_date', 'asc')->orderBy('id', 'asc')->get()->map(function ($l) {
                return (object) [
                    'id' => $l->id,
                    'is_unnumbered' => true,
                    'sequence_number' => null,
                    'incoming_date' => $l->received_date ?: $l->created_at,
                    'processing_unit_text' => $l->sender_unit ?: $l->sender_name ?: '-',
                    'signatory' => $l->signatory_name ?: $l->sender_name ?: '-',
                    'request_type' => $l->requested_actions ?: ($l->priority ?: 'Biasa'),
                    'destination' => $l->destination ?: ($l->recipientUnit?->unit_name ?: '-'),
                    'letter_date' => $l->letter_date,
                    'security_access' => $l->security_level ?: 'B',
                    'classification_code' => $l->archive_classification_code ?: 'UM.01',
                    'month_number' => $l->received_date ? $l->received_date->month : ($l->letter_date ? $l->letter_date->month : null),
                    'number_text' => '(Belum Diberi Nomor)',
                    'subject' => $l->subject,
                    'technical_officer' => $l->technical_officer ?: '-',
                    'type' => $l->letterNumberType,
                ];
            });

            $items = $items->concat($unnumberedItems);
        }

        return $items;
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
        $seqText = !empty($record->sequence_number)
            ? str_pad((string) $record->sequence_number, $padding, '0', STR_PAD_LEFT)
            : '-';

        return [
            $this->rowCounter,
            $record->incoming_date ? \Carbon\Carbon::parse($record->incoming_date)->format('d/m/Y') : '',
            $record->processing_unit_text ?: '-',
            $record->signatory ?: '-',
            $record->request_type ?: '-',
            $record->destination ?: '-',
            $record->letter_date ? \Carbon\Carbon::parse($record->letter_date)->format('d M Y') : '',
            $record->security_access ?: 'B',
            $seqText,
            $record->classification_code ?: 'UM.01',
            $record->month_number ?: '',
            $record->number_text ?: '(Belum Diberi Nomor)',
            $record->subject ?: '-',
            $record->technical_officer ?: '-',
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