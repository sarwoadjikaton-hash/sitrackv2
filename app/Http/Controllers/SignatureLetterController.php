<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterCategory;
use App\Models\LetterStatusLog;
use App\Models\Unit;
use App\Models\LetterNumberType;
use App\Services\LetterNumberService;
use App\Services\PdfTextExtractor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SignatureLetterController extends Controller
{
    /**
     * Allowed statuses for signature lane
     */
    public static function allowedStatuses(): array
    {
        return [
            'Diregistrasi',
            'Diterima',
            'Diperiksa Oleh TU Sekjen',
            'Diperiksa Oleh Kasubag TU Sekjen',
            'Diperiksa Oleh Sekjen',
            'Selesai dan Siap Untuk diambil',
            'Revisi',
            'Ditolak',
            'Dokumen Sudah diambil',
        ];
    }

    /**
     * Display signature lane letters
     */
    public function index(Request $request): Response
    {
        $types = LetterNumberType::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->get();

        $selectedWorkbookId = (int) $request->input('workbook', 0);
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $sort = $request->input('sort', 'date_desc');
        $periode = $request->input('periode', 'all');
        $tanggal = $request->input('tanggal', now()->toDateString());
        $bulan = (int) $request->input('bulan', now()->month);
        $year = (int) $request->input('year', date('Y'));

        $query = Letter::with(['category', 'recipientUnit', 'letterNumberType'])
            ->where('process_lane', 'signature');

        if ($selectedWorkbookId > 0) {
            $query->where('letter_number_type_id', $selectedWorkbookId);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_code', 'ILIKE', "%{$search}%")
                    ->orWhere('agenda_number', 'ILIKE', "%{$search}%")
                    ->orWhere('letter_number', 'ILIKE', "%{$search}%")
                    ->orWhere('subject', 'ILIKE', "%{$search}%")
                    ->orWhere('sender_name', 'ILIKE', "%{$search}%")
                    ->orWhere('sender_unit', 'ILIKE', "%{$search}%")
                    ->orWhere('destination', 'ILIKE', "%{$search}%")
                    ->orWhere('signatory_name', 'ILIKE', "%{$search}%")
                    ->orWhere('technical_officer', 'ILIKE', "%{$search}%")
                    ->orWhere('current_position', 'ILIKE', "%{$search}%")
                    ->orWhere('notes', 'ILIKE', "%{$search}%")
                    ->orWhereHas('recipientUnit', function ($uq) use ($search) {
                        $uq->where('unit_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhereHas('letterNumberType', function ($tq) use ($search) {
                        $tq->where('type_name', 'ILIKE', "%{$search}%")
                            ->orWhere('workbook_name', 'ILIKE', "%{$search}%");
                    })
                    ->orWhere('pdf_content', 'ILIKE', "%{$search}%");
            });
        }

        if ($status !== '' && in_array($status, self::allowedStatuses(), true)) {
            $query->where('status', $status);
        }

        if ($periode === 'hari') {
            $query->where(function ($q) use ($tanggal) {
                $q->whereDate('letter_date', $tanggal)
                    ->orWhereDate('received_date', $tanggal)
                    ->orWhereDate('created_at', $tanggal);
            });
        } elseif ($periode === 'bulan') {
            $query->where(function ($q) use ($bulan, $year) {
                $q->where(function ($sub) use ($bulan, $year) {
                    $sub->whereMonth('letter_date', $bulan)->whereYear('letter_date', $year);
                })->orWhere(function ($sub) use ($bulan, $year) {
                    $sub->whereMonth('received_date', $bulan)->whereYear('received_date', $year);
                })->orWhere(function ($sub) use ($bulan, $year) {
                    $sub->whereMonth('created_at', $bulan)->whereYear('created_at', $year);
                });
            });
        }

        match ($sort) {
            'number_asc' => $query->orderBy('agenda_number', 'asc')->orderBy('id', 'asc'),
            'number_desc' => $query->orderBy('agenda_number', 'desc')->orderBy('id', 'desc'),
            'date_asc' => $query->orderBy('letter_date', 'asc')->orderBy('id', 'asc'),
            default => $query->orderBy('letter_date', 'desc')->orderBy('id', 'desc'),
        };

        $letters = $query->paginate(15)->withQueryString();

        return Inertia::render('TindakLanjut/Index', [
            'letters' => $letters,
            'types' => $types,
            'selectedWorkbookId' => $selectedWorkbookId,
            'filters' => [
                'workbook' => $selectedWorkbookId,
                'search' => $search,
                'status' => $status,
                'sort' => $sort,
                'periode' => $periode,
                'tanggal' => $tanggal,
                'bulan' => $bulan,
                'year' => $year,
            ],
            'allowedStatuses' => self::allowedStatuses(),
        ]);
    }

    /**
     * Show form to create new signature letter
     */
    public function create(): Response
    {
        $categories = LetterCategory::where('is_active', true)->orderBy('category_name')->get();
        $units = Unit::where('is_active', true)->orderBy('unit_name')->get();
        $letterNumberTypes = LetterNumberType::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('type_name')
            ->get();

        return Inertia::render('TindakLanjut/Form', [
            'letter' => null,
            'categories' => $categories,
            'units' => $units,
            'letterNumberTypes' => $letterNumberTypes,
            'allowedStatuses' => self::allowedStatuses(),
        ]);
    }

    /**
     * Store new signature letter
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'letter_number' => ['nullable', 'string', 'max:150'],
            'slot_id' => ['nullable', 'exists:letter_numbers,id'],
            'letter_type' => ['nullable', 'in:in,out'],
            'sender_unit' => ['nullable', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:letter_categories,id'],
            'letter_number_type_id' => ['nullable', 'exists:letter_number_types,id'],
            'sender_name' => ['required', 'string', 'max:150'],
            'sender_phone' => ['nullable', 'string', 'max:50'],
            'destination' => ['nullable', 'string', 'max:255'],
            'recipient_unit_id' => ['nullable'],
            'subject' => ['required', 'string', 'max:500'],
            'letter_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:50'],
            'security_level' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string'],
            'current_position' => ['required', 'string', 'max:150'],
            'requested_actions' => ['nullable'],
            'notes' => ['nullable', 'string'],
            'letter_source' => ['nullable', 'string', 'max:50'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:20480'],
        ]);

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentPath = $file->store('letters', 'public');
        }

        $actions = is_array($validated['requested_actions'] ?? null)
            ? implode(', ', $validated['requested_actions'])
            : ($validated['requested_actions'] ?? null);

        $letterType = $validated['letter_type'] ?? 'in';

        DB::beginTransaction();
        try {
            $letterNumberType = !empty($validated['letter_number_type_id'])
                ? LetterNumberType::find($validated['letter_number_type_id'])
                : null;

            if ($validated['letter_source'] === 'SRIKANDI') {
                $trackingCode = null;
                $agendaNumber = null;
            } else {
                $trackingCode = LetterNumberService::generateTrackingCode($letterNumberType?->type_code);
                $agendaNumber = LetterNumberService::nextAgendaNumber($letterType);
            }

            $letter = Letter::create([
                'tracking_code' => $trackingCode,
                'agenda_number' => $agendaNumber,
                'letter_number' => $validated['letter_number'] ?? null,
                'letter_type' => $letterType,
                'process_lane' => 'signature',
                'sender_unit' => $validated['sender_unit'] ?? null,
                'category_id' => $validated['category_id'] ?: null,
                'letter_number_type_id' => $letterNumberType?->id,
                'sender_name' => $validated['sender_name'],
                'sender_phone' => $validated['sender_phone'] ?? null,
                'destination' => $validated['destination'] ?? null,
                'recipient_unit_id' => $validated['recipient_unit_id'] ?: (
                    !empty($validated['destination'])
                        ? Unit::where('unit_name', 'ILIKE', trim($validated['destination']))->value('id')
                        : null
                ),
                'subject' => $validated['subject'],
                'letter_date' => $validated['letter_date'] ?? null,
                'received_date' => $validated['received_date'] ?? date('Y-m-d'),
                'priority' => $validated['priority'],
                'security_level' => $validated['security_level'],
                'status' => $validated['status'],
                'current_position' => $validated['current_position'],
                'requested_actions' => $actions,
                'notes' => $validated['notes'] ?? null,
                'attachment_path' => $attachmentPath,
                'letter_source' => $validated['letter_source'],
                'created_by' => Auth::id(),
            ]);

            if (!empty($validated['slot_id'])) {
                $slot = LetterNumber::find($validated['slot_id']);
                if ($slot) {
                    $slot->update([
                        'status' => 'used',
                        'used_at' => now(),
                        'linked_letter_id' => $letter->id,
                        'number_text' => $letter->letter_number,
                        'subject' => $letter->subject,
                        'letter_date' => $letter->letter_date ?? date('Y-m-d'),
                        'incoming_date' => $letter->received_date ?? date('Y-m-d'),
                        'processing_unit_text' => $letter->sender_unit,
                        'unit_id' => $letter->recipient_unit_id,
                    ]);
                }
            }

            LetterStatusLog::create([
                'letter_id' => $letter->id,
                'status' => $letter->status,
                'position' => $letter->current_position,
                'note' => 'Dokumen baru dicatat ke sistem SiTrack.',
                'attachment_path' => $attachmentPath,
                'attachment_name' => $request->hasFile('attachment') ? $request->file('attachment')->getClientOriginalName() : null,
                'changed_by' => Auth::user()->name ?: Auth::user()->username,
                'changed_at' => now(),
            ]);

            DB::commit();

            if (!empty($letter->sender_phone)) {
                try {
                    \App\Services\WhatsAppService::sendSubmissionSuccess($letter);
                } catch (\Throwable $we) {
                    \Illuminate\Support\Facades\Log::warning('[WhatsApp] Failed to dispatch signature store notification: ' . $we->getMessage());
                }
            }

            return redirect()->route('tindak-lanjut.index')
                ->with('success', "Surat berhasil dicatat dengan nomor agenda: {$agendaNumber}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show form to edit signature letter
     */
    public function edit($id): Response
    {
        $letter = Letter::findOrFail($id);
        $categories = LetterCategory::where('is_active', true)->orderBy('category_name')->get();
        $units = Unit::where('is_active', true)->orderBy('unit_name')->get();
        $letterNumberTypes = LetterNumberType::where('is_active', true)
            ->orderBy('display_order')
            ->orderBy('type_name')
            ->get();

        return Inertia::render('TindakLanjut/Form', [
            'letter' => $letter,
            'categories' => $categories,
            'units' => $units,
            'letterNumberTypes' => $letterNumberTypes,
            'allowedStatuses' => self::allowedStatuses(),
        ]);
    }

    /**
     * Update signature letter
     */
    public function update(Request $request, $id)
    {
        $letter = Letter::findOrFail($id);

        $validated = $request->validate([
            'letter_number' => ['nullable', 'string', 'max:150'],
            'slot_id' => ['nullable', 'exists:letter_numbers,id'],
            'sender_unit' => ['nullable', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:letter_categories,id'],
            'letter_number_type_id' => ['nullable', 'exists:letter_number_types,id'],
            'sender_name' => ['required', 'string', 'max:150'],
            'sender_phone' => ['nullable', 'string', 'max:50'],
            'destination' => ['nullable', 'string', 'max:255'],
            'recipient_unit_id' => ['nullable'],
            'subject' => ['required', 'string', 'max:500'],
            'letter_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'priority' => ['nullable', 'string', 'max:50'],
            'security_level' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'string'],
            'current_position' => ['required', 'string', 'max:150'],
            'requested_actions' => ['nullable'],
            'notes' => ['nullable', 'string'],
            'letter_source' => ['nullable', 'string', 'max:50'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:20480'],
        ]);

        $attachmentPath = $letter->attachment_path;
        $pdfContent = $letter->pdf_content;
        $uploadedAttachmentName = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('letters', 'public');
            $uploadedAttachmentName = $request->file('attachment')->getClientOriginalName();
            $pdfContent = PdfTextExtractor::extract($attachmentPath);
        }

        $actions = is_array($validated['requested_actions'] ?? null)
            ? implode(', ', $validated['requested_actions'])
            : ($validated['requested_actions'] ?? null);

        $statusChanged = ($letter->status !== $validated['status'] || $letter->current_position !== $validated['current_position']);

        $letter->update([
            'letter_number' => $validated['letter_number'] ?? null,
            'sender_unit' => $validated['sender_unit'] ?? null,
            'category_id' => $validated['category_id'] ?: null,
            'letter_number_type_id' => $validated['letter_number_type_id'] ?: null,
            'sender_name' => $validated['sender_name'],
            'sender_phone' => $validated['sender_phone'] ?? null,
            'destination' => $validated['destination'] ?? null,
            'recipient_unit_id' => $validated['recipient_unit_id'] ?: (
                !empty($validated['destination'])
                    ? Unit::where('unit_name', 'ILIKE', trim($validated['destination']))->value('id')
                    : null
            ),
            'subject' => $validated['subject'],
            'letter_date' => $validated['letter_date'] ?? null,
            'received_date' => $validated['received_date'] ?? null,
            'priority' => $validated['priority'] ?? 'Biasa',
            'security_level' => $validated['security_level'] ?? 'Biasa',
            'status' => $validated['status'],
            'current_position' => $validated['current_position'],
            'requested_actions' => $actions,
            'notes' => $validated['notes'] ?? null,
            'attachment_path' => $attachmentPath,
            'letter_source' => $validated['letter_source'] ?? $letter->letter_source ?? 'Manual',
        ]);

        if (!empty($validated['slot_id'])) {
            $slot = LetterNumber::find($validated['slot_id']);
            if ($slot) {
                $slot->update([
                    'status' => 'used',
                    'used_at' => now(),
                    'linked_letter_id' => $letter->id,
                    'number_text' => $letter->letter_number,
                    'subject' => $letter->subject,
                    'letter_date' => $letter->letter_date ?? date('Y-m-d'),
                    'incoming_date' => $letter->received_date ?? date('Y-m-d'),
                    'processing_unit_text' => $letter->sender_unit,
                    'destination' => $letter->destination ?? null,
                    'unit_id' => $letter->recipient_unit_id,
                ]);
            }
        }

        if ($statusChanged || $request->hasFile('attachment')) {
            LetterStatusLog::create([
                'letter_id' => $letter->id,
                'status' => $letter->status,
                'position' => $letter->current_position,
                'note' => $request->hasFile('attachment') ? 'Pembaruan data surat dan unggah berkas naskah baru.' : 'Pembaruan data surat dan status operasional.',
                'attachment_path' => $uploadedAttachmentName ? $attachmentPath : null,
                'attachment_name' => $uploadedAttachmentName,
                'changed_by' => Auth::user()->name ?: Auth::user()->username,
                'changed_at' => now(),
            ]);

            if ($statusChanged && !empty($letter->sender_phone)) {
                try {
                    \App\Services\WhatsAppService::sendStatusUpdate($letter, $letter->notes);
                } catch (\Throwable $we) {
                    \Illuminate\Support\Facades\Log::warning('[WhatsApp] Failed to dispatch status update notification: ' . $we->getMessage());
                }
            }
        }

        return redirect()->route('tindak-lanjut.index')
            ->with('success', 'Data surat berhasil diperbarui.');
    }

    /**
     * Quick status update for signature letter
     */
    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'current_position' => ['required', 'string', 'max:150'],
            'requested_actions' => ['nullable'],
            'note' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:20480'],
        ]);

        $letter = Letter::findOrFail($id);

        $attachmentPath = $letter->attachment_path;
        $pdfContent = $letter->pdf_content;
        $uploadedAttachmentName = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('letters', 'public');
            $uploadedAttachmentName = $request->file('attachment')->getClientOriginalName();
            $pdfContent = PdfTextExtractor::extract($attachmentPath);
        }

        $actions = is_array($validated['requested_actions'] ?? null)
            ? implode(', ', $validated['requested_actions'])
            : ($validated['requested_actions'] ?? $letter->requested_actions);

        $letter->update([
            'status' => $validated['status'],
            'current_position' => $validated['current_position'],
            'requested_actions' => $actions,
            'attachment_path' => $attachmentPath,
            'pdf_content' => $pdfContent,
        ]);

        LetterStatusLog::create([
            'letter_id' => $letter->id,
            'status' => $validated['status'],
            'position' => $validated['current_position'],
            'note' => $validated['note'] ?: ($request->hasFile('attachment') ? 'Pembaruan status dokumen dan berkas lampiran.' : 'Pembaruan status dokumen.'),
            'attachment_path' => $uploadedAttachmentName ? $attachmentPath : null,
            'attachment_name' => $uploadedAttachmentName,
            'changed_by' => Auth::user()->name ?: Auth::user()->username,
            'changed_at' => now(),
        ]);

        if (!empty($letter->sender_phone)) {
            try {
                \App\Services\WhatsAppService::sendStatusUpdate($letter, $validated['note'] ?? null);
            } catch (\Throwable $we) {
                \Illuminate\Support\Facades\Log::warning('[WhatsApp] Failed to dispatch quick status update: ' . $we->getMessage());
            }
        }

        return back()->with('success', 'Status surat berhasil diperbarui.');
    }

    /**
     * Delete a signature letter
     */
    public function destroy($id)
    {
        $letter = Letter::findOrFail($id);

        if ($letter->attachment_path) {
            Storage::disk('public')->delete($letter->attachment_path);
        }

        $letter->delete();

        return redirect()->route('tindak-lanjut.index')
            ->with('success', 'Surat berhasil dihapus.');
    }
}
