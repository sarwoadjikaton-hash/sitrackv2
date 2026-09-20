<?php

namespace App\Services;

use App\Models\Letter;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class WhatsAppService
{
    /**
     * Format raw phone number to Meta/International WhatsApp standard (e.g., 6281282521514).
     */
    public static function formatPhoneNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Strip non-digit characters (+, -, spaces, dots)
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (empty($clean)) {
            return null;
        }

        // Replace leading 0 with 62 (e.g. 0812... -> 62812...)
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        } elseif (str_starts_with($clean, '8')) {
            // E.g. 812... -> 62812...
            $clean = '62' . $clean;
        }

        // Validate minimum length (indonesian numbers are generally 10-15 digits with country code)
        if (strlen($clean) < 9 || strlen($clean) > 16) {
            return null;
        }

        return $clean;
    }

    /**
     * Get direct tracking URL (supports current public host / tunnel).
     */
    public static function getTrackingUrl(string $trackingCode): string
    {
        try {
            if (!app()->runningInConsole() && request()->hasHeader('host')) {
                return url('/tracking/' . $trackingCode);
            }
        } catch (\Throwable $e) {
            // fallback
        }

        $appUrl = rtrim(config('whatsapp.app_url', config('app.url', 'http://localhost:8000')), '/');
        return "{$appUrl}/tracking/{$trackingCode}";
    }

    /**
     * Send notification for newly submitted letter with tracking code / resi.
     */
    public static function sendSubmissionSuccess(Letter $letter): array
    {
        $phone = self::formatPhoneNumber($letter->sender_phone);
        if (!$phone) {
            return ['ok' => false, 'message' => 'Nomor WhatsApp pemohon tidak tersedia atau format tidak valid'];
        }

        $trackingUrl = self::getTrackingUrl($letter->tracking_code);
        $tanggal = $letter->letter_date ? Carbon::parse($letter->letter_date)->translatedFormat('d F Y') : Carbon::now()->translatedFormat('d F Y');
        $agenda = $letter->agenda_number ? "No. Agenda: *{$letter->agenda_number}*\n" : '';

        $message = "📄 *PENGAJUAN NASKAH DINAS BERHASIL - SITRACK*\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "Halo *{$letter->sender_name}*,\n"
            . "Permohonan naskah dinas Anda telah berhasil dicatat ke dalam sistem SiTrack.\n\n"
            . "📌 *DETAIL PENGAJUAN:*\n"
            . "• *Nomor Resi / Kode Tracking:* \n`{$letter->tracking_code}`\n"
            . $agenda
            . "• *Unit Pengusul:* {$letter->sender_unit}\n"
            . "• *Tujuan:* {$letter->destination}\n"
            . "• *Perihal:* {$letter->subject}\n"
            . "• *Status Awal:* {$letter->status}\n"
            . "• *Tanggal:* {$tanggal}\n\n"
            . "🔍 *PELACAKAN PROGRES:*\n"
            . "Anda dapat memantau posisi dan status dokumen secara langsung melalui tautan berikut:\n"
            . "👉 {$trackingUrl}\n\n"
            . "Simpan nomor resi di atas untuk keperluan konfirmasi dan tindak lanjut.\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "_Sistem Tracking & Pelayanan Surat (SiTrack)_";

        return self::sendMessage($phone, $message);
    }

    /**
     * Send notification for status updates made by Admin/TU.
     */
    public static function sendStatusUpdate(Letter $letter, ?string $note = null): array
    {
        $phone = self::formatPhoneNumber($letter->sender_phone);
        if (!$phone) {
            return ['ok' => false, 'message' => 'Nomor WhatsApp pemohon tidak tersedia atau format tidak valid'];
        }

        $trackingUrl = self::getTrackingUrl($letter->tracking_code);
        $waktu = Carbon::now('Asia/Jakarta')->translatedFormat('d F Y, H:i') . ' WIB';

        $statusBadge = self::getStatusIcon($letter->status);
        $catatanText = !empty($note) ? "• *Catatan Petugas:* _{$note}_\n" : '';
        $letterNo = !empty($letter->letter_number) ? "• *No. Surat Resmi:* `{$letter->letter_number}`\n" : '';

        $message = "🔔 *PEMBARUAN STATUS DOKUMEN - SITRACK*\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "Halo *{$letter->sender_name}*,\n"
            . "Ada pembaruan status terkini untuk naskah dinas Anda:\n\n"
            . "📌 *STATUS TERBARU:*\n"
            . "{$statusBadge} *{$letter->status}*\n"
            . "• *Posisi Berkas Saat Ini:* {$letter->current_position}\n"
            . $catatanText
            . "• *Nomor Resi / Tracking:* `{$letter->tracking_code}`\n"
            . $letterNo
            . "• *Perihal:* {$letter->subject}\n"
            . "• *Waktu Pembaruan:* {$waktu}\n\n"
            . "🔍 *LIHAT RIWAYAT LENGKAP:*\n"
            . "👉 {$trackingUrl}\n\n"
            . "━━━━━━━━━━━━━━━━━━━━\n"
            . "_Sistem Tracking & Pelayanan Surat (SiTrack)_";

        return self::sendMessage($phone, $message);
    }

    /**
     * Send raw message via configured gateway.
     */
    public static function sendMessage(string $toPhone, string $message): array
    {
        if (!config('whatsapp.enabled', true)) {
            Log::info("[WhatsApp] Notification skipped because WHATSAPP_ENABLED is false. Destination: {$toPhone}");
            return ['ok' => false, 'message' => 'WhatsApp notification is disabled'];
        }

        $provider = config('whatsapp.provider', 'meta');

        return match ($provider) {
            'meta' => self::sendViaMeta($toPhone, $message),
            'fonnte' => self::sendViaFonnte($toPhone, $message),
            'log' => self::logOnly($toPhone, $message),
            default => self::sendViaMeta($toPhone, $message),
        };
    }

    /**
     * Send WhatsApp message via Meta WhatsApp Cloud API.
     */
    protected static function sendViaMeta(string $toPhone, string $message): array
    {
        $version = config('whatsapp.meta.version', 'v22.0');
        $phoneNumberId = config('whatsapp.meta.phone_number_id');
        $token = config('whatsapp.meta.token');

        if (empty($phoneNumberId) || empty($token)) {
            Log::warning("[WhatsApp Meta Cloud API] Missing WHATSAPP_PHONE_NUMBER_ID or WHATSAPP_API_TOKEN in config.");
            return [
                'ok' => false,
                'message' => 'Konfigurasi Meta WhatsApp Cloud API (Phone Number ID / Token) belum diisi di file .env',
            ];
        }

        $endpoint = "https://graph.facebook.com/{$version}/{$phoneNumberId}/messages";

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $toPhone,
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $message,
            ],
        ];

        try {
            $response = Http::timeout(10)
                ->withToken($token)
                ->acceptJson()
                ->post($endpoint, $payload);

            if ($response->successful()) {
                Log::info("[WhatsApp Meta Cloud API] Success sent to {$toPhone}. Message ID: " . ($response->json('messages.0.id') ?? 'OK'));
                return [
                    'ok' => true,
                    'message' => 'Pesan WhatsApp berhasil dikirim',
                    'data' => $response->json(),
                ];
            }

            $errorData = $response->json();
            $errorMessage = $errorData['error']['message'] ?? $response->body();
            Log::error("[WhatsApp Meta Cloud API] Failed to {$toPhone}: HTTP {$response->status()} - {$errorMessage}");

            return [
                'ok' => false,
                'status' => $response->status(),
                'message' => "Gagal mengirim pesan WhatsApp: {$errorMessage}",
                'error' => $errorData,
            ];
        } catch (Throwable $e) {
            Log::error("[WhatsApp Meta Cloud API] Exception sending to {$toPhone}: " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Terjadi kesalahan koneksi saat mengirim WhatsApp: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Fallback/Alternative: Send via Fonnte
     */
    protected static function sendViaFonnte(string $toPhone, string $message): array
    {
        $token = config('whatsapp.fonnte.token');
        if (empty($token)) {
            return ['ok' => false, 'message' => 'Fonnte token not set'];
        }

        try {
            $response = Http::timeout(10)
                ->withHeaders(['Authorization' => $token])
                ->post('https://api.fonnte.com/send', [
                    'target' => $toPhone,
                    'message' => $message,
                    'countryCode' => '62',
                ]);

            $data = $response->json();
            $status = $response->successful() && ($data['status'] ?? false) === true;

            if ($status) {
                Log::info("[WhatsApp Fonnte] Success sent to {$toPhone}. Detail: " . ($data['detail'] ?? 'OK'));
                return [
                    'ok' => true,
                    'message' => 'Pesan WhatsApp berhasil dikirim via Fonnte',
                    'data' => $data,
                ];
            } else {
                $reason = $data['reason'] ?? ($data['detail'] ?? $response->body());
                Log::warning("[WhatsApp Fonnte] Failed to send to {$toPhone}: {$reason}");
                return [
                    'ok' => false,
                    'message' => "Fonnte: {$reason}",
                    'data' => $data,
                ];
            }
        } catch (Throwable $e) {
            Log::error("[WhatsApp Fonnte] Exception sending to {$toPhone}: " . $e->getMessage());
            return ['ok' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Log only mode for testing without external API calls
     */
    protected static function logOnly(string $toPhone, string $message): array
    {
        Log::info("[WhatsApp SIMULATION] To: {$toPhone}\nMessage:\n{$message}");
        return ['ok' => true, 'message' => 'Message logged successfully'];
    }

    /**
     * Helper to return suitable emoji for document status
     */
    protected static function getStatusIcon(string $status): string
    {
        return match ($status) {
            'Pengajuan Berhasil', 'Diregistrasi' => '📥',
            'Diterima', 'Surat Diterima TU' => '📬',
            'Diperiksa Oleh TU Sekjen', 'Diperiksa Oleh Kasubag TU Sekjen' => '📋',
            'Diperiksa Oleh Sekjen', 'Diajukan ke Sekjen' => '✍️',
            'Didisposisikan', 'Diteruskan ke Unit' => '🔄',
            'Dalam Tindak Lanjut' => '⚙️',
            'Selesai dan Siap Untuk diambil' => '📦',
            'Selesai' => '✅',
            'Dokumen Sudah diambil' => '🎉',
            'Revisi' => '⚠️',
            'Ditolak', 'Dikembalikan' => '❌',
            default => '📌',
        };
    }
}
