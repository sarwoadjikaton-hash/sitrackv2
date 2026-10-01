<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use App\Models\LetterStatusLog;
use App\Models\Unit;
use App\Services\LetterNumberService;
use App\Exports\DataSuratExport;
use App\Exports\DataSuratMultiSheetExport;
use App\Services\PdfTextExtractor;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class DataSuratController extends Controller
{
    /**
     * Display Data Surat list with workbook filter
     */
    public function index(Request $request): Response
    {
        $types = LetterNumberType::where('is_active', true)
            ->orderBy('display_order', 'asc')
            ->get();

        $units = Unit::where('is_active', true)
            ->orderBy('unit_name', 'asc')
            ->get();

        $selectedWorkbookId = (int) $request->input('workbook', 0);
        $search = trim((string) $request->input('search', ''));
        $year = (int) $request->input('year', date('Y'));
        $periode = $request->input('periode', 'all');
        $sort = $request->input('sort', 'number_desc');
        $statusFilter = $request->input('status', 'all'); // 'all', 'with_number', 'without_number', 'reserved'

        $periodeLabel = "Semua Waktu";
        $date = $request->tanggal ?: now()->toDateString();
        $bulan = $request->bulan ?: now()->month;

        if ($periode === 'hari') {
            $periodeLabel = "Tanggal: " . date('d/m/Y', strtotime($date));
        } elseif ($periode === 'minggu') {
            $periodeLabel = "Minggu Ini";
        } elseif ($periode === 'bulan') {
            $periodeLabel = "Bulan Ke-" . $bulan . " Tahun " . $year;
        }

        $items = collect();

        // 1. Ambil Data Surat yang Bernomor / Reservasi (dari LetterNumber) jika filter 'all', 'with_number', atau 'reserved'
        if (in_array($statusFilter, ['all', 'with_number', 'used', 'reserved'])) {
            $lnQuery = LetterNumber::with(['type', 'unit', 'letter'])
                ->where('number_year', $year);

            if ($statusFilter === 'with_number' || $statusFilter === 'used') {
                $lnQuery->where('status', 'used');
            } elseif ($statusFilter === 'reserved') {
                $lnQuery->whereIn('status', ['reserved', 'preorder']);
            } else {
                $lnQuery->whereIn('status', ['used', 'reserved', 'preorder']);
            }

            if ($selectedWorkbookId > 0) {
                $lnQuery->where('type_id', $selectedWorkbookId);
            }

            if ($search) {
                $lnQuery->where(function ($q) use ($search) {
                    $q->where('subject', 'ILIKE', '%' . $search . '%')
                        ->orWhere('number_text', 'ILIKE', '%' . $search . '%')
                        ->orWhere('processing_unit_text', 'ILIKE', '%' . $search . '%')
                        ->orWhere('destination', 'ILIKE', '%' . $search . '%')
                        ->orWhere('signatory', 'ILIKE', '%' . $search . '%')
                        ->orWhere('technical_officer', 'ILIKE', '%' . $search . '%')
                        ->orWhere('reserved_for', 'ILIKE', '%' . $search . '%')
                        ->orWhereHas('unit', fn($uq) => $uq->where('unit_name', 'ILIKE', '%' . $search . '%'))
                        ->orWhereHas('letter', function ($lq) use ($search) {
                            $lq->where('sender_name', 'ILIKE', '%' . $search . '%')
                                ->orWhere('sender_unit', 'ILIKE', '%' . $search . '%')
                                ->orWhere('tracking_code', 'ILIKE', '%' . $search . '%');
                        })
                        ->orWhere('pdf_content', 'ILIKE', '%' . $search . '%');
                });
            }

            if ($periode === 'hari') {
                $lnQuery->whereDate('letter_date', $date);
            } elseif ($periode === 'minggu') {
                $start = now()->startOfWeek()->toDateString();
                $end = now()->endOfWeek()->toDateString();
                $lnQuery->whereBetween('letter_date', [$start, $end]);
            } elseif ($periode === 'bulan') {
                $lnQuery->whereMonth('letter_date', $bulan);
            }

            $lnRecords = $lnQuery->get()->map(function ($ln) {
                return (object) [
                    'id' => $ln->id,
                    'actual_id' => $ln->id,
                    'letter_id' => $ln->linked_letter_id,
                    'is_unnumbered' => false,
                    'type_id' => $ln->type_id,
                    'number_year' => $ln->number_year,
                    'sequence_number' => $ln->sequence_number,
                    'status' => $ln->status,
                    'signer_code' => $ln->signer_code,
                    'security_access' => $ln->security_access,
                    'classification_code' => $ln->classification_code,
                    'month_number' => $ln->month_number,
                    'number_text' => $ln->number_text,
                    'incoming_date' => $ln->incoming_date ? $ln->incoming_date->toDateString() : null,
                    'unit_id' => $ln->unit_id,
                    'processing_unit_text' => $ln->processing_unit_text,
                    'signatory' => $ln->signatory,
                    'request_type' => $ln->request_type,
                    'destination' => $ln->destination,
                    'letter_date' => $ln->letter_date ? $ln->letter_date->toDateString() : null,
                    'subject' => $ln->subject,
                    'technical_officer' => $ln->technical_officer,
                    'scan_result' => $ln->scan_result,
                    'nd_pengantar' => $ln->nd_pengantar,
                    'attachment_path' => $ln->attachment_path,
                    'pdf_content' => $ln->pdf_content,
                    'type' => $ln->type,
                    'unit' => $ln->unit,
                    'created_at' => $ln->created_at,
                    'tracking_code' => $ln->letter?->tracking_code,
                    'agenda_number' => $ln->letter?->agenda_number,
                ];
            });

            $items = $items->concat($lnRecords);
        }

        // 2. Ambil Surat yang Belum Diberi Nomor (dari tabel Letter) jika filter 'all' atau 'without_number'
        if (in_array($statusFilter, ['all', 'without_number', 'no_number'])) {
            $unQuery = Letter::with(['letterNumberType', 'recipientUnit', 'category'])
                ->where(function ($q) {
                    $q->whereNull('letter_number')->orWhere('letter_number', '');
                })
                ->where(function ($q) use ($year) {
                    $q->whereYear('received_date', $year)
                      ->orWhere(function ($sub) use ($year) {
                          $sub->whereNull('received_date')->whereYear('created_at', $year);
                      });
                });

            if ($selectedWorkbookId > 0) {
                $unQuery->where('letter_number_type_id', $selectedWorkbookId);
            }

            if ($search) {
                $unQuery->where(function ($q) use ($search) {
                    $q->where('subject', 'ILIKE', '%' . $search . '%')
                        ->orWhere('sender_unit', 'ILIKE', '%' . $search . '%')
                        ->orWhere('sender_name', 'ILIKE', '%' . $search . '%')
                        ->orWhere('destination', 'ILIKE', '%' . $search . '%')
                        ->orWhere('tracking_code', 'ILIKE', '%' . $search . '%')
                        ->orWhere('agenda_number', 'ILIKE', '%' . $search . '%')
                        ->orWhere('signatory_name', 'ILIKE', '%' . $search . '%')
                        ->orWhere('technical_officer', 'ILIKE', '%' . $search . '%')
                        ->orWhere('pdf_content', 'ILIKE', '%' . $search . '%');
                });
            }

            if ($periode === 'hari') {
                $unQuery->whereDate('received_date', $date);
            } elseif ($periode === 'minggu') {
                $start = now()->startOfWeek()->toDateString();
                $end = now()->endOfWeek()->toDateString();
                $unQuery->whereBetween('received_date', [$start, $end]);
            } elseif ($periode === 'bulan') {
                $unQuery->whereMonth('received_date', $bulan);
            }

            $unRecords = $unQuery->get()->map(function ($l) use ($year) {
                return (object) [
                    'id' => 'letter_' . $l->id,
                    'actual_id' => $l->id,
                    'letter_id' => $l->id,
                    'is_unnumbered' => true,
                    'type_id' => $l->letter_number_type_id,
                    'number_year' => $l->received_date ? $l->received_date->year : ($l->created_at ? $l->created_at->year : $year),
                    'sequence_number' => null,
                    'status' => 'without_number',
                    'signer_code' => '1',
                    'security_access' => $l->security_level ?: 'B',
                    'classification_code' => $l->archive_classification_code ?: 'UM.01',
                    'month_number' => $l->received_date ? $l->received_date->month : ($l->letter_date ? $l->letter_date->month : null),
                    'number_text' => null,
                    'incoming_date' => $l->received_date ? $l->received_date->toDateString() : ($l->created_at ? $l->created_at->toDateString() : null),
                    'unit_id' => $l->recipient_unit_id,
                    'processing_unit_text' => $l->sender_unit ?: $l->sender_name ?: '-',
                    'signatory' => $l->signatory_name ?: $l->sender_name ?: '-',
                    'request_type' => $l->requested_actions ?: ($l->priority ?: 'Biasa'),
                    'destination' => $l->destination ?: ($l->recipientUnit?->unit_name ?: '-'),
                    'letter_date' => $l->letter_date ? $l->letter_date->toDateString() : null,
                    'subject' => $l->subject,
                    'technical_officer' => $l->technical_officer,
                    'scan_result' => $l->status ?: 'Proses',
                    'nd_pengantar' => $l->cover_letter_number ?: $l->agenda_number ?: $l->tracking_code,
                    'attachment_path' => $l->attachment_path,
                    'pdf_content' => $l->pdf_content,
                    'type' => $l->letterNumberType,
                    'unit' => $l->recipientUnit,
                    'created_at' => $l->created_at,
                    'tracking_code' => $l->tracking_code,
                    'agenda_number' => $l->agenda_number,
                ];
            });

            $items = $items->concat($unRecords);
        }

        // --- SORTING LOGIC ---
        if ($sort === 'number_asc') {
            $sortedItems = $items->sort(function ($a, $b) {
                if ($a->sequence_number !== null && $b->sequence_number !== null) {
                    return $a->sequence_number <=> $b->sequence_number;
                }
                return ($a->sequence_number !== null) ? -1 : 1;
            })->values();
        } elseif ($sort === 'date_desc') {
            $sortedItems = $items->sortByDesc(function ($item) {
                return $item->letter_date ?: $item->incoming_date ?: '1970-01-01';
            })->values();
        } elseif ($sort === 'date_asc') {
            $sortedItems = $items->sortBy(function ($item) {
                return $item->letter_date ?: $item->incoming_date ?: '9999-12-31';
            })->values();
        } else {
            // number_desc (default)
            $sortedItems = $items->sort(function ($a, $b) {
                if ($a->sequence_number !== null && $b->sequence_number !== null) {
                    return $b->sequence_number <=> $a->sequence_number;
                }
                if ($a->sequence_number !== null) return -1;
                if ($b->sequence_number !== null) return 1;
                return strcmp($b->incoming_date ?? '', $a->incoming_date ?? '');
            })->values();
        }

        // --- PAGINATION / PRINT ---
        if ($request->has('print')) {
            $records = $sortedItems;
        } else {
            $page = (int) $request->input('page', 1);
            $perPage = 20;
            $records = new \Illuminate\Pagination\LengthAwarePaginator(
                $sortedItems->forPage($page, $perPage)->values(),
                $sortedItems->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        // --- STATS BASE ---
        $statsBase = LetterNumber::where('number_year', $year);
        if ($selectedWorkbookId > 0) {
            $statsBase->where('type_id', $selectedWorkbookId);
        }

        $statsRow = $statsBase->selectRaw("
            COUNT(*) FILTER (WHERE status = 'used') as used,
            COUNT(*) FILTER (WHERE status = 'available') as available,
            COUNT(*) FILTER (WHERE status = 'reserved' OR status = 'preorder') as reserved
        ")->first();

        $statsWithoutNumber = Letter::where(function ($q) {
                $q->whereNull('letter_number')->orWhere('letter_number', '');
            })
            ->where(function ($q) use ($year) {
                $q->whereYear('received_date', $year)
                  ->orWhere(function ($sub) use ($year) {
                      $sub->whereNull('received_date')->whereYear('created_at', $year);
                  });
            })
            ->when($selectedWorkbookId > 0, fn($q) => $q->where('letter_number_type_id', $selectedWorkbookId))
            ->count();

        return Inertia::render('DataSurat/Index', [
            'records' => $records,
            'isPrintMode' => $request->has('print'),
            'periodeLabel' => $periodeLabel,
            'types' => $types,
            'units' => $units,
            'selectedWorkbookId' => $selectedWorkbookId,
            'selectedYear' => $year,
            'filters' => [
                'workbook' => $selectedWorkbookId,
                'search' => $search,
                'year' => $year,
                'periode' => $periode,
                'sort' => $sort,
                'status' => $statusFilter,
            ],
            'stats' => [
                'used' => (int) ($statsRow->used ?? 0),
                'available' => (int) ($statsRow->available ?? 0),
                'reserved' => (int) ($statsRow->reserved ?? 0),
                'without_number' => $statsWithoutNumber,
            ],
        ]);
    }

    /**
     * Fetch available/reserved slots for a workbook type and year
     */
    public function getSlots(Request $request): JsonResponse
    {
        $typeId = (int) $request->input('type_id', 0);
        $year = (int) $request->input('year', date('Y'));

        if ($typeId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Pilih jenis naskah.', 'items' => []]);
        }

        $slots = LetterNumber::with('type')
            ->where('type_id', $typeId)
            ->where('number_year', $year)
            ->whereIn('status', ['available', 'reserved'])
            ->orderBy('sequence_number', 'asc')
            ->limit(2000)
            ->get();

        return response()->json(['ok' => true, 'items' => $slots]);
    }

    /**
     * Store final letter data into a slot
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'letter_number_id' => ['required', 'exists:letter_numbers,id'],
            'incoming_date' => ['required', 'date'],
            'letter_date' => ['required', 'date'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'processing_unit_text' => ['required', 'string', 'max:150'],
            'signatory' => ['required', 'string', 'max:150'],
            'request_type' => ['required', 'string', 'max:100'],
            'destination' => ['nullable', 'string', 'max:255'],
            'security_access' => ['nullable', 'string', 'max:20'],
            'classification_code' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:500'],
            'technical_officer' => ['nullable', 'string', 'max:150'],
            'scan_result' => ['nullable', 'string', 'max:255'],
            'nd_pengantar' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        DB::beginTransaction();
        try {
            /** @var LetterNumber $slot */
            $slot = LetterNumber::with('type')->where('id', $validated['letter_number_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($slot->status, ['available', 'reserved'])) {
                throw new RuntimeException('Nomor urut tersebut sudah tidak tersedia.');
            }

            $type = $slot->type;
            $letterDate = $validated['letter_date'];
            $monthNumber = (int) date('n', strtotime($letterDate));
            $signerCode = $type->default_signer_code ?: '1';
            $securityAccess = strtoupper($validated['security_access'] ?? '');
            $classificationCode = strtoupper($validated['classification_code']);

            $numberText = LetterNumberService::buildNumberText($type, [
                'sequence_number' => $slot->sequence_number,
                'number_year' => $slot->number_year,
                'security_access' => $securityAccess,
                'signer_code' => $signerCode,
                'classification_code' => $classificationCode,
                'month_number' => $monthNumber,
            ]);

            if (empty($numberText)) {
                throw new RuntimeException('Format penomoran belum valid.');
            }

            $scanResult = ($type->extra_field === 'nd_pengantar') ? null : ($validated['scan_result'] ?? null);
            $ndPengantar = ($type->extra_field === 'nd_pengantar') ? ($validated['nd_pengantar'] ?? null) : null;

            $attachmentPath = $slot->attachment_path;
            $pdfContent = $slot->pdf_content;
            if ($request->hasFile('attachment')) {
                if ($attachmentPath) {
                    Storage::disk('public')->delete($attachmentPath);
                }
                $attachmentPath = $request->file('attachment')->store('data-surat', 'public');
                $pdfContent = PdfTextExtractor::extract($attachmentPath);
            }

            $slot->update([
                'status' => 'used',
                'number_text' => $numberText,
                'incoming_date' => $validated['incoming_date'],
                'unit_id' => $validated['unit_id'] ?: null,
                'processing_unit_text' => $validated['processing_unit_text'],
                'signatory' => $validated['signatory'],
                'request_type' => $validated['request_type'],
                'destination' => $validated['destination'] ?? null,
                'letter_date' => $letterDate,
                'security_access' => $securityAccess ?: null,
                'signer_code' => $signerCode,
                'classification_code' => $classificationCode,
                'month_number' => $monthNumber,
                'subject' => $validated['subject'],
                'technical_officer' => $validated['technical_officer'] ?? null,
                'scan_result' => $scanResult,
                'nd_pengantar' => $ndPengantar,
                'attachment_path' => $attachmentPath,
                'pdf_content' => $pdfContent,
                'used_at' => now(),
                'created_by' => $slot->created_by ?: Auth::id(),
            ]);

            if ($slot->linked_letter_id) {
                $linkedLetter = Letter::find($slot->linked_letter_id);
                if ($linkedLetter) {
                    $linkedLetter->update([
                        'letter_number' => $numberText,
                        'sender_unit' => $validated['processing_unit_text'],
                        'sender_name' => $validated['signatory'] ?: $linkedLetter->sender_name,
                        'subject' => $validated['subject'],
                        'letter_date' => $letterDate,
                        'received_date' => $validated['incoming_date'],
                        'archive_classification_code' => $classificationCode,
                        'signatory_name' => $validated['signatory'],
                        'technical_officer' => $validated['technical_officer'] ?? null,
                        'destination' => $validated['destination'] ?? null,
                        'attachment_path' => $attachmentPath ?: $linkedLetter->attachment_path,
                        'pdf_content' => $pdfContent ?: $linkedLetter->pdf_content,
                    ]);
                }
            } else {
                $letter = Letter::create([
                    'tracking_code' => LetterNumberService::generateTrackingCode($type->type_code),
                    'agenda_number' => LetterNumberService::nextAgendaNumber('in'),
                    'letter_number' => $numberText,
                    'letter_number_type_id' => $type->id,
                    'letter_type' => 'in',
                    'process_lane' => 'signature',
                    'sender_unit' => $validated['processing_unit_text'],
                    'sender_name' => $validated['signatory'] ?: 'Arsiparis',
                    'subject' => $validated['subject'],
                    'letter_date' => $letterDate,
                    'received_date' => $validated['incoming_date'],
                    'priority' => 'Biasa',
                    'security_level' => $securityAccess ?: 'Biasa',
                    'status' => 'Dokumen Diterima dan Diinput',
                    'current_position' => 'Arsiparis',
                    'archive_classification_code' => $classificationCode,
                    'signatory_name' => $validated['signatory'],
                    'technical_officer' => $validated['technical_officer'] ?? null,
                    'destination' => $validated['destination'] ?? null,
                    'attachment_path' => $attachmentPath,
                    'pdf_content' => $pdfContent,
                    'letter_source' => 'Manual',
                    'created_by' => Auth::id(),
                ]);

                LetterStatusLog::create([
                    'letter_id' => $letter->id,
                    'status' => 'Dokumen Diterima dan Diinput',
                    'position' => 'Arsiparis',
                    'note' => 'Dokumen baru dicatat ke sistem SiTrack dari Data Surat.',
                    'changed_by' => Auth::user()?->name ?: 'Admin',
                    'changed_at' => now(),
                ]);

                $slot->update(['linked_letter_id' => $letter->id]);
            }

            DB::commit();

            return redirect()->route('data-surat.index', ['workbook' => $type->id])
                ->with('success', "Data Surat berhasil dicatat dengan nomor: {$numberText}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update an existing used letter data record
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'incoming_date' => ['required', 'date'],
            'letter_date' => ['required', 'date'],
            'unit_id' => ['nullable', 'exists:units,id'],
            'processing_unit_text' => ['required', 'string', 'max:150'],
            'signatory' => ['required', 'string', 'max:150'],
            'request_type' => ['required', 'string', 'max:100'],
            'destination' => ['nullable', 'string', 'max:255'],
            'security_access' => ['nullable', 'string', 'max:20'],
            'classification_code' => ['required', 'string', 'max:80'],
            'subject' => ['required', 'string', 'max:500'],
            'technical_officer' => ['nullable', 'string', 'max:150'],
            'scan_result' => ['nullable', 'string', 'max:255'],
            'nd_pengantar' => ['nullable', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:20480'],
        ]);

        DB::beginTransaction();
        try {
            $slot = LetterNumber::with('type')->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($slot->status, ['used', 'reserved', 'preorder'])) {
                throw new RuntimeException('Data nomor tidak ditemukan atau belum tersedia untuk diubah.');
            }

            $type = $slot->type;
            $letterDate = $validated['letter_date'];
            $monthNumber = (int) date('n', strtotime($letterDate));
            $signerCode = $slot->signer_code ?: ($type->default_signer_code ?: '1');
            $securityAccess = strtoupper($validated['security_access'] ?? '');
            $classificationCode = strtoupper($validated['classification_code']);

            $numberText = LetterNumberService::buildNumberText($type, [
                'sequence_number' => $slot->sequence_number,
                'number_year' => $slot->number_year,
                'security_access' => $securityAccess,
                'signer_code' => $signerCode,
                'classification_code' => $classificationCode,
                'month_number' => $monthNumber,
            ]);

            $scanResult = ($type->extra_field === 'nd_pengantar') ? null : ($validated['scan_result'] ?? null);
            $ndPengantar = ($type->extra_field === 'nd_pengantar') ? ($validated['nd_pengantar'] ?? null) : null;

            $attachmentPath = $slot->attachment_path;
            $pdfContent = $slot->pdf_content;
            if ($request->hasFile('attachment')) {
                $attachmentPath = $request->file('attachment')->store('data-surat', 'public');
                $pdfContent = PdfTextExtractor::extract($attachmentPath);
            }

            $slot->update([
                'status' => 'used',
                'number_text' => $numberText,
                'incoming_date' => $validated['incoming_date'],
                'unit_id' => $validated['unit_id'] ?: null,
                'processing_unit_text' => $validated['processing_unit_text'],
                'signatory' => $validated['signatory'],
                'request_type' => $validated['request_type'],
                'destination' => $validated['destination'] ?? null,
                'letter_date' => $letterDate,
                'security_access' => $securityAccess ?: null,
                'classification_code' => $classificationCode,
                'month_number' => $monthNumber,
                'subject' => $validated['subject'],
                'technical_officer' => $validated['technical_officer'] ?? null,
                'scan_result' => $scanResult,
                'nd_pengantar' => $ndPengantar,
                'attachment_path' => $attachmentPath,
                'pdf_content' => $pdfContent,
                'used_at' => $slot->used_at ?? now(),
            ]);

            if ($slot->linked_letter_id) {
                $linkedLetter = Letter::find($slot->linked_letter_id);
                if ($linkedLetter) {
                    $linkedLetter->update([
                        'letter_number' => $numberText,
                        'sender_unit' => $validated['processing_unit_text'],
                        'sender_name' => $validated['signatory'] ?: $linkedLetter->sender_name,
                        'subject' => $validated['subject'],
                        'letter_date' => $letterDate,
                        'received_date' => $validated['incoming_date'],
                        'archive_classification_code' => $classificationCode,
                        'signatory_name' => $validated['signatory'],
                        'technical_officer' => $validated['technical_officer'] ?? null,
                        'destination' => $validated['destination'] ?? null,
                        'attachment_path' => $attachmentPath ?: $linkedLetter->attachment_path,
                        'pdf_content' => $pdfContent ?: $linkedLetter->pdf_content,
                    ]);
                }
            } else {
                $letter = Letter::create([
                    'tracking_code' => LetterNumberService::generateTrackingCode($type->type_code),
                    'agenda_number' => LetterNumberService::nextAgendaNumber('in'),
                    'letter_number' => $numberText,
                    'letter_number_type_id' => $type->id,
                    'letter_type' => 'in',
                    'process_lane' => 'signature',
                    'sender_unit' => $validated['processing_unit_text'],
                    'sender_name' => $validated['signatory'] ?: 'Arsiparis',
                    'subject' => $validated['subject'],
                    'letter_date' => $letterDate,
                    'received_date' => $validated['incoming_date'],
                    'priority' => 'Biasa',
                    'security_level' => $securityAccess ?: 'Biasa',
                    'status' => 'Dokumen Diterima dan Diinput',
                    'current_position' => 'Arsiparis',
                    'archive_classification_code' => $classificationCode,
                    'signatory_name' => $validated['signatory'],
                    'technical_officer' => $validated['technical_officer'] ?? null,
                    'destination' => $validated['destination'] ?? null,
                    'attachment_path' => $attachmentPath,
                    'pdf_content' => $pdfContent,
                    'letter_source' => 'Manual',
                    'created_by' => Auth::id(),
                ]);

                LetterStatusLog::create([
                    'letter_id' => $letter->id,
                    'status' => 'Dokumen Diterima dan Diinput',
                    'position' => 'Arsiparis',
                    'note' => 'Dokumen baru dicatat ke sistem SiTrack dari Data Surat.',
                    'changed_by' => Auth::user()?->name ?: 'Admin',
                    'changed_at' => now(),
                ]);

                $slot->update(['linked_letter_id' => $letter->id]);
            }

            DB::commit();

            return redirect()->route('data-surat.index', ['workbook' => $type->id])
                ->with('success', "Data Surat berhasil diperbarui: {$numberText}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function export(Request $request)
    {
        $typeId = (int) $request->input('workbook', 0);
        $year = (int) $request->input('year', date('Y'));
        $search = trim((string) $request->input('search', ''));
        $status = $request->input('status', 'all');

        return Excel::download(
            new DataSuratMultiSheetExport($typeId, $year, $search, $status),
            'laporan-data-surat-' . now()->format('Ymd-His') . '.xlsx'
        );
    }
}
