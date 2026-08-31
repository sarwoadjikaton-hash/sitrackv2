<?php

namespace App\Exports;

use App\Models\LetterNumber;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class DataSuratExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        protected int $workbookId,
        protected string $search
    ) {
    }

    public function collection()
    {
        $query = LetterNumber::with(['type', 'unit'])
            ->where('status', 'used');

        if ($this->workbookId > 0) {
            $query->where('type_id', $this->workbookId);
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

        return $query->orderByRaw('COALESCE(incoming_date, letter_date) DESC')
            ->orderBy('id', 'desc')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No Urut',
            'Nomor Surat',
            'Tanggal Masuk',
            'Tanggal Surat',
            'Unit Pengolah',
            'Penandatangan',
            'Permohonan',
            'Tujuan',
            'Keamanan Akses',
            'Kode Klasifikasi',
            'Perihal Surat',
            'Petugas Unit Teknis',
            'Hasil / ND',
        ];
    }

    public function map($record): array
    {
        return [
            str_pad((string) $record->sequence_number, $record->type?->sequence_padding ?? 4, '0', STR_PAD_LEFT),
            $record->number_text,
            $record->incoming_date ? \Carbon\Carbon::parse($record->incoming_date)->format('d-m-Y') : '',
            $record->letter_date ? \Carbon\Carbon::parse($record->letter_date)->format('d-m-Y') : '',
            $record->processing_unit_text,
            $record->signatory,
            $record->request_type,
            $record->destination,
            $record->security_access,
            $record->classification_code,
            $record->subject,
            $record->technical_officer,
            $record->scan_result ?: $record->nd_pengantar,
        ];
    }
}