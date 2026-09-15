<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('sitrack:reset-letters {--force : Jalankan tanpa konfirmasi manual}', function () {
    $this->warn('Memulai proses pembersihan data transaksi surat, nomor surat, dan lampiran...');

    \Illuminate\Support\Facades\DB::transaction(function () {
        \App\Models\LetterRelation::query()->delete();
        \App\Models\Disposition::query()->delete();
        \App\Models\LetterStatusLog::query()->delete();
        \App\Models\Letter::query()->delete();
        \App\Models\LetterNumber::query()->delete();
        \App\Models\LetterNumberAvailabilityBatch::query()->delete();
    });

    \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('letters');
    \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('data-surat');
    \Illuminate\Support\Facades\Storage::disk('public')->deleteDirectory('dispositions');
    \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('letters');
    \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('data-surat');
    \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('dispositions');

    $this->info('✓ Data surat, stok nomor surat, ketersediaan nomor, disposisi, log status, dan file lampiran berhasil dibersihkan total!');
})->purpose('Reset semua data transaksi surat, stok nomor, dan berkas lampiran tanpa menghapus master data (users, units, jenis naskah).');
