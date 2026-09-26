<?php

namespace App\Services\Documentation;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DocumentationService
{
    public function __construct(
        protected DocumentationRegistry $registry,
        protected MarkdownParser $parser
    ) {}

    /**
     * Get accessible menu tree for a user.
     */
    public function getMenu(User $user): array
    {
        return $this->registry->getAccessibleMenu($user);
    }

    /**
     * Find and parse a document if the user is authorized.
     *
     * @return array{
     *     title: string,
     *     slug: string,
     *     category: string,
     *     category_name: string,
     *     category_icon: string,
     *     filename: string,
     *     html: string,
     *     toc: array<int, array{level: int, text: string, id: string}>,
     *     last_modified: int,
     *     formatted_date: string,
     *     size_bytes: int,
     *     word_count: int,
     *     reading_time: int
     * }|null
     */
    public function getDocument(string $category, string $slug, User $user): ?array
    {
        $docMeta = $this->registry->findDocument($category, $slug);
        if (! $docMeta) {
            return null;
        }

        // Authorize user against document roles
        $roles = $docMeta['roles'] ?? [];
        if (! $this->registry->canAccess($user, $roles)) {
            return null;
        }

        $filePath = $docMeta['file_path'];
        if (! file_exists($filePath)) {
            return null;
        }

        $mtime = filemtime($filePath) ?: time();
        $cacheKey = "documentation:doc:{$category}:{$slug}:{$mtime}";

        return Cache::remember($cacheKey, now()->addDays(7), function () use ($docMeta, $filePath, $mtime) {
            $rawContent = file_get_contents($filePath);
            $parsed = $this->parser->parse($rawContent);

            $wordCount = str_word_count(strip_tags($rawContent));
            $fm = $parsed['frontmatter'] ?? [];
            $title = $fm['title'] ?? $docMeta['title'];
            $readingTime = isset($fm['estimated_time'])
                ? (int) filter_var($fm['estimated_time'], FILTER_SANITIZE_NUMBER_INT)
                : max(1, (int) ceil($wordCount / 180));

            return [
                'title' => $title,
                'slug' => $docMeta['slug'],
                'category' => $docMeta['category'],
                'category_name' => $docMeta['category_name'],
                'category_icon' => $docMeta['category_icon'],
                'filename' => $docMeta['filename'],
                'html' => $parsed['html'],
                'toc' => $parsed['toc'],
                'frontmatter' => $fm,
                'difficulty' => $fm['difficulty'] ?? null,
                'version' => $fm['version'] ?? null,
                'last_modified' => $mtime,
                'formatted_date' => date('d M Y, H:i', $mtime),
                'size_bytes' => filesize($filePath),
                'word_count' => $wordCount,
                'reading_time' => $readingTime,
            ];
        });
    }

    /**
     * Resolve Previous and Next document navigation for the current document.
     *
     * @return array{
     *     prev: array{title: string, url: string}|null,
     *     next: array{title: string, url: string}|null
     * }
     */
    public function getNavigation(User $user, string $category, string $slug): array
    {
        $menu = $this->getMenu($user);
        $flattened = [];

        foreach ($menu as $cat) {
            foreach ($cat['docs'] as $doc) {
                $flattened[] = [
                    'category' => $cat['key'],
                    'slug' => $doc['slug'],
                    'title' => $doc['title'],
                    'url' => $doc['url'],
                ];
            }
        }

        $currentIndex = -1;
        foreach ($flattened as $index => $item) {
            if ($item['category'] === $category && $item['slug'] === $slug) {
                $currentIndex = $index;
                break;
            }
        }

        if ($currentIndex === -1) {
            return ['prev' => null, 'next' => null];
        }

        $prev = $currentIndex > 0 ? $flattened[$currentIndex - 1] : null;
        $next = $currentIndex < (count($flattened) - 1) ? $flattened[$currentIndex + 1] : null;

        return [
            'prev' => $prev ? ['title' => $prev['title'], 'url' => $prev['url']] : null,
            'next' => $next ? ['title' => $next['title'], 'url' => $next['url']] : null,
        ];
    }

    /**
     * Generate client-side search index JSON data accessible to the user.
     *
     * @return array<int, array{
     *     title: string,
     *     category: string,
     *     category_name: string,
     *     slug: string,
     *     url: string,
     *     excerpt: string,
     *     keywords: string
     * }>
     */
    public function getSearchIndex(User $user): array
    {
        $menu = $this->getMenu($user);
        $primaryRole = $user->roles->first()?->name ?? 'Guest';
        $cacheKey = "documentation:search_index:{$primaryRole}";

        return Cache::remember($cacheKey, now()->addHours(24), function () use ($menu) {
            $items = [];

            foreach ($menu as $catKey => $catData) {
                foreach ($catData['docs'] as $docData) {
                    $docMeta = $this->registry->findDocument($catKey, $docData['slug']);
                    if (! $docMeta || ! file_exists($docMeta['file_path'])) {
                        continue;
                    }

                    $rawContent = file_get_contents($docMeta['file_path']);
                    $cleanText = preg_replace('/\s+/', ' ', strip_tags(Str::markdown($rawContent)));
                    $excerpt = Str::limit($cleanText, 180);

                    // Extract first few heading lines as keywords
                    preg_match_all('/^#{1,3}\s+(.+)$/m', $rawContent, $headingMatches);
                    $headings = implode(' ', $headingMatches[1] ?? []);

                    $items[] = [
                        'title' => $docData['title'],
                        'category' => $catKey,
                        'category_name' => $catData['name'],
                        'slug' => $docData['slug'],
                        'url' => $docData['url'],
                        'excerpt' => $excerpt,
                        'keywords' => Str::limit($headings, 300),
                    ];
                }
            }

            return $items;
        });
    }

    /**
     * Get summary metrics for the Documentation Center homepage.
     */
    public function getHomeSummary(User $user): array
    {
        $menu = $this->getMenu($user);
        $totalArticles = 0;
        $categoriesCount = count($menu);

        foreach ($menu as $cat) {
            $totalArticles += count($cat['docs']);
        }

        return [
            'total_categories' => $categoriesCount,
            'total_articles' => $totalArticles,
            'menu' => $menu,
        ];
    }
}
