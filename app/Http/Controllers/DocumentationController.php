<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    /**
     * Display the authenticated system documentation and role-based operational guide.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $primaryRole = $user?->roles->first()?->name ?? 'Santri';
        $activeTab = $request->query('tab', strtolower($primaryRole));

        return view('pages.documentation.index', compact('user', 'primaryRole', 'activeTab'));
    }
}
