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
     * Print Lembar Disposisi (A4)
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
     * Print Lembar Pendamping Penandatanganan / Tindak Lanjut (A4)
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

        return Inertia::render('Print/Pendamping', [
            'letter' => $letter,
            'qrCodeBase64' => $qrCodeBase64,
        ]);
    }
}