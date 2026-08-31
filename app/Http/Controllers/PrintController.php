<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PrintController extends Controller
{
    /**
     * Print Lembar Disposisi (A4)
     */
    public function disposisi($id): Response
    {
        $letter = Letter::with(['category', 'recipientUnit', 'dispositions.toUnit'])
            ->findOrFail($id);

        return Inertia::render('Print/Disposisi', [
            'letter' => $letter,
        ]);
    }

    /**
     * Print Lembar Pendamping Penandatanganan / Tindak Lanjut (A4)
     */
    public function pendamping($id): Response
    {
        $letter = Letter::with(['category', 'recipientUnit', 'statusLogs'])
            ->findOrFail($id);

        return Inertia::render('Print/Pendamping', [
            'letter' => $letter,
        ]);
    }
}
