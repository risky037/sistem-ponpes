<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Documentation\DocumentationService;
use App\Services\Documentation\MarkdownParser;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DocumentationCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Cache::flush();
    }

    public function test_guest_is_redirected_to_login_from_all_documentation_endpoints(): void
    {
        $this->get(route('documentation.index'))
            ->assertRedirect(route('login'));

        $this->get(route('documentation.show', ['category' => 'user-guide', 'slug' => 'pengenalan-sistem']))
            ->assertRedirect(route('login'));

        $this->get(route('documentation.search-index'))
            ->assertRedirect(route('login'));
    }

    public function test_administrator_can_view_documentation_homepage_with_all_categories(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Dokumentasi']);
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('documentation.index'));

        $response->assertStatus(200);
        $response->assertSee('Panduan Penggunaan Sistem DIGITREN');
        $response->assertSee('Panduan Pengguna');
        $response->assertSee('Arsitektur Akademik');
        $response->assertSee('Arsitektur Sistem & LMS');
        $response->assertSee('Panduan Desain UI/UX');
        $response->assertSee('Catatan Rilis');
        $response->assertSee('01. Pengenalan Sistem DIGITREN');
    }

    public function test_document_reader_renders_markdown_content_and_metadata(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Reader']);
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('documentation.show', [
            'category' => 'user-guide',
            'slug' => 'pengenalan-sistem',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Panduan Pengguna');
        $response->assertSee('menit baca');
        $response->assertSee('kata');
        $response->assertSee('Di Halaman Ini');
        $response->assertSee('Dokumen Selanjutnya');
    }

    public function test_markdown_parser_converts_mermaid_and_callout_containers(): void
    {
        $parser = new MarkdownParser;

        $markdown = <<<'MD'
# Judul Panduan

::: warning
Pastikan Tahun Ajaran aktif sebelum membuat jadwal KBM.
:::

> [!NOTE]
> Catatan penting bagi dewan pengurus.

```mermaid
graph TD;
  A[Setting] --> B[Tahun Ajaran];
```

| Kolom 1 | Kolom 2 |
| --- | --- |
| Nilai A | Nilai B |
MD;

        $result = $parser->parse($markdown);

        // Check callouts
        $this->assertStringContainsString('doc-callout', $result['html']);
        $this->assertStringContainsString('Peringatan Penting', $result['html']);
        $this->assertStringContainsString('Informasi &amp; Catatan', $result['html']);

        // Check mermaid container
        $this->assertStringContainsString('mermaid-diagram-card', $result['html']);
        $this->assertStringContainsString('<div class="mermaid text-dark">', $result['html']);

        // Check table wrapper
        $this->assertStringContainsString('table-responsive', $result['html']);
        $this->assertStringContainsString('table-bordered', $result['html']);

        // Check TOC
        $this->assertNotEmpty($result['toc']);
        $this->assertStringContainsString('id="judul-panduan"', $result['html']);
    }

    public function test_role_authorization_restricts_documents_per_role(): void
    {
        $santri = User::factory()->create(['name' => 'Santri Pembaca']);
        $santri->assignRole('Santri');

        // Santri can access santri-workflow
        $responseSantri = $this->actingAs($santri)->get(route('documentation.show', [
            'category' => 'user-guide',
            'slug' => 'santri-workflow',
        ]));
        $responseSantri->assertStatus(200);
        $responseSantri->assertSee('Portal Santri');

        // Santri CANNOT access guru-workflow
        $responseForbidden = $this->actingAs($santri)->get(route('documentation.show', [
            'category' => 'user-guide',
            'slug' => 'guru-workflow',
        ]));
        $responseForbidden->assertStatus(404);

        // Guru can access guru-workflow
        $guru = User::factory()->create(['name' => 'Ustadz Pengajar']);
        $guru->assignRole('Guru');

        $responseGuru = $this->actingAs($guru)->get(route('documentation.show', [
            'category' => 'user-guide',
            'slug' => 'guru-workflow',
        ]));
        $responseGuru->assertStatus(200);
        $responseGuru->assertSee('Portal Guru');
    }

    public function test_search_index_json_returns_authorized_documents(): void
    {
        $guru = User::factory()->create(['name' => 'Guru Searcher']);
        $guru->assignRole('Guru');

        $response = $this->actingAs($guru)->get(route('documentation.search-index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            '*' => [
                'title',
                'category',
                'category_name',
                'slug',
                'url',
                'excerpt',
                'keywords',
            ],
        ]);

        $json = $response->json();
        $this->assertNotEmpty($json);

        // Guru should see guru-workflow in search results
        $titles = collect($json)->pluck('title')->toArray();
        $this->assertContains('05. Alur Kerja Guru & Asatidz', $titles);
    }

    public function test_documentation_caching_caches_parsed_content(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Cache']);
        $admin->assignRole('Administrator');

        /** @var DocumentationService $service */
        $service = app(DocumentationService::class);

        $doc1 = $service->getDocument('user-guide', 'pengenalan-sistem', $admin);
        $this->assertNotNull($doc1);

        // Fetch again, should match
        $doc2 = $service->getDocument('user-guide', 'pengenalan-sistem', $admin);
        $this->assertEquals($doc1['title'], $doc2['title']);
        $this->assertEquals($doc1['last_modified'], $doc2['last_modified']);
    }

    public function test_navigation_resolves_previous_and_next_documents(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Nav']);
        $admin->assignRole('Administrator');

        /** @var DocumentationService $service */
        $service = app(DocumentationService::class);

        $nav = $service->getNavigation($admin, 'user-guide', 'role-dan-hak-akses');

        // Document before role-dan-hak-akses is pengenalan-sistem
        $this->assertNotNull($nav['prev']);
        $this->assertStringContainsString('Pengenalan Sistem', $nav['prev']['title']);

        // Document after role-dan-hak-akses is initial-system-setup
        $this->assertNotNull($nav['next']);
        $this->assertStringContainsString('Pengaturan Awal Sistem', $nav['next']['title']);
    }

    public function test_onboarding_quickstart_section_visible_on_homepage(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Onboarding']);
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('documentation.index'));

        $response->assertStatus(200);
        $response->assertSee('Mulai Menggunakan DIGITREN');
        $response->assertSee('Mulai Dari Awal');
        $response->assertSee('Panduan Guru');
        $response->assertSee('Panduan Santri');
        $response->assertSee('Mulai Setup');
        $response->assertSee('Buka Panduan Guru');
        $response->assertSee('Buka Panduan Santri');
    }

    public function test_onboarding_document_renders_frontmatter_metadata(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Reader']);
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('documentation.show', [
            'category' => 'onboarding',
            'slug' => 'administrator-first-setup',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Panduan Pertama Administrator');
        $response->assertSee('Tingkat: Beginner');
        $response->assertSee('v5.8.8.2');
        $response->assertSee('15 menit baca');

        /** @var DocumentationService $service */
        $service = app(DocumentationService::class);
        $doc = $service->getDocument('onboarding', 'administrator-first-setup', $admin);

        $this->assertNotNull($doc);
        $this->assertEquals('beginner', $doc['difficulty']);
        $this->assertEquals('5.8.8.2', $doc['version']);
        $this->assertEquals(15, $doc['reading_time']);
        $this->assertArrayHasKey('frontmatter', $doc);
        $this->assertContains('Administrator', $doc['frontmatter']['role']);
    }

    public function test_start_here_document_renders_workflow_diagram(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Workflow']);
        $admin->assignRole('Administrator');

        $response = $this->actingAs($admin)->get(route('documentation.show', [
            'category' => 'user-guide',
            'slug' => 'start-here',
        ]));

        $response->assertStatus(200);
        $response->assertSee('Apa itu DIGITREN');
        $response->assertSee('Urutan Penggunaan Sistem');
        $response->assertSee('mermaid-diagram-card');
        $response->assertSee('Sistem Kosong');
        $response->assertSee('Profil Pondok');
        $response->assertSee('Learning Management System');
    }

    public function test_role_authorization_on_onboarding_documents(): void
    {
        $santri = User::factory()->create(['name' => 'Santri Onboarding']);
        $santri->assignRole('Santri');

        // Santri can access santri-first-use
        $responseSantri = $this->actingAs($santri)->get(route('documentation.show', [
            'category' => 'onboarding',
            'slug' => 'santri-first-use',
        ]));
        $responseSantri->assertStatus(200);
        $responseSantri->assertSee('Panduan Pertama Santri &amp; Wali', false);

        // Santri CANNOT access administrator-first-setup
        $responseForbidden = $this->actingAs($santri)->get(route('documentation.show', [
            'category' => 'onboarding',
            'slug' => 'administrator-first-setup',
        ]));
        $responseForbidden->assertStatus(404);

        // Guru can access guru-first-use but NOT administrator-first-setup
        $guru = User::factory()->create(['name' => 'Guru Onboarding']);
        $guru->assignRole('Guru');

        $responseGuru = $this->actingAs($guru)->get(route('documentation.show', [
            'category' => 'onboarding',
            'slug' => 'guru-first-use',
        ]));
        $responseGuru->assertStatus(200);
        $responseGuru->assertSee('Panduan Pertama Guru / Asatidz');

        $responseGuruForbidden = $this->actingAs($guru)->get(route('documentation.show', [
            'category' => 'onboarding',
            'slug' => 'administrator-first-setup',
        ]));
        $responseGuruForbidden->assertStatus(404);
    }
}
