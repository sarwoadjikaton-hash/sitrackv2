<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use App\Models\Letter;
use App\Models\LetterCategory;
use App\Models\LetterRelation;
use App\Models\LetterStatusLog;
use App\Models\Unit;
use App\Services\LetterNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

class DispositionLetterController extends Controller
{
    /**
     * Allowed statuses for disposition lane
     */
    public static function allowedStatuses(): array
    {
        return [
            'Surat Diterima TU',
            'Diajukan ke Sekjen',
            'Didisposisikan',
            'Diteruskan ke Unit',
            'Dalam Tindak Lanjut',
            'Selesai',
            'Dikembalikan',
        ];
    }

    /**
     * Display disposition lane letters
     */
    public function index(Request $request): Response
    {
        $search = trim((string) $request->input('search', ''));
        $status = trim((string) $request->input('status', ''));
        $source = trim((string) $request->input('source', ''));

        $query = Letter::with(['category', 'recipientUnit'])
            ->withCount('dispositions')
            ->where('process_lane', 'disposition');

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('tracking_code', 'ILIKE', "%{$search}%")
                  ->orWhere('agenda_number', 'ILIKE', "%{$search}%")
                  ->orWhere('letter_number', 'ILIKE', "%{$search}%")
                  ->orWhere('subject', 'ILIKE', "%{$search}%")
                  ->orWhere('sender_name', 'ILIKE', "%{$search}%")
                  ->orWhere('sender_unit', 'ILIKE', "%{$search}%");
            });
        }

        if ($status !== '' && in_array($status, self::allowedStatuses(), true)) {
            $query->where('status', $status);
        }

        if ($source !== '' && in_array($source, ['Manual', 'SRIKANDI'], true)) {
            $query->where('letter_source', $source);
        }

        $letters = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        return Inertia::render('Disposisi/Index', [
            'letters' => $letters,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'source' => $source,
            ],
            'allowedStatuses' => self::allowedStatuses(),
        ]);
    }

    /**
     * Show form to create new disposition letter
     */
    public function create(): Response
    {
        $categories = LetterCategory::where('is_active', true)->orderBy('category_name')->get();
        $units = Unit::where('is_active', true)->orderBy('unit_name')->get();

        return Inertia::render('Disposisi/Create', [
            'categories' => $categories,
            'units' => $units,
            'allowedStatuses' => self::allowedStatuses(),
        ]);
    }

    /**
     * Store new disposition letter
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'letter_number' => ['nullable', 'string', 'max:150'],
            'sender_unit' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'exists:letter_categories,id'],
            'sender_name' => ['required', 'string', 'max:150'],
            'sender_phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:500'],
            'letter_date' => ['nullable', 'date'],
            'received_date' => ['nullable', 'date'],
            'priority' => ['required', 'in:urgent,high,normal,low'],
            'security_level' => ['required', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
            'letter_source' => ['required', 'in:Manual,SRIKANDI'],
            
            // Initial Disposition fields (optional)
            'instruction' => ['nullable', 'string'],
            'to_unit_id' => ['nullable', 'exists:units,id'],
            'to_name' => ['nullable', 'string', 'max:150'],
            'due_date' => ['nullable', 'date'],
            'is_koordinator' => ['nullable', 'boolean'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:20480'],
        ]);

        $trackingCode = LetterNumberService::generateTrackingCode();
        $agendaNumber = LetterNumberService::nextAgendaNumber('in');

        $attachmentPath = null;
        if ($request->hasFile('attachment')) {
            $attachmentPath = $request->file('attachment')->store('letters', 'public');
        }

        $instruction = trim((string) ($validated['instruction'] ?? ''));
        $status = $instruction !== '' ? 'Didisposisikan' : 'Diajukan ke Sekjen';
        
        $toUnit = !empty($validated['to_unit_id']) ? Unit::find($validated['to_unit_id']) : null;
        $position = $instruction !== '' 
            ? ($toUnit?->unit_name ?: ($validated['to_name'] ?: 'Penerima Disposisi'))
            : 'Sekretaris Jenderal';

        $userId = Auth::id();
        $userName = Auth::user()->name ?: Auth::user()->username;

        DB::beginTransaction();
        try {
            $letter = Letter::create([
                'tracking_code' => $trackingCode,
                'agenda_number' => $agendaNumber,
                'letter_number' => $validated['letter_number'] ?? null,
                'letter_type' => 'in',
                'process_lane' => 'disposition',
                'sender_unit' => $validated['sender_unit'],
                'category_id' => $validated['category_id'] ?: null,
                'sender_name' => $validated['sender_name'],
                'sender_phone' => $validated['sender_phone'] ?? null,
                'subject' => $validated['subject'],
                'letter_date' => $validated['letter_date'] ?? null,
                'received_date' => $validated['received_date'] ?? date('Y-m-d'),
                'priority' => $validated['priority'],
                'security_level' => $validated['security_level'],
                'status' => $status,
                'current_position' => $position,
                'notes' => $validated['notes'] ?? null,
                'attachment_path' => $attachmentPath,
                'letter_source' => $validated['letter_source'],
                'created_by' => $userId,
            ]);

            if ($instruction !== '') {
                Disposition::create([
                    'letter_id' => $letter->id,
                    'from_name' => 'Sekretaris Jenderal',
                    'to_unit_id' => $validated['to_unit_id'] ?: null,
                    'to_name' => $validated['to_name'] ?? null,
                    'instruction' => $instruction,
                    'due_date' => $validated['due_date'] ?? null,
                    'status' => 'Didisposisikan',
                    'is_koordinator' => !empty($validated['is_koordinator']),
                    'created_by' => $userId,
                    'disposition_date' => now(),
                ]);
            }

            LetterStatusLog::create([
                'letter_id' => $letter->id,
                'status' => 'Surat Diterima TU',
                'position' => 'TU Sekjen',
                'note' => 'Surat masuk diregistrasi ke Lajur Disposisi.',
                'changed_by' => $userName,
                'changed_at' => now(),
            ]);

            if ($instruction !== '') {
                LetterStatusLog::create([
                    'letter_id' => $letter->id,
                    'status' => $status,
                    'position' => $position,
                    'note' => 'Disposisi awal dicatat: ' . substr($instruction, 0, 100),
                    'changed_by' => $userName,
                    'changed_at' => now()->addSecond(),
                ]);
            }

            DB::commit();

            return redirect()->route('disposisi.show', $letter->id)
                ->with('success', "Surat berhasil dicatat ke Lajur Disposisi dengan agenda: {$agendaNumber}");
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show detailed disposition page with hierarchy tree and timeline
     */
    public function show($id): Response
    {
        $letter = Letter::with(['category', 'recipientUnit', 'creator'])
            ->findOrFail($id);

        $dispositions = Disposition::with(['toUnit', 'creator', 'children.toUnit', 'children.creator'])
            ->where('letter_id', $id)
            ->whereNull('parent_disposition_id')
            ->orderBy('disposition_date', 'asc')
            ->get();

        $statusLogs = LetterStatusLog::where('letter_id', $id)
            ->orderBy('changed_at', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $relations = LetterRelation::with(['sourceLetter', 'targetLetter', 'creator'])
            ->where('source_letter_id', $id)
            ->orWhere('target_letter_id', $id)
            ->orderBy('created_at', 'desc')
            ->get();

        $units = Unit::where('is_active', true)->orderBy('unit_name')->get();

        $otherLetters = Letter::where('id', '!=', $id)
            ->select('id', 'agenda_number', 'tracking_code', 'subject', 'process_lane')
            ->orderBy('id', 'desc')
            ->limit(100)
            ->get();

        return Inertia::render('Disposisi/Detail', [
            'letter' => $letter,
            'dispositions' => $dispositions,
            'statusLogs' => $statusLogs,
            'relations' => $relations,
            'units' => $units,
            'otherLetters' => $otherLetters,
            'allowedStatuses' => self::allowedStatuses(),
        ]);
    }

    /**
     * Add a child or root disposition instruction
     */
    public function addDisposition(Request $request, $id)
    {
        $letter = Letter::findOrFail($id);

        $validated = $request->validate([
            'parent_disposition_id' => ['nullable', 'exists:dispositions,id'],
            'from_name' => ['required', 'string', 'max:150'],
            'to_unit_id' => ['nullable', 'exists:units,id'],
            'to_name' => ['nullable', 'string', 'max:150'],
            'instruction' => ['required', 'string'],
            'due_date' => ['nullable', 'date'],
            'is_koordinator' => ['nullable', 'boolean'],
            'item_status' => ['nullable', 'string'],
        ]);

        $toUnit = !empty($validated['to_unit_id']) ? Unit::find($validated['to_unit_id']) : null;
        $targetName = $toUnit?->unit_name ?: ($validated['to_name'] ?: 'Unit Penerima');

        $userId = Auth::id();
        $userName = Auth::user()->name ?: Auth::user()->username;

        DB::beginTransaction();
        try {
            Disposition::create([
                'letter_id' => $letter->id,
                'parent_disposition_id' => $validated['parent_disposition_id'] ?: null,
                'from_name' => $validated['from_name'],
                'to_unit_id' => $validated['to_unit_id'] ?: null,
                'to_name' => $validated['to_name'] ?? null,
                'instruction' => $validated['instruction'],
                'due_date' => $validated['due_date'] ?? null,
                'status' => $validated['item_status'] ?: 'Didisposisikan',
                'is_koordinator' => !empty($validated['is_koordinator']),
                'created_by' => $userId,
                'disposition_date' => now(),
            ]);

            $letter->update([
                'status' => 'Didisposisikan',
                'current_position' => $targetName,
            ]);

            LetterStatusLog::create([
                'letter_id' => $letter->id,
                'status' => 'Didisposisikan',
                'position' => $targetName,
                'note' => "Disposisi oleh {$validated['from_name']}: " . substr($validated['instruction'], 0, 100),
                'changed_by' => $userName,
                'changed_at' => now(),
            ]);

            DB::commit();

            return back()->with('success', 'Instruksi disposisi berhasil ditambahkan.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update individual disposition follow up progress and check overall resolution
     */
    public function updateDispositionItem(Request $request, $id, $dispositionId)
    {
        $letter = Letter::findOrFail($id);
        $disposition = Disposition::where('letter_id', $id)->findOrFail($dispositionId);

        $validated = $request->validate([
            'status' => ['required', 'in:Didisposisikan,Dalam Tindak Lanjut,Selesai,Dikembalikan'],
            'follow_up_note' => ['nullable', 'string'],
        ]);

        $userName = Auth::user()->name ?: Auth::user()->username;

        DB::beginTransaction();
        try {
            $disposition->update([
                'status' => $validated['status'],
                'follow_up_note' => $validated['follow_up_note'] ?? null,
            ]);

            // Check if all disposition items for this letter are completed
            $uncompletedCount = Disposition::where('letter_id', $id)
                ->where('status', '!=', 'Selesai')
                ->count();

            if ($uncompletedCount === 0) {
                $letter->update([
                    'status' => 'Selesai',
                    'current_position' => 'TU Sekjen',
                ]);

                LetterStatusLog::create([
                    'letter_id' => $letter->id,
                    'status' => 'Selesai',
                    'position' => 'TU Sekjen',
                    'note' => 'Seluruh tindak lanjut disposisi telah tuntas diselesaikan.',
                    'changed_by' => $userName,
                    'changed_at' => now(),
                ]);
            } else {
                $letter->update([
                    'status' => 'Dalam Tindak Lanjut',
                ]);
            }

            DB::commit();

            return back()->with('success', 'Status tindak lanjut disposisi diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Quick update of parent letter status in disposition lane
     */
    public function updateLetterStatus(Request $request, $id)
    {
        $letter = Letter::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string'],
            'current_position' => ['required', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $letter->update([
            'status' => $validated['status'],
            'current_position' => $validated['current_position'],
        ]);

        LetterStatusLog::create([
            'letter_id' => $letter->id,
            'status' => $validated['status'],
            'position' => $validated['current_position'],
            'note' => $validated['note'] ?: 'Status lajur disposisi diperbarui.',
            'changed_by' => Auth::user()->name ?: Auth::user()->username,
            'changed_at' => now(),
        ]);

        return back()->with('success', 'Status lajur disposisi diperbarui.');
    }
}
