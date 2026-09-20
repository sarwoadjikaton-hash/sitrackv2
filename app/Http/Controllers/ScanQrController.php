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
     * Helper to extract clean tracking / agenda code from URL or raw text
     */
    public static function extractCode(string $input): string
    {
        $input = trim($input);
        if (preg_match('/tracking\/([A-Za-z0-9_\-]+)/i', $input, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/([A-Za-z0-9_]+-\d{8}-\d+)/i', $input, $matches)) {
            return trim($matches[1]);
        }
        if (preg_match('/(AG-[KM]-\d{4}-\d+)/i', $input, $matches)) {
            return trim($matches[1]);
        }
        return $input;
    }

    /**
     * Show QR scanner and manual tracking search interface
     */
    public function index(Request $request): Response
    {
        $rawTracking = trim((string) $request->input('tracking', ''));
        $letter = null;
        $cleanCode = '';

        if ($rawTracking !== '') {
            $cleanCode = self::extractCode($rawTracking);

            $letter = Letter::with(['category', 'recipientUnit'])
                ->where(function ($q) use ($cleanCode, $rawTracking) {
                    $q->where('tracking_code', $cleanCode)
                        ->orWhere('tracking_code', $rawTracking)
                        ->orWhere('agenda_number', $cleanCode)
                        ->orWhere('agenda_number', $rawTracking)
                        ->orWhere('letter_number', $cleanCode)
                        ->orWhere('letter_number', $rawTracking);
                })
                ->where('process_lane', 'signature')
                ->first();
        }

        return Inertia::render('ScanQr/Index', [
            'tracking' => $cleanCode ?: $rawTracking,
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

        $code = self::extractCode($validated['tracking_code']);

        $letter = Letter::where(function ($q) use ($code, $validated) {
            $q->where('tracking_code', $code)
                ->orWhere('tracking_code', $validated['tracking_code'])
                ->orWhere('agenda_number', $code);
        })
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

        if (!empty($letter->sender_phone)) {
            try {
                \App\Services\WhatsAppService::sendStatusUpdate($letter, $validated['note'] ?? null);
            } catch (\Throwable $we) {
                \Illuminate\Support\Facades\Log::warning('[WhatsApp] Failed to dispatch QR scan status update: ' . $we->getMessage());
            }
        }

        return redirect()->route('scan-status.index', ['tracking' => $letter->tracking_code])
            ->with('success', 'Status surat berhasil diperbarui via scan QR.');
    }
}
