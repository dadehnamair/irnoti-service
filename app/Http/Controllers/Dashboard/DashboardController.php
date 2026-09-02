<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Customer account home (docs/starter.md §15 — the light version: plan status,
 * profile completeness and quick links, not the full SMS panel which is out of
 * the current scope).
 */
class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.index', [
            'user' => $request->user(),
        ]);
    }
}
