<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Events\AfterSheet;

class DataSuratTemplateExport implements FromArray, WithHeadings, ShouldAutoSize, WithEvents
{
    /** Header row exactly as used by the legacy workbook format. */
    public function headings(): array
    {
        return [
            'NO',
            'TANGGAL MASUK',
            'UNIT PENGOLAH ARSIP',
            'PENANDATANGAN SURAT',
            'PERMOHONAN',
            'TUJUAN SURAT',
            'TANGGAL SURAT',
            'KEAMANAN AKSES',
            'NOMOR URUT',
            'KODE KLAS. ARSIP',
            'BULAN',
            'NOMOR SURAT',
            'PERIHAL SURAT',
            'PETUGAS UNIT TEKNIS',
            'ND Pengantar',
        ];
    }

    /** One example row so the person filling it in knows the expected format per column. */
    public function array(): array
    {
        return [
            [
                1,
                '2026-01-15',
                'Biro Umum',
                'Sekretaris Jenderal',
                'Permohonan Cuti',
                'Kepala Bagian TU',
                '2026-01-16',
                'B',
                1,
                'UM.01',
                'Januari',
                'ND-001/UM/I/2026',
                'Contoh perihal surat di sini',
                'Nama Petugas',
                'ND-002/UM/I/2026',
            ],
        ];
    }

    /** Bold the header row for readability. */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->getStyle('A1:O1')->getFont()->setBold(true);
                $event->sheet->getStyle('A1:O1')->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('DBEAFE');
            },
        ];
    }
}