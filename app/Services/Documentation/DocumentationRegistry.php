<?php

namespace App\Services\Documentation;

use App\Models\User;
use Illuminate\Support\Str;

class DocumentationRegistry
{
    /**
     * Base path to the documentation markdown files.
     */
    protected string $baseDocsPath;

    public function __construct(?string $baseDocsPath = null)
    {
        $this->baseDocsPath = $baseDocsPath ?? base_path('docs');
    }

    /**
     * Get the base path for docs.
     */
    public function getBaseDocsPath(): string
    {
        return $this->baseDocsPath;
    }

    /**
     * Registered categories and documents configuration.
     * Single source of truth is still the markdown files, but this registry provides
     * curated metadata, ordering, friendly titles, and role authorization.
     *
     * @return array<string, array{
     *     name: string,
     *     icon: string,
     *     description: string,
     *     order: int,
     *     subfolder?: string,
     *     roles: array<string>,
     *     docs: array<string, array{
     *         title: string,
     *         slug: string,
     *         roles?: array<string>
     *     }>
     * }>
     */
    public function getCategories(): array
    {
        return [
            'onboarding' => [
                'name' => 'Mulai Menggunakan (Onboarding)',
                'icon' => 'bx bx-rocket',
                'description' => 'Panduan cepat langkah demi langkah bagi pengguna yang baru pertama kali masuk ke DIGITREN.',
                'order' => 1,
                'subfolder' => 'user-guide'.DIRECTORY_SEPARATOR.'onboarding',
                'roles' => ['Administrator', 'Pengurus', 'Guru', 'Keuangan', 'Santri'],
                'docs' => [
                    '01-administrator-first-setup.md' => [
                        'title' => 'Panduan Pertama Administrator',
                        'slug' => 'administrator-first-setup',
                        'roles' => ['Administrator'],
                    ],
                    '02-guru-first-use.md' => [
                        'title' => 'Panduan Pertama Guru / Asatidz',
                        'slug' => 'guru-first-use',
                        'roles' => ['Administrator', 'Pengurus', 'Guru'],
                    ],
                    '03-santri-first-use.md' => [
                        'title' => 'Panduan Pertama Santri & Wali',
                        'slug' => 'santri-first-use',
                        'roles' => ['Administrator', 'Pengurus', 'Santri'],
                    ],
                    '04-pengurus-first-use.md' => [
                        'title' => 'Panduan Pertama Dewan Pengurus',
                        'slug' => 'pengurus-first-use',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                    '05-keuangan-first-use.md' => [
                        'title' => 'Panduan Pertama Staf Keuangan',
                        'slug' => 'keuangan-first-use',
                        'roles' => ['Administrator', 'Pengurus', 'Keuangan'],
                    ],
                ],
            ],
            'user-guide' => [
                'name' => 'Panduan Pengguna',
                'icon' => 'bx bx-book-open',
                'description' => 'Buku panduan operasional langkah-demi-langkah bagi seluruh peran pengguna DIGITREN.',
                'order' => 2,
                'roles' => ['Administrator', 'Pengurus', 'Guru', 'Keuangan', 'Santri'],
                'docs' => [
                    '00-start-here.md' => [
                        'title' => '00. Mulai Dari Sini (Start Here)',
                        'slug' => 'start-here',
                        'roles' => ['Administrator', 'Pengurus', 'Guru', 'Keuangan', 'Santri'],
                    ],
                    '01-pengenalan-sistem.md' => [
                        'title' => '01. Pengenalan Sistem DIGITREN',
                        'slug' => 'pengenalan-sistem',
                        'roles' => ['Administrator', 'Pengurus', 'Guru', 'Keuangan', 'Santri'],
                    ],
                    '02-role-dan-hak-akses.md' => [
                        'title' => '02. Pembagian Peran dan Hak Akses',
                        'slug' => 'role-dan-hak-akses',
                        'roles' => ['Administrator', 'Pengurus', 'Guru', 'Keuangan', 'Santri'],
                    ],
                    '03-initial-system-setup.md' => [
                        'title' => '03. Pengaturan Awal Sistem',
                        'slug' => 'initial-system-setup',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                    '04-academic-setup-workflow.md' => [
                        'title' => '04. Alur Penyiapan Akademik',
                        'slug' => 'academic-setup-workflow',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                    '05-guru-workflow.md' => [
                        'title' => '05. Alur Kerja Guru & Asatidz',
                        'slug' => 'guru-workflow',
                        'roles' => ['Administrator', 'Pengurus', 'Guru'],
                    ],
                    '06-santri-workflow.md' => [
                        'title' => '06. Alur Penggunaan Portal Santri',
                        'slug' => 'santri-workflow',
                        'roles' => ['Administrator', 'Pengurus', 'Santri'],
                    ],
                    '07-keuangan-workflow.md' => [
                        'title' => '07. Alur Pengelolaan Keuangan & Tabungan',
                        'slug' => 'keuangan-workflow',
                        'roles' => ['Administrator', 'Pengurus', 'Keuangan'],
                    ],
                    '08-penilaian-workflow.md' => [
                        'title' => '08. Alur Penilaian & Evaluasi Akademik',
                        'slug' => 'penilaian-workflow',
                        'roles' => ['Administrator', 'Pengurus', 'Guru'],
                    ],
                    '09-intelligence-dashboard.md' => [
                        'title' => '09. Dasbor Intelijen Akademik',
                        'slug' => 'intelligence-dashboard',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                    '10-learning-management-workflow.md' => [
                        'title' => '10. Alur Pengelolaan Materi Pembelajaran',
                        'slug' => 'learning-management-workflow',
                        'roles' => ['Administrator', 'Pengurus', 'Guru', 'Santri'],
                    ],
                ],
            ],
            'academic' => [
                'name' => 'Arsitektur Akademik',
                'icon' => 'bx bx-certification',
                'description' => 'Dokumentasi fondasi alur kerja dan evaluasi pembelajaran pesantren.',
                'order' => 3,
                'roles' => ['Administrator', 'Pengurus', 'Guru'],
                'docs' => [
                    'workflow-audit.md' => [
                        'title' => 'Audit Alur Kerja Domain Akademik',
                        'slug' => 'workflow-audit',
                        'roles' => ['Administrator', 'Pengurus', 'Guru'],
                    ],
                    'assessment-architecture.md' => [
                        'title' => 'Tinjauan Arsitektur Penilaian & Evaluasi',
                        'slug' => 'assessment-architecture',
                        'roles' => ['Administrator', 'Pengurus', 'Guru'],
                    ],
                ],
            ],
            'architecture' => [
                'name' => 'Arsitektur Sistem & LMS',
                'icon' => 'bx bx-cube-alt',
                'description' => 'Desain arsitektur modul sistem, basis data, dan integrasi LMS.',
                'order' => 4,
                'roles' => ['Administrator', 'Pengurus', 'Guru'],
                'docs' => [
                    'learning-management-design.md' => [
                        'title' => 'Desain Arsitektur LMS Pesantren',
                        'slug' => 'learning-management-design',
                        'roles' => ['Administrator', 'Pengurus', 'Guru'],
                    ],
                    'learning-management-database.md' => [
                        'title' => 'Proposal Basis Data Manajemen Pembelajaran',
                        'slug' => 'learning-management-database',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                    'documentation-center-design.md' => [
                        'title' => 'Desain Arsitektur Documentation Center',
                        'slug' => 'documentation-center-design',
                        'roles' => ['Administrator'],
                    ],
                ],
            ],
            'design' => [
                'name' => 'Panduan Desain UI/UX',
                'icon' => 'bx bx-palette',
                'description' => 'Pedoman komponen antarmuka, token visual, dan tata letak aplikasi.',
                'order' => 5,
                'roles' => ['Administrator', 'Pengurus', 'Guru'],
                'docs' => [
                    'lms-ui-guideline.md' => [
                        'title' => 'Pedoman Desain Antarmuka LMS',
                        'slug' => 'lms-ui-guideline',
                        'roles' => ['Administrator', 'Pengurus', 'Guru'],
                    ],
                    'documentation-portal.md' => [
                        'title' => 'Rencana Peningkatan Portal Dokumentasi',
                        'slug' => 'documentation-portal',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                ],
            ],
            'releases' => [
                'name' => 'Catatan Rilis',
                'icon' => 'bx bx-tag',
                'description' => 'Riwayat perubahan fitur, penguatan operasional, dan fase rilis.',
                'order' => 6,
                'roles' => ['Administrator', 'Pengurus'],
                'docs' => [
                    'phase-5.8.8.1-release-notes.md' => [
                        'title' => 'Catatan Rilis Fase 5.8.8.1',
                        'slug' => 'phase-5-8-8-1-release-notes',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                    'phase-5.8.8-release-notes.md' => [
                        'title' => 'Catatan Rilis Fase 5.8.8',
                        'slug' => 'phase-5-8-8-release-notes',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                    'phase-5.8.7E-release-notes.md' => [
                        'title' => 'Catatan Rilis Fase 5.8.7E',
                        'slug' => 'phase-5-8-7e-release-notes',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                    'phase-5.8.7D-release-notes.md' => [
                        'title' => 'Catatan Rilis Fase 5.8.7D',
                        'slug' => 'phase-5-8-7d-release-notes',
                        'roles' => ['Administrator', 'Pengurus'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Check if a user has access to a specific document or category.
     */
    public function canAccess(User $user, array $roles): bool
    {
        if ($user->hasRole('Administrator')) {
            return true;
        }

        if (empty($roles)) {
            return false;
        }

        return $user->hasAnyRole($roles);
    }

    /**
     * Find document metadata by category and slug.
     * Supports both registered docs and automatic discovery.
     */
    public function findDocument(string $categoryKey, string $slug): ?array
    {
        $categories = $this->getCategories();
        if (! isset($categories[$categoryKey])) {
            return null;
        }

        $category = $categories[$categoryKey];
        $folder = $category['subfolder'] ?? $categoryKey;

        // 1. Search in defined docs in registry
        foreach ($category['docs'] as $filename => $docMeta) {
            if ($docMeta['slug'] === $slug) {
                $filePath = $this->baseDocsPath.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$filename;
                if (! file_exists($filePath)) {
                    continue;
                }

                return array_merge($docMeta, [
                    'filename' => $filename,
                    'file_path' => $filePath,
                    'category' => $categoryKey,
                    'category_name' => $category['name'],
                    'category_icon' => $category['icon'],
                    'roles' => $docMeta['roles'] ?? $category['roles'],
                ]);
            }
        }

        // 2. Fallback: Check if file with matching slug exists in docs/{folder}/
        $folderPath = $this->baseDocsPath.DIRECTORY_SEPARATOR.$folder;
        if (is_dir($folderPath)) {
            $files = scandir($folderPath);
            foreach ($files as $file) {
                if (! str_ends_with($file, '.md')) {
                    continue;
                }

                $fileSlug = Str::slug(str_replace('.md', '', $file));
                if ($fileSlug === $slug) {
                    $fullPath = $folderPath.DIRECTORY_SEPARATOR.$file;
                    $title = $this->extractTitleFromMarkdown($fullPath) ?? Str::headline(str_replace('.md', '', $file));

                    return [
                        'title' => $title,
                        'slug' => $fileSlug,
                        'filename' => $file,
                        'file_path' => $fullPath,
                        'category' => $categoryKey,
                        'category_name' => $category['name'],
                        'category_icon' => $category['icon'],
                        'roles' => $category['roles'],
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Get accessible menu tree for a user.
     */
    public function getAccessibleMenu(User $user): array
    {
        $categories = $this->getCategories();
        $menu = [];

        foreach ($categories as $catKey => $catData) {
            if (! $this->canAccess($user, $catData['roles'])) {
                continue;
            }

            $folder = $catData['subfolder'] ?? $catKey;
            $accessibleDocs = [];

            foreach ($catData['docs'] as $filename => $docData) {
                $docRoles = $docData['roles'] ?? $catData['roles'];
                if (! $this->canAccess($user, $docRoles)) {
                    continue;
                }

                $filePath = $this->baseDocsPath.DIRECTORY_SEPARATOR.$folder.DIRECTORY_SEPARATOR.$filename;
                if (! file_exists($filePath)) {
                    continue;
                }

                $accessibleDocs[] = [
                    'title' => $docData['title'],
                    'slug' => $docData['slug'],
                    'filename' => $filename,
                    'url' => route('documentation.show', ['category' => $catKey, 'slug' => $docData['slug']]),
                    'roles' => $docRoles,
                ];
            }

            if (! empty($accessibleDocs)) {
                $menu[$catKey] = [
                    'key' => $catKey,
                    'name' => $catData['name'],
                    'icon' => $catData['icon'],
                    'description' => $catData['description'],
                    'order' => $catData['order'],
                    'docs' => $accessibleDocs,
                ];
            }
        }

        // Sort categories by defined order
        uasort($menu, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $menu;
    }

    /**
     * Extract document title from the first level-1 heading.
     */
    protected function extractTitleFromMarkdown(string $filePath): ?string
    {
        if (! file_exists($filePath)) {
            return null;
        }

        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (! $lines) {
            return null;
        }

        $inFrontmatter = false;
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '---') {
                $inFrontmatter = ! $inFrontmatter;

                continue;
            }
            if ($inFrontmatter) {
                continue;
            }
            if (str_starts_with($line, '# ')) {
                return trim(substr($line, 2));
            }
        }

        return null;
    }
}
