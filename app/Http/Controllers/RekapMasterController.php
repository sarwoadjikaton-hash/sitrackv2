<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use App\Models\Unit;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RekapMasterController extends Controller
{
    /**
     * Display master recap overview
     */
    public function index(Request $request): Response
    {
        $year = (int) $request->input('year', date('Y'));

        $typeStats = LetterNumberType::where('is_active', true)
            ->withCount([
                'letterNumbers as total_slots' => fn ($q) => $q->where('number_year', $year),
                'letterNumbers as used_slots' => fn ($q) => $q->where('number_year', $year)->where('status', 'used'),
                'letterNumbers as available_slots' => fn ($q) => $q->where('number_year', $year)->where('status', 'available'),
                'letterNumbers as reserved_slots' => fn ($q) => $q->where('number_year', $year)->where('status', 'reserved'),
            ])
            ->orderBy('display_order', 'asc')
            ->get();

        $signatureCount = Letter::where('process_lane', 'signature')->count();
        $dispositionCount = Letter::where('process_lane', 'disposition')->count();

        return Inertia::render('RekapMaster/Index', [
            'typeStats' => $typeStats,
            'selectedYear' => $year,
            'totalSignature' => $signatureCount,
            'totalDisposition' => $dispositionCount,
        ]);
    }

    /**
     * Export letters or letter numbers to CSV
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $type = $request->input('type', 'letters');
        $fileName = "rekap_{$type}_" . date('Ymd_His') . ".csv";

        $response = new StreamedResponse(function () use ($type) {
            $handle = fopen('php://output', 'w');
            
            // Add UTF-8 BOM for Excel
            fputs($handle, "\xEF\xBB\xBF");

            if ($type === 'data_surat') {
                fputcsv($handle, [
                    'No Urut', 'Jenis Naskah', 'Nomor Surat', 'Tanggal Masuk', 'Tanggal Surat',
                    'Unit Pengolah', 'Penandatangan', 'Permohonan', 'Tujuan', 'Perihal', 'Petugas'
                ]);

                LetterNumber::with(['type', 'unit'])
                    ->where('status', 'used')
                    ->orderBy('id', 'desc')
                    ->chunk(200, function ($rows) use ($handle) {
                        foreach ($rows as $r) {
                            fputcsv($handle, [
                                $r->sequence_number,
                                $r->type?->workbook_name,
                                $r->number_text,
                                $r->incoming_date?->format('Y-m-d'),
                                $r->letter_date?->format('Y-m-d'),
                                $r->processing_unit_text,
                                $r->signatory,
                                $r->request_type,
                                $r->destination,
                                $r->subject,
                                $r->technical_officer,
                            ]);
                        }
                    });
            } else {
                fputcsv($handle, [
                    'Kode Tracking', 'Nomor Agenda', 'Lajur', 'Nomor Surat', 'Pengirim / Asal',
                    'Perihal', 'Tujuan Unit', 'Status', 'Posisi Terakhir', 'Tanggal Diterima'
                ]);

                Letter::with(['category', 'recipientUnit'])
                    ->orderBy('id', 'desc')
                    ->chunk(200, function ($letters) use ($handle) {
                        foreach ($letters as $l) {
                            fputcsv($handle, [
                                $l->tracking_code,
                                $l->agenda_number,
                                $l->process_lane === 'disposition' ? 'Lajur Disposisi' : 'Tindak Lanjut / TTD',
                                $l->letter_number,
                                $l->sender_unit ?: $l->sender_name,
                                $l->subject,
                                $l->recipientUnit?->unit_name ?: '-',
                                $l->status,
                                $l->current_position,
                                $l->received_date?->format('Y-m-d'),
                            ]);
                        }
                    });
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$fileName}\"");

        return $response;
    }
}
