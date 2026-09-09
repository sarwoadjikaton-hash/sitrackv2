<?php

namespace App\Http\Controllers;

use App\Models\LetterNumber;
use App\Models\LetterNumberType;
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

        $query = LetterNumber::with(['type', 'unit'])
            ->where('number_year', $year)
            ->where('status', 'used');

        if ($selectedWorkbookId > 0) {
            $query->where('type_id', $selectedWorkbookId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ILIKE', '%' . $search . '%')
                    ->orWhere('number_text', 'ILIKE', '%' . $search . '%')
                    ->orWhere('processing_unit_text', 'ILIKE', '%' . $search . '%')
                    ->orWhere('destination', 'ILIKE', '%' . $search . '%')
                    ->orWhere('pdf_content', 'ILIKE', '%' . $search . '%');
            });
        }

        $periodeLabel = "Semua Waktu";

        if ($periode === 'hari') {
            $date = $request->tanggal ?: now()->toDateString();
            $query->whereDate('letter_date', $date);
            $periodeLabel = "Tanggal: " . date('d/m/Y', strtotime($date));
        } elseif ($periode === 'minggu') {
            $start = now()->startOfWeek()->toDateString();
            $end = now()->endOfWeek()->toDateString();
            $query->whereBetween('letter_date', [$start, $end]);
            $periodeLabel = "Minggu Ini";
        } elseif ($periode === 'bulan') {
            $bulan = $request->bulan ?: now()->month;
            $query->whereMonth('letter_date', $bulan);
            $periodeLabel = "Bulan Ke-" . $bulan . " Tahun " . $year;
        }

        // --- FIX: satu titik penentu urutan, sesuai pilihan dropdown sort ---
        $applySort = function ($q) use ($sort) {
            return match ($sort) {
                'number_asc' => $q->orderBy('sequence_number', 'asc'),
                'date_desc' => $q->orderBy('letter_date', 'desc')->orderBy('sequence_number', 'desc'),
                'date_asc' => $q->orderBy('letter_date', 'asc')->orderBy('sequence_number', 'asc'),
                default => $q->orderBy('sequence_number', 'desc'),
            };
        };

        if ($request->has('print')) {
            // Cetak selalu urut nomor naik (sesuai buku register fisik), terlepas dari pilihan sort di layar
            $records = (clone $query)->orderBy('sequence_number', 'asc')->get();
        } else {
            $records = $applySort(clone $query)->paginate(20)->withQueryString();
        }

        $statsBase = LetterNumber::where('number_year', $year);
        if ($selectedWorkbookId > 0) {
            $statsBase->where('type_id', $selectedWorkbookId);
        }

        $statsRow = $statsBase->selectRaw("
        COUNT(*) FILTER (WHERE status = 'used') as used,
        COUNT(*) FILTER (WHERE status = 'available') as available,
        COUNT(*) FILTER (WHERE status = 'reserved') as reserved
    ")->first();

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
            ],
            'stats' => [
                'used' => (int) ($statsRow->used ?? 0),
                'available' => (int) ($statsRow->available ?? 0),
                'reserved' => (int) ($statsRow->reserved ?? 0),
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
            'attachment' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
        ]);

        DB::beginTransaction();
        try {
            $slot = LetterNumber::with('type')->where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($slot->status !== 'used') {
                throw new RuntimeException('Data tidak ditemukan atau belum digunakan.');
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
                if ($attachmentPath) {
                    Storage::disk('public')->delete($attachmentPath);
                }
                $attachmentPath = $request->file('attachment')->store('data-surat', 'public');
                $pdfContent = PdfTextExtractor::extract($attachmentPath);
            }

            $slot->update([
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
            ]);

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

        return Excel::download(
            new DataSuratMultiSheetExport($typeId, $year, $search),
            'laporan-data-surat-' . now()->format('Ymd-His') . '.xlsx'
        );
    }
}
