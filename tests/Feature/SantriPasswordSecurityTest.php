<?php

namespace Tests\Feature;

use App\Models\Kabupaten;
use App\Models\Kamar;
use App\Models\Kecamatan;
use App\Models\Kelas;
use App\Models\Kelurahan;
use App\Models\Provinsi;
use App\Models\Santri;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SantriPasswordSecurityTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected Kelas $kelas;

    protected Kamar $kamar;

    protected Provinsi $provinsi;

    protected Kabupaten $kabupaten;

    protected Kecamatan $kecamatan;

    protected Kelurahan $kelurahan;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        Setting::firstOrCreate([
            'log_activity' => false,
        ]);

        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('AdminSecret123!'),
        ]);
        $this->admin->assignRole('Administrator');

        $this->kelas = Kelas::create([
            'kode' => 'K1A',
            'kelas' => '1A',
            'tingkatan' => 'Ula',
        ]);

        $this->kamar = Kamar::create([
            'kode' => 'KMR-A',
            'nama' => 'Kamar Abu Bakar',
            'blok' => 'A',
            'maksimal_santri' => 10,
            'jumlah_santri' => 0,
        ]);

        $this->provinsi = Provinsi::create(['name' => 'Jawa Timur']);
        $this->kabupaten = Kabupaten::create([
            'provinsi_id' => $this->provinsi->id,
            'name' => 'Surabaya',
        ]);
        $this->kecamatan = Kecamatan::create([
            'kabupaten_id' => $this->kabupaten->id,
            'name' => 'Wonokromo',
        ]);
        $this->kelurahan = Kelurahan::create([
            'kecamatan_id' => $this->kecamatan->id,
            'name' => 'Darmo',
        ]);
    }

    /**
     * Helper to create a santri with linked user.
     */
    protected function createSantriWithUser(string $initialPassword = 'InitialPassword123!'): array
    {
        $santriUser = User::factory()->create([
            'name' => 'Santri Original Name',
            'email' => 'santri_original@example.com',
            'password' => Hash::make($initialPassword),
        ]);
        $santriUser->assignRole('Santri');

        $santri = Santri::create([
            'no_induk' => '20230001',
            'user_id' => $santriUser->id,
            'jenis_kelamin' => 'Laki-Laki',
            'nik' => '3578012345670001',
            'kk' => '3578012345670002',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2005-01-01',
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => '2023-07-01',
            'tahun_masuk_hijriyah' => '1444',
            'status' => 'Santri Aktif',
            'maksimal_perizinan' => 10,
            'foto' => 'santri.png',
        ]);

        return [$santri, $santriUser];
    }

    /**
     * Helper to build valid form payload for santri update.
     */
    protected function getValidUpdatePayload(array $overrides = []): array
    {
        return array_merge([
            'kelas' => $this->kelas->id,
            'kamar' => $this->kamar->id,
            'nama_lengkap' => 'Santri Updated Name',
            'provinsi_id' => $this->provinsi->id,
            'kabupaten_id' => $this->kabupaten->id,
            'kecamatan_id' => $this->kecamatan->id,
            'kelurahan_id' => $this->kelurahan->id,
            'dusun' => 'Dusun Krajan',
            'jenis_kelamin' => 'Laki-Laki',
            'whatsapp' => '081234567890',
            'tanggal_lahir' => '2005-01-01',
            'tempat_lahir' => 'Surabaya',
            'tahun_masuk' => '2023-07-01',
            'nama_ayah' => 'Ahmad',
            'nama_ibu' => 'Fatimah',
        ], $overrides);
    }

    /**
     * Scenario 1: Update santri without password field.
     * Expected: Existing password remains unchanged.
     */
    public function test_update_santri_without_password_field_preserves_existing_password(): void
    {
        [$santri, $santriUser] = $this->createSantriWithUser('SecretInitial123!');

        $payload = $this->getValidUpdatePayload([
            'nama_lengkap' => 'Santri Name Changed',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('santri.update', $santri->id), $payload);

        $response->assertRedirect();

        $santriUser->refresh();
        $this->assertTrue(
            Hash::check('SecretInitial123!', $santriUser->password),
            'Existing password must remain unchanged when password field is omitted.'
        );
        $this->assertFalse(
            Hash::check('password', $santriUser->password),
            'Password must not be reset to default password.'
        );
    }

    /**
     * Scenario 2: Update santri with new password.
     * Expected: Password updated and hashed.
     */
    public function test_update_santri_with_new_password_updates_and_hashes_password(): void
    {
        [$santri, $santriUser] = $this->createSantriWithUser('OldSecret123!');

        $payload = $this->getValidUpdatePayload([
            'password' => 'BrandNewStrongPass456!',
            'password_confirmation' => 'BrandNewStrongPass456!',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('santri.update', $santri->id), $payload);

        $response->assertRedirect();

        $santriUser->refresh();
        $this->assertTrue(
            Hash::check('BrandNewStrongPass456!', $santriUser->password),
            'Password must be updated to the new hashed value.'
        );
        $this->assertFalse(
            Hash::check('OldSecret123!', $santriUser->password),
            'Old password must no longer match.'
        );
    }

    /**
     * Scenario 3: Empty password submission.
     * Expected: No password modification.
     */
    public function test_empty_password_submission_preserves_existing_password(): void
    {
        [$santri, $santriUser] = $this->createSantriWithUser('KeepThisPassword789!');

        $payload = $this->getValidUpdatePayload([
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('santri.update', $santri->id), $payload);

        $response->assertRedirect();

        $santriUser->refresh();
        $this->assertTrue(
            Hash::check('KeepThisPassword789!', $santriUser->password),
            'Existing password must remain unchanged when empty password is submitted.'
        );
        $this->assertFalse(
            Hash::check('password', $santriUser->password),
            'Password must not be reset to default password on empty submission.'
        );
    }

    /**
     * Scenario 4: Unauthorized user cannot update.
     * Expected: HTTP 403 Forbidden for non-admin/non-pengurus, or redirect for guest.
     */
    public function test_unauthorized_user_cannot_update_santri(): void
    {
        [$santri, $santriUser] = $this->createSantriWithUser('OriginalSecurePass123!');

        $payload = $this->getValidUpdatePayload([
            'nama_lengkap' => 'Malicious Modification',
            'password' => 'MaliciousNewPassword123!',
            'password_confirmation' => 'MaliciousNewPassword123!',
        ]);

        // 4a. Authenticated user without Administrator or Pengurus role (e.g. regular Santri)
        $unauthorizedUser = User::factory()->create();
        $unauthorizedUser->assignRole('Santri');

        $response = $this->actingAs($unauthorizedUser)
            ->patch(route('santri.update', $santri->id), $payload);

        $response->assertForbidden();

        $santriUser->refresh();
        $this->assertTrue(
            Hash::check('OriginalSecurePass123!', $santriUser->password),
            'Password must not be modified by an unauthorized user.'
        );

        // 4b. Guest (unauthenticated)
        auth()->logout();
        $guestResponse = $this->patch(route('santri.update', $santri->id), $payload);
        $guestResponse->assertRedirect(); // redirected to login

        $santriUser->refresh();
        $this->assertTrue(
            Hash::check('OriginalSecurePass123!', $santriUser->password),
            'Password must not be modified by guest.'
        );
    }

    /**
     * Additional Security Test: Password confirmation mismatch causes validation error.
     */
    public function test_password_confirmation_mismatch_fails_validation(): void
    {
        [$santri, $santriUser] = $this->createSantriWithUser('ExistingPass123!');

        $payload = $this->getValidUpdatePayload([
            'password' => 'NewPassword123!',
            'password_confirmation' => 'DifferentPassword123!',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('santri.update', $santri->id), $payload);

        $response->assertSessionHasErrors('password');

        $santriUser->refresh();
        $this->assertTrue(
            Hash::check('ExistingPass123!', $santriUser->password),
            'Password must remain unchanged when confirmation fails.'
        );
    }

    /**
     * Additional Security Test: Password below minimum length fails validation.
     */
    public function test_password_below_minimum_length_fails_validation(): void
    {
        [$santri, $santriUser] = $this->createSantriWithUser('ExistingPass123!');

        $payload = $this->getValidUpdatePayload([
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response = $this->actingAs($this->admin)
            ->patch(route('santri.update', $santri->id), $payload);

        $response->assertSessionHasErrors('password');

        $santriUser->refresh();
        $this->assertTrue(
            Hash::check('ExistingPass123!', $santriUser->password),
            'Password must remain unchanged when minimum length requirement is not met.'
        );
    }
}
