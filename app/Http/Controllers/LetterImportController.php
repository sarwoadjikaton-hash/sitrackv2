<?php

namespace App\Http\Controllers;

use App\Exports\DataSuratTemplateExport;
use App\Imports\DataSuratImport;
use App\Imports\LettersImport;
use App\Imports\NumberBatchesImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LetterImportController extends Controller
{
    public function downloadDataSuratTemplate(): BinaryFileResponse
    {
        return Excel::download(new DataSuratTemplateExport(), 'template-data-surat.xlsx');
    }

    public function importDataSurat(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type_id' => ['nullable', 'integer', 'exists:letter_number_types,id'],
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new DataSuratImport();

        try {
            $import->import(
                $request->file('file')->getRealPath(),
                !empty($data['type_id']) ? (int) $data['type_id'] : null
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        $messages = [];

        if (!empty($import->skippedSheets)) {
            $messages[] = count($import->skippedSheets) . ' sheet dilewati karena bukan Jenis Naskah: ' . implode(', ', $import->skippedSheets);
        }

        if (!empty($import->failures)) {
            $firstErrors = collect($import->failures)->take(5)
                ->map(fn($f) => "[{$f['sheet']}] Baris {$f['row']}: " . implode(', ', (array) $f['errors']))
                ->implode(' | ');
            $messages[] = count($import->failures) . ' baris gagal: ' . $firstErrors;
        }

        if (!empty($messages)) {
            return back()->with('warning', "{$import->imported} data surat berhasil diimpor. " . implode(' | ', $messages));
        }

        return back()->with('success', "{$import->imported} data surat berhasil diimpor.");
    }

    public function importLetters(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $import = new LettersImport();

        try {
            Excel::import($import, $request->file('file'));
        } catch (ValidationException $e) {
            return back()->with('error', 'Import gagal: ' . $this->summarizeFailures($e->failures()));
        } catch (\Throwable $e) {
            return back()->with('error', 'Import gagal: ' . $e->getMessage());
        }

        if (!empty($import->failures)) {
            return back()->with('warning', "{$import->imported} surat berhasil diimpor, tapi " . count($import->failures) . " baris gagal. Lihat log untuk detail.");
        }

        return back()->with('success', "{$import->imported} surat berhasil diimpor.");
    }

    public function importNumberBatches(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:20480',
        ]);
        Excel::import(new NumberBatchesImport, $request->file('file'));

        return back()->with('success', 'Data stok nomor berhasil diimport!');
    }

    private function summarizeFailures(array $failures): string
    {
        return collect($failures)->take(5)
            ->map(fn($f) => 'Baris ' . $f->row() . ': ' . implode(', ', $f->errors()))
            ->implode(' | ');
    }
}