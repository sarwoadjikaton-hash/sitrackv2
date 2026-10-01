<?php

namespace App\Exports;

use App\Models\LetterNumberType;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DataSuratMultiSheetExport implements WithMultipleSheets
{
    public function __construct(
        protected int $typeId,
        protected int $year,
        protected string $search = '',
        protected string $status = 'all'
    ) {
    }

    public function sheets(): array
    {
        $types = LetterNumberType::query()
            ->where('is_active', true)
            ->when($this->typeId > 0, fn($q) => $q->where('id', $this->typeId))
            ->orderBy('display_order')
            ->get();

        $groupedByWorkbook = $types->groupBy('workbook_name');
        $sheets = [];

        // 1. Sheet(s) untuk Data yang SUDAH Diberi Nomor (Bernomor)
        if (in_array($this->status, ['all', 'with_number', 'used', 'reserved'])) {
            foreach ($groupedByWorkbook as $workbookName => $typesInWorkbook) {
                $sheetTitle = $workbookName;
                if ($this->status === 'all' && $this->typeId > 0) {
                    $sheetTitle = mb_substr($workbookName, 0, 20) . ' (Bernomor)';
                }

                $sheets[] = new DataSuratExport(
                    $workbookName,
                    $typesInWorkbook,
                    $this->year,
                    $this->search,
                    'with_number',
                    $sheetTitle
                );
            }
        }

        // 2. Sheet(s) untuk Data yang BELUM Diberi Nomor (Terpisah Sheet)
        if (in_array($this->status, ['all', 'without_number', 'no_number'])) {
            if ($this->typeId > 0) {
                foreach ($groupedByWorkbook as $workbookName => $typesInWorkbook) {
                    $sheetTitle = mb_substr($workbookName, 0, 19) . ' (Belum No)';
                    $sheets[] = new DataSuratExport(
                        $workbookName,
                        $typesInWorkbook,
                        $this->year,
                        $this->search,
                        'without_number',
                        $sheetTitle
                    );
                }
            } else {
                // Saat ekspor semua jenis naskah, pisahkan sheet khusus "Belum Diberi Nomor"
                $sheets[] = new DataSuratExport(
                    'Naskah Belum Diberi Nomor',
                    $types,
                    $this->year,
                    $this->search,
                    'without_number',
                    'Belum Diberi Nomor'
                );
            }
        }

        return $sheets;
    }
}