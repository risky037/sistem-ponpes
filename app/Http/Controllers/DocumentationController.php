<?php

namespace App\Http\Controllers;

use App\Services\Documentation\DocumentationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DocumentationController extends Controller
{
    public function __construct(
        protected DocumentationService $docService
    ) {}

    /**
     * Display the Documentation Center homepage and role-based quick start.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $primaryRole = $user?->roles->first()?->name ?? 'Santri';
        $activeTab = $request->query('tab', strtolower($primaryRole));

        $summary = $this->docService->getHomeSummary($user);

        return view('pages.documentation.index', [
            'user' => $user,
            'primaryRole' => $primaryRole,
            'activeTab' => $activeTab,
            'menu' => $summary['menu'],
            'totalArticles' => $summary['total_articles'],
            'totalCategories' => $summary['total_categories'],
        ]);
    }

    /**
     * Display a specific documentation article in the documentation reader.
     */
    public function show(Request $request, string $category, string $slug): View
    {
        $user = auth()->user();
        $document = $this->docService->getDocument($category, $slug, $user);

        if (! $document) {
            abort(404, 'Dokumen panduan tidak ditemukan atau Anda tidak memiliki hak akses untuk membukanya.');
        }

        $menu = $this->docService->getMenu($user);
        $navigation = $this->docService->getNavigation($user, $category, $slug);

        return view('pages.documentation.show', [
            'user' => $user,
            'document' => $document,
            'menu' => $menu,
            'navigation' => $navigation,
            'currentCategory' => $category,
            'currentSlug' => $slug,
        ]);
    }

    /**
     * Return JSON search index of documents accessible to the authenticated user.
     */
    public function searchIndex(Request $request): JsonResponse
    {
        $user = auth()->user();
        $index = $this->docService->getSearchIndex($user);

        return response()->json($index);
    }
}
