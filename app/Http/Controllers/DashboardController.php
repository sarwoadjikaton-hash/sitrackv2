<?php

namespace App\Http\Controllers;

use App\Models\Disposition;
use App\Models\Letter;
use App\Models\LetterNumber;
use App\Models\LetterNumberType;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the analytics dashboard
     */
    public function index(Request $request): Response
    {
        $currentYear = (int) date('Y');

        // Signature Lane Stats
        $signatureTotal = Letter::where('process_lane', 'signature')->count();
        $signatureCompleted = Letter::where('process_lane', 'signature')
            ->whereIn('status', ['Dokumen Sudah diambil', 'Selesai dan Siap Untuk diambil', 'Selesai', 'Surat Selesai di Paraf/TTD dan bisa diambil'])
            ->count();
        $signatureInProgress = Letter::where('process_lane', 'signature')
            ->whereNotIn('status', ['Dokumen Sudah diambil', 'Selesai dan Siap Untuk diambil', 'Selesai', 'Surat Selesai di Paraf/TTD dan bisa diambil', 'Ditolak'])
            ->count();

        // Disposition Lane Stats
        $dispositionTotal = Letter::where('process_lane', 'disposition')->count();
        $dispositionCompleted = Letter::where('process_lane', 'disposition')
            ->where('status', 'Selesai')
            ->count();
        $dispositionInProgress = Letter::where('process_lane', 'disposition')
            ->whereNotIn('status', ['Selesai', 'Dikembalikan'])
            ->count();

        // Number Stock Stats (Current Year)
        $stockUsed = LetterNumber::where('number_year', $currentYear)->where('status', 'used')->count();
        $stockAvailable = LetterNumber::where('number_year', $currentYear)->where('status', 'available')->count();
        $stockReserved = LetterNumber::where('number_year', $currentYear)->where('status', 'reserved')->count();

        // Recent Letters (Signature & Disposition)
        $recentLetters = Letter::with(['category', 'recipientUnit'])
            ->orderBy('id', 'desc')
            ->limit(7)
            ->get();

        // Recent Dispositions
        $recentDispositions = Disposition::with(['letter', 'toUnit'])
            ->orderBy('id', 'desc')
            ->limit(5)
            ->get();

        // Workbook / Type Summary
        $typesSummary = LetterNumberType::where('is_active', true)
            ->withCount([
                'letterNumbers as total_slots' => fn ($q) => $q->where('number_year', $currentYear),
                'letterNumbers as used_slots' => fn ($q) => $q->where('number_year', $currentYear)->where('status', 'used'),
                'letterNumbers as available_slots' => fn ($q) => $q->where('number_year', $currentYear)->where('status', 'available'),
            ])
            ->orderBy('display_order', 'asc')
            ->limit(8)
            ->get();

        return Inertia::render('Dashboard/Index', [
            'stats' => [
                'signature' => [
                    'total' => $signatureTotal,
                    'in_progress' => $signatureInProgress,
                    'completed' => $signatureCompleted,
                ],
                'disposition' => [
                    'total' => $dispositionTotal,
                    'in_progress' => $dispositionInProgress,
                    'completed' => $dispositionCompleted,
                ],
                'stock' => [
                    'year' => $currentYear,
                    'used' => $stockUsed,
                    'available' => $stockAvailable,
                    'reserved' => $stockReserved,
                    'total' => $stockUsed + $stockAvailable + $stockReserved,
                ],
            ],
            'recentLetters' => $recentLetters,
            'recentDispositions' => $recentDispositions,
            'typesSummary' => $typesSummary,
        ]);
    }
}
