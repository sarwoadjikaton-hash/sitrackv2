<?php

namespace App\Console\Commands;

use App\Models\Letter;
use App\Services\WhatsAppService;
use Illuminate\Console\Command;

class SendWhatsAppTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wa:test {phone? : Nomor WhatsApp tujuan (misal 081282521514)} {--code= : Kode tracking surat jika ingin uji template surat}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Uji coba pengiriman pesan WhatsApp via Meta WhatsApp Cloud API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $phoneInput = $this->argument('phone') ?: $this->ask('Masukkan nomor WhatsApp tujuan (misal 081282521514)');
        $formatted = WhatsAppService::formatPhoneNumber($phoneInput);

        if (!$formatted) {
            $this->error("Format nomor telepon '{$phoneInput}' tidak valid!");
            return Command::FAILURE;
        }

        $this->info("Menyiapkan pesan ke: {$formatted}...");
        $provider = config('whatsapp.provider');
        $settings = [
            ['Enabled', config('whatsapp.enabled') ? 'true' : 'false'],
            ['Provider', $provider],
        ];

        if ($provider === 'fonnte') {
            $fonnteToken = config('whatsapp.fonnte.token');
            $settings[] = ['Fonnte Token', $fonnteToken ? substr($fonnteToken, 0, 10) . '...' : '(Kosong)'];
        } else {
            $settings[] = ['API Version', config('whatsapp.meta.version')];
            $settings[] = ['Phone Number ID', config('whatsapp.meta.phone_number_id') ?: '(Kosong)'];
            $settings[] = ['API Token', config('whatsapp.meta.token') ? substr(config('whatsapp.meta.token'), 0, 15) . '...' : '(Kosong)'];
        }

        $this->table(['Setting', 'Value'], $settings);

        $code = $this->option('code');
        if ($code) {
            $letter = Letter::where('tracking_code', $code)->first();
            if (!$letter) {
                $this->error("Surat dengan kode tracking '{$code}' tidak ditemukan.");
                return Command::FAILURE;
            }
            $letter->sender_phone = $formatted;
            $this->info("Menguji template notifikasi pengajuan berhasil untuk surat: {$code}...");
            $result = WhatsAppService::sendSubmissionSuccess($letter);
        } else {
            $message = "🧪 *UJI COBA NOTIFIKASI SITRACK*\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "Halo! Ini adalah pesan uji coba integrasi WhatsApp Cloud API pada sistem SiTrack.\n\n"
                . "✅ Koneksi API berhasil terhubung!\n"
                . "Waktu server: " . now()->format('d-m-Y H:i:s') . "\n"
                . "━━━━━━━━━━━━━━━━━━━━\n"
                . "_SiTrack Notification Bot_";

            $result = WhatsAppService::sendMessage($formatted, $message);
        }

        if ($result['ok'] ?? false) {
            $this->info(" Berhasil: " . ($result['message'] ?? 'Pesan terkirim'));
            if (!empty($result['data'])) {
                $this->line(json_encode($result['data'], JSON_PRETTY_PRINT));
            }
            return Command::SUCCESS;
        } else {
            $this->error("❌ Gagal: " . ($result['message'] ?? 'Terjadi kesalahan'));
            if (!empty($result['error'])) {
                $this->line(json_encode($result['error'], JSON_PRETTY_PRINT));
            }
            return Command::FAILURE;
        }
    }
}
