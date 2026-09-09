<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class PdfTextExtractor
{
    /**
     * Ekstrak teks dari file PDF yang tersimpan di disk 'public'.
     * Mengembalikan null (bukan melempar error) kalau file bukan PDF,
     * corrupt, atau gagal diparse — supaya proses upload utama tetap
     * berhasil walau ekstraksi teksnya gagal.
     */
    public static function extract(?string $storagePath): ?string
    {
        if (!$storagePath) {
            return null;
        }

        // Hanya proses file berekstensi .pdf
        if (strtolower(pathinfo($storagePath, PATHINFO_EXTENSION)) !== 'pdf') {
            return null;
        }

        if (!Storage::disk('public')->exists($storagePath)) {
            return null;
        }

        try {
            $fullPath = Storage::disk('public')->path($storagePath);
            $parser = new Parser();
            $pdf = $parser->parseFile($fullPath);
            $text = $pdf->getText();

            // Batasi panjang teks yang disimpan (hindari PDF ratusan halaman
            // membengkakkan database secara tidak wajar)
            $text = trim($text);
            if (mb_strlen($text) > 50000) {
                $text = mb_substr($text, 0, 50000);
            }

            return $text !== '' ? $text : null;
        } catch (\Throwable $e) {
            Log::warning('Gagal ekstraksi teks PDF: ' . $storagePath, [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}