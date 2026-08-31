<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AlurStatusController extends Controller
{
    /**
     * Display status workflow reference diagrams
     */
    public function index(): Response
    {
        return Inertia::render('AlurStatus/Index');
    }
}
