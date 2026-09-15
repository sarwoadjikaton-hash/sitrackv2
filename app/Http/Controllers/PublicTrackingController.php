<?php

namespace App\Http\Controllers;

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
                    'Pengajuan Berhasil' => 15,
                    'Dokumen Diterima dan Diinput' => 15,
                    'Diperiksa Arsiparis' => 35,
                    'Paraf Pengendalian Administrasi (KtusSAMSKM)' => 55,
                    'Proses Paraf/TTD Sekjen' => 75,
                    'Surat Selesai di Paraf/TTD dan bisa diambil' => 90,
                    'Dokumen Sudah diambil' => 100,

                    'Surat Diterima TU' => 20,
                    'Diajukan ke Sekjen' => 45,
                    'Didisposisikan' => 65,
                    'Diteruskan ke Unit' => 80,
                    'Dalam Tindak Lanjut' => 85,
                    'Selesai' => 100,

                    'Revisi' => 40,
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

            DB::commit();

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
}
