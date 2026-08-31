<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterCategory;
use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use App\Models\LetterStatusLog;
use App\Models\Unit;
use App\Services\LetterNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $code = strtoupper(trim((string) $request->input('code', '')));
        $letter = null;
        $logs = [];
        $dispositions = [];
        $progress = 0;

        if ($code !== '') {
            $letter = Letter::with(['category', 'recipientUnit'])
                ->where('tracking_code', $code)
                ->orWhere('agenda_number', $code)
                ->orWhere('letter_number', $code)
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
            'recipient_unit_id' => ['nullable', 'exists:units,id'],
            'category_id' => ['nullable', 'exists:letter_categories,id'],
            'subject' => ['required', 'string', 'max:500'],
            'letter_date' => ['nullable', 'date'],
            'letter_number_id' => ['required', 'exists:letter_numbers,id'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::beginTransaction();
        try {
            /** @var LetterNumber $slot */
            $slot = LetterNumber::with('type')->where('id', $validated['letter_number_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if (!in_array($slot->status, ['available', 'reserved'])) {
                throw new RuntimeException('Nomor surat sudah digunakan orang lain. Silakan pilih nomor yang lain.');
            }

            $type = $slot->type;
            $monthNumber = (int) date('n');
            $signerCode = $type->default_signer_code ?: '1';

            $numberText = LetterNumberService::buildNumberText($type, [
                'sequence_number' => $slot->sequence_number,
                'number_year' => $slot->number_year,
                'security_access' => 'B',
                'signer_code' => $signerCode,
                'classification_code' => 'UM.01',
                'month_number' => $monthNumber,
            ]);

            $trackingCode = LetterNumberService::generateTrackingCode();
            $agendaNumber = LetterNumberService::nextAgendaNumber('in');

            $letter = Letter::create([
                'tracking_code' => $trackingCode,
                'agenda_number' => $agendaNumber,
                'letter_number' => $numberText,
                'letter_type' => 'in',
                'letter_source' => 'Manual',
                'process_lane' => 'signature',
                'category_id' => $validated['category_id'] ?: null,
                'sender_name' => $validated['sender_name'],
                'sender_phone' => $validated['sender_phone'],
                'recipient_unit_id' => $validated['recipient_unit_id'] ?: null,
                'subject' => $validated['subject'],
                'letter_date' => $validated['letter_date'] ?? null,
                'received_date' => date('Y-m-d'),
                'status' => 'Dokumen Diterima dan Diinput',
                'priority' => 'normal',
                'security_level' => 'Biasa',
                'current_position' => 'Tata Usaha',
                'notes' => $validated['notes'] ?? null,
            ]);

            $slot->update([
                'status' => 'used',
                'used_at' => now(),
                'linked_letter_id' => $letter->id,
                'subject' => $validated['subject'],
                'letter_date' => $validated['letter_date'] ?? null,
                'incoming_date' => date('Y-m-d'),
                'processing_unit_text' => $validated['sender_name'],
                'unit_id' => $validated['recipient_unit_id'] ?: null,
                'number_text' => $numberText,
            ]);

            LetterStatusLog::create([
                'letter_id' => $letter->id,
                'status' => 'Dokumen Diterima dan Diinput',
                'position' => 'Tata Usaha',
                'note' => 'Pengajuan dokumen mandiri via portal publik SiTrack.',
                'changed_by' => 'Publik',
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
}
