<?php

namespace App\Http\Controllers;

use App\Models\AppNotification;
use App\Models\Letter;
use App\Models\LetterCategory;
use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use App\Models\LetterStatusLog;
use App\Models\Unit;
use App\Services\LetterNumberService;
use App\Services\PdfTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class PublicTrackingController extends Controller
{
    /**
     * Public tracking search and result page
     */
    public function index(Request $request): Response
    {
        $rawCode = trim((string) $request->input('code', ''));
        $code = $rawCode;
        if (preg_match('/tracking\/([A-Za-z0-9_\-]+)/i', $rawCode, $matches)) {
            $code = $matches[1];
        }

        $letter = null;
        $logs = [];
        $dispositions = [];
        $progress = 0;

        if ($code !== '') {
            $letter = Letter::with(['category', 'recipientUnit'])
                ->where(function ($q) use ($code, $rawCode) {
                    $q->where('tracking_code', $code)
                        ->orWhere('tracking_code', $rawCode)
                        ->orWhere('agenda_number', $code)
                        ->orWhere('agenda_number', $rawCode)
                        ->orWhere('letter_number', $code)
                        ->orWhere('letter_number', $rawCode);
                })
                ->first();

            if ($letter) {
                $logs = LetterStatusLog::where('letter_id', $letter->id)
                    ->orderBy('changed_at', 'asc')
                    ->get();

                if ($letter->process_lane === 'disposition') {
                    $dispositions = $letter->dispositions()->with('toUnit')->get();
                }

                // Calculate progress %
                $progress = match ($letter->status) {
                    'Diregistrasi' => 10,
                    'Diterima' => 20,
                    'Diperiksa Oleh TU Sekjen' => 40,
                    'Diperiksa Oleh Kasubag TU Sekjen' => 60,
                    'Diperiksa Oleh Sekjen' => 80,
                    'Selesai dan Siap Untuk diambil', 'Selesai' => 95,
                    'Dokumen Sudah diambil' => 100,

                    'Surat Diterima TU' => 20,
                    'Diajukan ke Sekjen' => 45,
                    'Didisposisikan' => 65,
                    'Diteruskan ke Unit' => 80,
                    'Dalam Tindak Lanjut' => 85,

                    'Revisi' => 35,
                    'Ditolak', 'Dikembalikan' => 100,
                    default => 25,
                };
            }
        }

        return Inertia::render('Tracking/Index', [
            'searchCode' => $code,
            'letter' => $letter,
            'logs' => $logs,
            'dispositions' => $dispositions,
            'progress' => $progress,
        ]);
    }

    /**
     * Public letter submission form
     */
    public function create(): Response
    {
        $categories = LetterCategory::where('is_active', true)->orderBy('category_name')->get();
        $units = Unit::where('is_active', true)->orderBy('unit_name')->get();
        $types = LetterNumberType::where('is_active', true)->orderBy('display_order')->get();

        return Inertia::render('Tracking/Submit', [
            'categories' => $categories,
            'units' => $units,
            'types' => $types,
        ]);
    }

    /**
     * Store public submitted letter
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'sender_name' => ['required', 'string', 'max:150'],
            'sender_phone' => ['required', 'string', 'max:50'],
            'unit_id' => ['required', 'exists:units,id'],
            'destination' => ['required', 'string', 'max:255'],
            'priority' => ['required', 'in:Biasa,Segera'],
            'type_id' => ['required', 'exists:letter_number_types,id'],
            'subject' => ['required', 'string', 'max:500'],
            'letter_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:20480'],
        ]);

        DB::beginTransaction();
        try {
            /** @var LetterNumberType $type */
            $type = LetterNumberType::findOrFail($validated['type_id']);

            $unit = Unit::find($validated['unit_id']);
            $unitPengusulName = $unit ? $unit->unit_name : 'Unit Pengusul';

            $trackingCode = LetterNumberService::generateTrackingCode($type->type_code);
            $agendaNumber = LetterNumberService::nextAgendaNumber('in');

            $attachmentPath = null;
            $pdfContent = null;
            $uploadedAttachmentName = null;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('letters', 'public');
                $uploadedAttachmentName = $request->file('attachment')->getClientOriginalName();
                $pdfContent = PdfTextExtractor::extract($attachmentPath);
            }

            $letter = Letter::create([
                'tracking_code' => $trackingCode,
                'agenda_number' => $agendaNumber,
                'letter_number' => null,
                'letter_type' => 'in',
                'letter_source' => 'Manual',
                'process_lane' => 'signature',
                'letter_number_type_id' => $type->id,
                'sender_unit' => $unitPengusulName,
                'sender_name' => $validated['sender_name'],
                'sender_phone' => $validated['sender_phone'],
                'recipient_unit_id' => null,
                'destination' => $validated['destination'],
                'subject' => $validated['subject'],
                'letter_date' => $validated['letter_date'] ?? date('Y-m-d'),
                'received_date' => date('Y-m-d'),
                'status' => 'Pengajuan Berhasil',
                'priority' => $validated['priority'],
                'security_level' => 'Biasa',
                'current_position' => $unitPengusulName,
                'notes' => $validated['notes'] ?? null,
                'attachment_path' => $attachmentPath,
                'pdf_content' => $pdfContent,
            ]);

            LetterStatusLog::create([
                'letter_id' => $letter->id,
                'status' => 'Pengajuan Berhasil',
                'position' => $unitPengusulName,
                'note' => 'Permohonan paraf naskah dinas berhasil diajukan via portal SiTrack.',
                'attachment_path' => $attachmentPath,
                'attachment_name' => $uploadedAttachmentName,
                'changed_by' => 'Pemohon (Publik)',
                'changed_at' => now(),
            ]);

            if (Schema::hasTable('app_notifications')) {
                try {
                    AppNotification::create([
                        'letter_id' => $letter->id,
                        'type' => 'new_letter_submission',
                        'title' => 'Pengajuan Surat Masuk Baru',
                        'message' => "{$unitPengusulName} ({$validated['sender_name']}) mengajukan permohonan naskah: \"{$validated['subject']}\"",
                        'data' => [
                            'id' => $letter->id,
                            'letter_id' => $letter->id,
                            'tracking_code' => $trackingCode,
                            'agenda_number' => $agendaNumber,
                            'sender_unit' => $unitPengusulName,
                            'sender_name' => $validated['sender_name'],
                            'destination' => $validated['destination'],
                            'subject' => $validated['subject'],
                            'priority' => $validated['priority'],
                            'created_at' => now()->toIso8601String(),
                        ],
                        'is_read' => false,
                    ]);
                } catch (\Throwable $ne) {
                    // Fail silently so letter submission is never blocked
                }
            }

            DB::commit();

            // Push WhatsApp Notification to sender
            try {
                \App\Services\WhatsAppService::sendSubmissionSuccess($letter);
            } catch (\Throwable $we) {
                \Illuminate\Support\Facades\Log::warning('[WhatsApp] Failed to dispatch submission notification: ' . $we->getMessage());
            }

            return redirect()->route('tracking.success', ['code' => $trackingCode]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show tracking submission success receipt
     */
    public function success($code): Response
    {
        $letter = Letter::with(['recipientUnit', 'category'])->where('tracking_code', $code)->firstOrFail();

        return Inertia::render('Tracking/Success', [
            'letter' => $letter,
        ]);
    }

    /**
     * Handle direct URL tracking: /tracking/{code}
     */
    public function showByCode(Request $request, string $code): Response
    {
        $request->merge(['code' => $code]);
        return $this->index($request);
    }

    /**
     * Safely stream attachment file in browser tab
     */
    public function viewAttachment(string $path)
    {
        if (empty($path) || $path === '0' || $path === 'null') {
            abort(404, 'Berkas lampiran tidak ditemukan.');
        }

        $cleanPath = ltrim($path, '/');
        if (str_starts_with($cleanPath, 'storage/')) {
            $cleanPath = substr($cleanPath, 8);
        }

        // List of possible file locations to check
        $possiblePaths = [
            storage_path('app/public/' . $cleanPath),
            storage_path('app/' . $cleanPath),
            public_path('storage/' . $cleanPath),
            public_path($cleanPath),
        ];

        // 1. Check in storage public disk
        if (Storage::disk('public')->exists($cleanPath)) {
            $mime = Storage::disk('public')->mimeType($cleanPath) ?: 'application/octet-stream';
            return Storage::disk('public')->response($cleanPath, null, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($cleanPath) . '"',
            ]);
        }

        // 2. Check in storage local/default disk
        if (Storage::disk('local')->exists($cleanPath)) {
            $mime = Storage::disk('local')->mimeType($cleanPath) ?: 'application/octet-stream';
            return Storage::disk('local')->response($cleanPath, null, [
                'Content-Type' => $mime,
                'Content-Disposition' => 'inline; filename="' . basename($cleanPath) . '"',
            ]);
        }

        // 3. Direct filesystem check
        foreach ($possiblePaths as $filePath) {
            if (file_exists($filePath) && is_file($filePath)) {
                $mime = mime_content_type($filePath) ?: 'application/octet-stream';
                return response()->file($filePath, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
                ]);
            }
        }

        abort(404, 'Berkas lampiran tidak ditemukan.');
    }
}

