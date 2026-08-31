<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use App\Models\LetterStatusLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ScanQrController extends Controller
{
    /**
     * Show QR scanner and manual tracking search interface
     */
    public function index(Request $request): Response
    {
        $tracking = trim((string) $request->input('tracking', ''));
        $letter = null;

        if ($tracking !== '') {
            $letter = Letter::with(['category', 'recipientUnit'])
                ->where('tracking_code', $tracking)
                ->where('process_lane', 'signature')
                ->first();
        }

        return Inertia::render('ScanQr/Index', [
            'tracking' => $tracking,
            'letter' => $letter,
            'allowedStatuses' => SignatureLetterController::allowedStatuses(),
        ]);
    }

    /**
     * Update status via QR scanner fast form
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'tracking_code' => ['required', 'string'],
            'status' => ['required', 'string'],
            'current_position' => ['required', 'string', 'max:150'],
            'requested_actions' => ['nullable', 'array'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $letter = Letter::where('tracking_code', $validated['tracking_code'])
            ->where('process_lane', 'signature')
            ->firstOrFail();

        $actions = !empty($validated['requested_actions'])
            ? implode(', ', $validated['requested_actions'])
            : $letter->requested_actions;

        $letter->update([
            'status' => $validated['status'],
            'current_position' => $validated['current_position'],
            'requested_actions' => $actions,
        ]);

        LetterStatusLog::create([
            'letter_id' => $letter->id,
            'status' => $validated['status'],
            'position' => $validated['current_position'],
            'note' => $validated['note'] ?: 'Status diperbarui melalui pemindai QR Code.',
            'changed_by' => Auth::user()->name ?: Auth::user()->username,
            'changed_at' => now(),
        ]);

        return redirect()->route('scan-status.index', ['tracking' => $letter->tracking_code])
            ->with('success', 'Status surat berhasil diperbarui via scan QR.');
    }
}
