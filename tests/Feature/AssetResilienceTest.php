<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class AssetResilienceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        $testStorageDir = storage_path('app/public/uploads/setting');
        if (File::exists($testStorageDir.'/test_logo.png')) {
            File::delete($testStorageDir.'/test_logo.png');
        }
        if (File::exists($testStorageDir.'/test_favicon.png')) {
            File::delete($testStorageDir.'/test_favicon.png');
        }

        parent::tearDown();
    }

    public function test_setting_logo_and_favicon_return_fallback_when_no_record_exists(): void
    {
        $this->assertDatabaseCount('settings', 0);

        $logoUrl = Setting::getLogoUrl();
        $faviconUrl = Setting::getFaviconUrl();

        $this->assertStringContainsString('assets/images/logo-icon.png', $logoUrl);
        $this->assertStringContainsString('assets/images/favicon-32x32.png', $faviconUrl);
    }

    public function test_setting_logo_and_favicon_return_fallback_when_files_missing_from_disk(): void
    {
        $setting = Setting::create([
            'logo' => 'non_existent_logo_12345.png',
            'favicon' => 'non_existent_favicon_12345.png',
            'log_activity' => true,
        ]);

        $logoUrl = Setting::getLogoUrl($setting);
        $faviconUrl = Setting::getFaviconUrl($setting);

        $this->assertStringContainsString('assets/images/logo-icon.png', $logoUrl);
        $this->assertStringContainsString('assets/images/favicon-32x32.png', $faviconUrl);

        // Test instance helpers as well
        $this->assertStringContainsString('assets/images/logo-icon.png', $setting->logoUrl());
        $this->assertStringContainsString('assets/images/favicon-32x32.png', $setting->faviconUrl());
    }

    public function test_setting_logo_returns_valid_url_when_file_exists_on_disk(): void
    {
        $dir = storage_path('app/public/uploads/setting');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($dir.'/test_logo.png', 'fake-image-content');

        $setting = Setting::create([
            'logo' => 'test_logo.png',
            'favicon' => null,
            'log_activity' => true,
        ]);

        $logoUrl = Setting::getLogoUrl($setting);
        $this->assertStringContainsString('uploads/setting/test_logo.png', $logoUrl);
    }

    public function test_storage_fallback_route_serves_existing_public_file(): void
    {
        $dir = storage_path('app/public/uploads/setting');
        if (! File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        File::put($dir.'/test_logo.png', 'dummy-pixel-data');

        $response = $this->get('/storage/uploads/setting/test_logo.png');

        $response->assertStatus(200);
        $response->assertHeader('Cache-Control', 'max-age=86400, public');
    }

    public function test_storage_fallback_route_returns_404_for_missing_file(): void
    {
        $response = $this->get('/storage/uploads/setting/definitely_missing_file_9999.png');

        $response->assertStatus(404);
    }

    public function test_storage_fallback_route_blocks_directory_traversal(): void
    {
        $response = $this->get('/storage/../../../.env');

        // Path traversal is normalized and rejected (404 or 403)
        $this->assertTrue(in_array($response->status(), [403, 404]));
    }
}
