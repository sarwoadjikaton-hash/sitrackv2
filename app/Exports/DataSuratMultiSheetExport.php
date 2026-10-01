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
        $usedTitles = [];

        // Helper untuk menghasilkan judul sheet unik dan valid (<= 31 karakter)
        $makeSafeTitle = function (string $name, string $suffix = '') use (&$usedTitles): string {
            $cleanName = preg_replace('/[:\\\\\/\?\*\[\]]/', '-', $name);
            $cleanSuffix = $suffix ? ' ' . $suffix : '';
            $maxLen = 31 - mb_strlen($cleanSuffix);
            $base = mb_substr($cleanName, 0, max(1, $maxLen)) . $cleanSuffix;
            $title = $base;
            $counter = 1;
            while (in_array(strtolower($title), $usedTitles, true)) {
                $countSuffix = " ({$counter})";
                $title = mb_substr($base, 0, 31 - mb_strlen($countSuffix)) . $countSuffix;
                $counter++;
            }
            $usedTitles[] = strtolower($title);
            return $title;
        };

        // 1. Jika pilih 'all' (Semua):
        // Setiap naskah dinas dibuatkan 2 sheet terpisah: [Naskah] (Bernomor) dan [Naskah] (Belum No)
        if ($this->status === 'all') {
            // Sheet per naskah dinas - SUDAH BERNOMOR
            foreach ($groupedByWorkbook as $workbookName => $typesInWorkbook) {
                $title = $makeSafeTitle($workbookName, '(Bernomor)');
                $sheets[] = new DataSuratExport(
                    $workbookName,
                    $typesInWorkbook,
                    $this->year,
                    $this->search,
                    'with_number',
                    $title
                );
            }

            // Sheet per naskah dinas - BELUM BERNOMOR
            foreach ($groupedByWorkbook as $workbookName => $typesInWorkbook) {
                $title = $makeSafeTitle($workbookName, '(Belum No)');
                $sheets[] = new DataSuratExport(
                    $workbookName,
                    $typesInWorkbook,
                    $this->year,
                    $this->search,
                    'without_number',
                    $title
                );
            }
        } elseif (in_array($this->status, ['with_number', 'used', 'reserved'])) {
            // 2. Jika pilih 'Hanya Sudah Bernomor': Tetap dipisah per sheet naskah dinas
            foreach ($groupedByWorkbook as $workbookName => $typesInWorkbook) {
                $title = $makeSafeTitle($workbookName);
                $sheets[] = new DataSuratExport(
                    $workbookName,
                    $typesInWorkbook,
                    $this->year,
                    $this->search,
                    'with_number',
                    $title
                );
            }
        } elseif (in_array($this->status, ['without_number', 'no_number'])) {
            // 3. Jika pilih 'Hanya Belum Bernomor': Tetap dipisah per sheet naskah dinas
            foreach ($groupedByWorkbook as $workbookName => $typesInWorkbook) {
                $title = $makeSafeTitle($workbookName);
                $sheets[] = new DataSuratExport(
                    $workbookName,
                    $typesInWorkbook,
                    $this->year,
                    $this->search,
                    'without_number',
                    $title
                );
            }
        }

        return $sheets;
    }
}