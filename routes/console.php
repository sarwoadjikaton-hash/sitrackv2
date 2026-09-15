<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sitrack:reset-letters {--all-slots : Hapus juga semua batch dan stok slot nomor} {--force : Jalankan tanpa konfirmasi manual}', function () {
    $this->warn('Memulai proses pembersihan data transaksi surat & lampiran...');

    \Illuminate\Support\Facades\DB::transaction(function () {
        \App\Models\LetterRelation::query()->delete();
        \App\Models\Disposition::query()->delete();
        \App\Models\LetterStatusLog::query()->delete();
        \App\Models\Letter::query()->delete();

        if ($this->option('all-slots')) {
            \App\Models\LetterNumber::query()->delete();
            \App\Models\LetterNumberAvailabilityBatch::query()->delete();
        } else {
            \App\Models\LetterNumber::query()->update([
                'status' => 'available',
                'number_text' => null,
                'incoming_date' => null,
                'unit_id' => null,
                'processing_unit_text' => null,
                'signatory' => null,
                'request_type' => null,
                'destination' => null,
                'letter_date' => null,
                'security_access' => null,
                'classification_code' => null,
                'month_number' => null,
                'subject' => null,
                'technical_officer' => null,
                'scan_result' => null,
                'nd_pengantar' => null,
                'attachment_path' => null,
                'pdf_content' => null,
                'linked_letter_id' => null,
                'reserved_for' => null,
                'reserved_at' => null,
                'used_at' => null,
            ]);
        }
    });

    \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('letters');
    \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('data-surat');
    \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('dispositions');
    \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('letters');
    \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('data-surat');
    \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('dispositions');

    $this->info('✓ Data surat, disposisi, log status, dan file lampiran berhasil dibersihkan!');
})->purpose('Reset semua data surat dan berkas lampiran tanpa menghapus master data (users, units, dll).');
