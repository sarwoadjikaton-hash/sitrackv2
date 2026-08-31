<?php

namespace App\Http\Controllers;

use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use App\Models\Unit;
use App\Services\LetterNumberService;
use App\Exports\DataSuratExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // --- PERBAIKAN UTAMA: Tambahkan filter status = 'used' ---
        // Laporan data surat hanya menampilkan slot yang sudah terisi data surat.
        $query = LetterNumber::with(['type', 'unit'])
            ->where('number_year', $year)
            ->where('status', 'used'); // <--- PENTING: Hanya tampilkan yang sudah terpakai

        // 1. Filter Workbook
        if ($selectedWorkbookId > 0) {
            $query->where('type_id', $selectedWorkbookId);
        }

        // 2. Filter Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'ILIKE', '%' . $search . '%')
                    ->orWhere('number_text', 'ILIKE', '%' . $search . '%')
                    ->orWhere('processing_unit_text', 'ILIKE', '%' . $search . '%')
                    ->orWhere('destination', 'ILIKE', '%' . $search . '%');
            });
        }

        // 3. Logic Filter Periode
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

        // Gunakan orderBy sequence_number desc agar data terbaru di atas
        $records = $query->orderBy('sequence_number', 'desc')->paginate(20)->withQueryString();

        // 4. Hitung Stats (Harus sinkron dengan tahun yang dipilih)
        $statsBase = LetterNumber::where('number_year', $year);
        if ($selectedWorkbookId > 0) {
            $statsBase->where('type_id', $selectedWorkbookId);
        }

        $statsRow = $statsBase->selectRaw("
            COUNT(*) FILTER (WHERE status = 'used') as used,
            COUNT(*) FILTER (WHERE status = 'available') as available,
            COUNT(*) FILTER (WHERE status = 'reserved') as reserved
        ")->first();

        if ($request->has('print')) {
            $records = $query->orderBy('sequence_number', 'asc')->get(); // Ambil SEMUA data dari urutan 1
        } else {
            $records = $query->orderBy('sequence_number', 'desc')->paginate(20)->withQueryString();
        }

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
        $workbookId = (int) $request->input('workbook', 0);
        $search = trim((string) $request->input('search', ''));

        return Excel::download(
            new DataSuratExport($workbookId, $search),
            'laporan-data-surat-' . now()->format('Ymd-His') . '.xlsx'
        );
    }
}
