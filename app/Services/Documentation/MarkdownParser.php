<?php

namespace App\Services\Documentation;

use Illuminate\Support\Str;

class MarkdownParser
{
    /**
     * Parse markdown text into safe, enhanced HTML and extract Table of Contents (TOC).
     *
     * @return array{
     *     html: string,
     *     toc: array<int, array{level: int, text: string, id: string}>
     * }
     */
    public function parse(string $markdown): array
    {
        // 1. Preprocess custom containers (::: type ... :::)
        $processed = $this->preprocessCustomContainers($markdown);

        // 2. Preprocess GitHub-style callouts (> [!NOTE], etc.)
        $processed = $this->preprocessGithubCallouts($processed);

        // 3. Preprocess Mermaid code blocks to avoid unwanted markdown mutations
        [$processed, $mermaidPlaceholders] = $this->extractMermaidBlocks($processed);

        // 4. Convert markdown to HTML using Laravel's built-in Str::markdown (League CommonMark)
        $html = Str::markdown($processed);

        // 5. Restore Mermaid blocks with dedicated container
        $html = $this->restoreMermaidBlocks($html, $mermaidPlaceholders);

        // 6. Enhance tables with responsive Bootstrap styling
        $html = $this->enhanceTables($html);

        // 7. Enhance images with lazy loading and rounded styling
        $html = $this->enhanceImages($html);

        // 8. Enhance headings with ID anchors and build Table of Contents (TOC)
        [$html, $toc] = $this->extractHeadingsAndBuildToc($html);

        // 9. Enhance code blocks with copy button
        $html = $this->enhanceCodeBlocks($html);

        return [
            'html' => $html,
            'toc' => $toc,
        ];
    }

    /**
     * Preprocess ::: type ... ::: syntax into HTML callout cards.
     */
    protected function preprocessCustomContainers(string $markdown): string
    {
        $pattern = '/(?:\r?\n)?:::\s*(warning|info|tip|caution|note|danger|success)\s*\r?\n(.*?)\r?\n:::/s';

        return preg_replace_callback($pattern, function ($matches) {
            $type = strtolower($matches[1]);
            $body = trim($matches[2]);

            $config = $this->getCalloutConfig($type);

            return "\n\n<div class=\"doc-callout {$config['wrapper_class']} p-3 my-3 radius-10 border-start border-4\">"
                .'<div class="d-flex align-items-center mb-1">'
                ."<i class=\"bx {$config['icon']} font-22 me-2 {$config['icon_class']}\"></i>"
                ."<strong class=\"text-uppercase font-12 {$config['title_class']}\">".htmlspecialchars($config['label'], ENT_QUOTES, 'UTF-8').'</strong>'
                .'</div>'
                .'<div class="doc-callout-body font-14 text-secondary">'.Str::markdown($body).'</div>'
                ."</div>\n\n";
        }, $markdown);
    }

    /**
     * Preprocess GitHub-style callouts (> [!NOTE], etc.)
     */
    protected function preprocessGithubCallouts(string $markdown): string
    {
        $pattern = '/(?:^|\r?\n)>\s*\[!(NOTE|TIP|IMPORTANT|WARNING|CAUTION)\][ \t]*\r?\n((?:>[^\r\n]*(?:\r?\n|$))+)/m';

        return preg_replace_callback($pattern, function ($matches) {
            $type = strtolower($matches[1]);
            $rawLines = explode("\n", $matches[2]);
            $cleanLines = [];

            foreach ($rawLines as $line) {
                $line = trim($line);
                if (str_starts_with($line, '>')) {
                    $line = trim(substr($line, 1));
                }
                if ($line !== '') {
                    $cleanLines[] = $line;
                }
            }

            $body = implode("\n", $cleanLines);
            $config = $this->getCalloutConfig($type);

            return "\n\n<div class=\"doc-callout {$config['wrapper_class']} p-3 my-3 radius-10 border-start border-4\">"
                .'<div class="d-flex align-items-center mb-1">'
                ."<i class=\"bx {$config['icon']} font-22 me-2 {$config['icon_class']}\"></i>"
                ."<strong class=\"text-uppercase font-12 {$config['title_class']}\">".htmlspecialchars($config['label'], ENT_QUOTES, 'UTF-8').'</strong>'
                .'</div>'
                .'<div class="doc-callout-body font-14 text-secondary">'.Str::markdown($body).'</div>'
                ."</div>\n\n";
        }, $markdown);
    }

    /**
     * Get visual configuration for alert boxes.
     */
    protected function getCalloutConfig(string $type): array
    {
        return match ($type) {
            'warning' => [
                'wrapper_class' => 'bg-light-warning border-warning',
                'icon' => 'bx-error-circle',
                'icon_class' => 'text-warning',
                'title_class' => 'text-warning',
                'label' => 'Peringatan Penting',
            ],
            'caution', 'danger' => [
                'wrapper_class' => 'bg-light-danger border-danger',
                'icon' => 'bx-shield-x',
                'icon_class' => 'text-danger',
                'title_class' => 'text-danger',
                'label' => 'Perhatian Khusus',
            ],
            'tip', 'success' => [
                'wrapper_class' => 'bg-light-success border-success',
                'icon' => 'bx-bulb',
                'icon_class' => 'text-success',
                'title_class' => 'text-success',
                'label' => 'Tips Praktis',
            ],
            'important' => [
                'wrapper_class' => 'bg-light-info border-info',
                'icon' => 'bx-bookmark-star',
                'icon_class' => 'text-info',
                'title_class' => 'text-info',
                'label' => 'Penting',
            ],
            default => [ // note, info
                'wrapper_class' => 'bg-light-primary border-primary',
                'icon' => 'bx-info-circle',
                'icon_class' => 'text-primary',
                'title_class' => 'text-primary',
                'label' => 'Informasi & Catatan',
            ],
        };
    }

    /**
     * Extract Mermaid diagram blocks and replace with placeholders.
     */
    protected function extractMermaidBlocks(string $markdown): array
    {
        $placeholders = [];
        $pattern = '/```mermaid\s*\r?\n(.*?)\r?\n```/s';

        $processed = preg_replace_callback($pattern, function ($matches) use (&$placeholders) {
            $key = '%%MERMAID_BLOCK_'.count($placeholders).'%%';
            $code = trim($matches[1]);
            $placeholders[$key] = $code;

            return "\n\n".$key."\n\n";
        }, $markdown);

        return [$processed, $placeholders];
    }

    /**
     * Restore Mermaid diagram blocks into rendered HTML with mermaid class.
     */
    protected function restoreMermaidBlocks(string $html, array $placeholders): string
    {
        foreach ($placeholders as $key => $code) {
            $mermaidHtml = '<div class="mermaid-diagram-card my-4 p-3 bg-light radius-12 border shadow-sm text-center overflow-auto">'
                .'<div class="text-muted font-11 text-uppercase mb-2"><i class="bx bx-git-repo-forked me-1"></i>Diagram Alur Sistem</div>'
                .'<div class="mermaid text-dark">'.htmlspecialchars($code, ENT_QUOTES, 'UTF-8').'</div>'
                .'</div>';

            $html = str_replace("<p>{$key}</p>", $mermaidHtml, $html);
            $html = str_replace($key, $mermaidHtml, $html);
        }

        return $html;
    }

    /**
     * Wrap <table> in responsive div and add Bootstrap table classes.
     */
    protected function enhanceTables(string $html): string
    {
        return preg_replace_callback('/<table(?:\s+[^>]*)?>(.*?)<\/table>/s', function ($matches) {
            return '<div class="table-responsive my-3">'
                .'<table class="table table-bordered table-striped table-hover align-middle mb-0 font-14">'
                .$matches[1]
                .'</table></div>';
        }, $html);
    }

    /**
     * Add responsive, lazy-loading classes to images.
     */
    protected function enhanceImages(string $html): string
    {
        return preg_replace_callback('/<img\s+([^>]*?)>/i', function ($matches) {
            $attrs = $matches[1];
            if (! str_contains($attrs, 'loading=')) {
                $attrs .= ' loading="lazy"';
            }
            if (str_contains($attrs, 'class="')) {
                $attrs = preg_replace('/class="([^"]*)"/', 'class="$1 img-fluid rounded radius-10 my-2 shadow-sm border"', $attrs);
            } else {
                $attrs .= ' class="img-fluid rounded radius-10 my-2 shadow-sm border"';
            }

            return '<img '.$attrs.'>';
        }, $html);
    }

    /**
     * Add IDs to headings (h2, h3) and generate Table of Contents.
     */
    protected function extractHeadingsAndBuildToc(string $html): array
    {
        $toc = [];
        $usedSlugs = [];

        $html = preg_replace_callback('/<h([1-3])(?:\s+[^>]*)?>(.*?)<\/h\1>/i', function ($matches) use (&$toc, &$usedSlugs) {
            $level = (int) $matches[1];
            $rawText = $matches[2];
            $cleanText = trim(strip_tags($rawText));

            $baseSlug = Str::slug($cleanText) ?: 'heading';
            $slug = $baseSlug;
            $counter = 1;

            while (in_array($slug, $usedSlugs, true)) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $usedSlugs[] = $slug;

            $toc[] = [
                'level' => $level,
                'text' => $cleanText,
                'id' => $slug,
            ];

            return "<h{$level} id=\"{$slug}\" class=\"doc-heading doc-h{$level} mt-4 mb-2 font-weight-bold\">"
                ."<a href=\"#{$slug}\" class=\"doc-heading-anchor text-decoration-none me-2 text-muted opacity-50\" title=\"Tautan langsung ke bagian ini\"><i class=\"bx bx-hash\"></i></a>"
                .$rawText
                ."</h{$level}>";
        }, $html);

        return [$html, $toc];
    }

    /**
     * Wrap code blocks in responsive container with a copy-to-clipboard button.
     */
    protected function enhanceCodeBlocks(string $html): string
    {
        return preg_replace_callback('/<pre><code(?:\s+class="language-([^"]*)")?>(.*?)<\/code><\/pre>/s', function ($matches) {
            $lang = $matches[1] ?? 'text';
            $code = $matches[2];

            return '<div class="doc-code-block position-relative my-3">'
                .'<div class="doc-code-header d-flex align-items-center justify-content-between px-3 py-1 bg-dark text-white-50 font-12 rounded-top">'
                .'<span class="text-uppercase font-11 font-weight-bold text-light">'.htmlspecialchars($lang, ENT_QUOTES, 'UTF-8').'</span>'
                .'<button type="button" class="btn btn-sm btn-link text-white-50 text-decoration-none btn-copy-code p-0" title="Salin Kode">'
                .'<i class="bx bx-copy me-1"></i> Salin'
                .'</button>'
                .'</div>'
                .'<pre class="m-0 p-3 rounded-bottom bg-dark text-white overflow-auto font-13"><code class="language-'.htmlspecialchars($lang, ENT_QUOTES, 'UTF-8').'">'.$code.'</code></pre>'
                .'</div>';
        }, $html);
    }
}
