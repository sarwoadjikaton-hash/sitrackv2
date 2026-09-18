<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PrintController extends Controller
{
    /**
     * Print Lembar Disposisi (A5)
     */
    public function disposisi($id): Response
    {
        $letter = Letter::with(['category', 'recipientUnit', 'dispositions.toUnit'])
            ->findOrFail($id);

        return Inertia::render('Print/Disposisi', [
            'letter' => $letter,
        ]);
    }

    /**
     * Print Lembar Pendamping Penandatanganan / Tindak Lanjut (A5)
     */
    public function pendamping($id): Response
    {
        $letter = Letter::with(['category', 'recipientUnit', 'statusLogs'])
            ->findOrFail($id);

        // TAMBAHAN: generate QR sebagai SVG lokal (server-side), lalu embed jadi data URI.
        // Tidak butuh Imagick, tidak ada request ke domain luar, dan lolos CSP img-src 'self'
        // karena data: URI dianggap sumber gambar inline, bukan request eksternal.
        $trackingUrl = url('/tracking/' . $letter->tracking_code);
        $qrSvg = QrCode::size(200)->generate($trackingUrl);
        $qrCodeBase64 = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        // Find existing signature if available
        $signaturePath = null;
        $receiverName = null;

        if ($letter->attachment_path && (str_contains($letter->attachment_path, 'signatures/') || str_contains($letter->attachment_path, 'sig_'))) {
            $signaturePath = $letter->attachment_path;
        }

        if (!$signaturePath && $letter->statusLogs) {
            $sigLog = $letter->statusLogs->sortByDesc('id')->first(function ($log) {
                return $log->attachment_path && (str_contains($log->attachment_path, 'signatures/') || str_contains($log->attachment_path, 'sig_'));
            });
            if ($sigLog) {
                $signaturePath = $sigLog->attachment_path;
            }
        }

        // Receiver name for signature is intentionally left empty so it displays dots ( .................................... )
        $receiverName = null;

        return Inertia::render('Print/Pendamping', [
            'letter' => $letter,
            'qrCodeBase64' => $qrCodeBase64,
            'signaturePath' => $signaturePath,
            'receiverName' => $receiverName,
        ]);
    }

    /**
     * Simpan Tanda Tangan Langsung Lembar Pendamping & Auto Update Status Dokumen Sudah Diambil
     */
    public function saveSignature(Request $request, $id)
    {
        $request->validate([
            'signature_base64' => ['required', 'string'],
            'receiver_name' => ['nullable', 'string', 'max:150'],
            'auto_update_status' => ['nullable', 'boolean'],
        ]);

        $letter = Letter::findOrFail($id);

        $base64 = $request->input('signature_base64');
        if (preg_match('/^data:image\/(\w+);base64,/', $base64, $type)) {
            $data = substr($base64, strpos($base64, ',') + 1);
            $type = strtolower($type[1]);
            $data = base64_decode($data);
            if ($data === false) {
                return response()->json(['ok' => false, 'message' => 'Format gambar tanda tangan tidak valid'], 422);
            }
        } else {
            return response()->json(['ok' => false, 'message' => 'Data base64 tidak valid'], 422);
        }

        $filename = 'signatures/sig_' . $letter->id . '_' . time() . '.' . $type;
        \Illuminate\Support\Facades\Storage::disk('public')->put($filename, $data);

        $receiverName = $request->input('receiver_name') ?: 'Penerima Berkas';

        // Update letter attachment_path
        $updates = [
            'attachment_path' => $filename,
        ];

        $shouldUpdateStatus = $request->boolean('auto_update_status', true);
        if ($shouldUpdateStatus) {
            $updates['status'] = 'Dokumen Sudah diambil';
            $updates['current_position'] = 'Unit Pengolah / Pemohon';

            \App\Models\LetterStatusLog::create([
                'letter_id' => $letter->id,
                'status' => 'Dokumen Sudah diambil',
                'position' => 'Unit Pengolah / Pemohon',
                'note' => "Dokumen fisik telah diambil oleh {$receiverName} dengan tanda tangan digital lembar pendamping.",
                'attachment_path' => $filename,
                'changed_by' => \Illuminate\Support\Facades\Auth::user()?->name ?: 'Petugas TU',
                'changed_at' => now(),
            ]);
        }

        $letter->update($updates);

        return response()->json([
            'ok' => true,
            'message' => 'Tanda tangan digital berhasil disimpan dan berkas otomatis terlampir!',
            'attachment_path' => $filename,
        ]);
    }
}