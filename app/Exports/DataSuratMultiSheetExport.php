<?php

namespace App\Exports;

use App\Models\LetterNumberType;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DataSuratMultiSheetExport implements WithMultipleSheets
{
    public function __construct(
        protected int $typeId,
        protected int $year,
        protected string $search
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

        return $groupedByWorkbook
            ->map(fn($typesInWorkbook, $workbookName) => new DataSuratExport(
                $workbookName,
                $typesInWorkbook,
                $this->year,
                $this->search
            ))
            ->values()
            ->all();
    }
}