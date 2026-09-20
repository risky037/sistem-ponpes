<?php

namespace Tests\Feature\Academic;

use App\Models\AcademicCalendarEvent;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AcademicCalendarEventTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $pengurus;

    protected User $santriUser;

    protected AcademicYear $academicYear;

    protected function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::firstOrCreate(['name' => 'Administrator', 'guard_name' => 'web']);
        $pengurusRole = Role::firstOrCreate(['name' => 'Pengurus', 'guard_name' => 'web']);
        $santriRole = Role::firstOrCreate(['name' => 'Santri', 'guard_name' => 'web']);

        $this->admin = User::create([
            'name' => 'Admin Kalender',
            'email' => 'admin.kalender@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->admin->assignRole($adminRole);

        $this->pengurus = User::create([
            'name' => 'Pengurus Kalender',
            'email' => 'pengurus.kalender@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->pengurus->assignRole($pengurusRole);

        $this->santriUser = User::create([
            'name' => 'Santri Kalender',
            'email' => 'santri.kalender@example.com',
            'password' => Hash::make('Secret123!'),
        ]);
        $this->santriUser->assignRole($santriRole);

        $this->academicYear = AcademicYear::create([
            'name' => '2024/2025',
            'semester' => 'Ganjil',
            'start_date' => '2024-07-15',
            'end_date' => '2024-12-20',
            'is_active' => true,
        ]);
    }

    public function test_calendar_event_index_renders_for_authorized_users(): void
    {
        $response = $this->actingAs($this->admin)->get(route('academic-calendar-event.index'));
        $response->assertStatus(200);
        $response->assertSee('Kalender Akademik');

        $response = $this->actingAs($this->pengurus)->get(route('academic-calendar-event.index'));
        $response->assertStatus(200);

        $response = $this->actingAs($this->santriUser)->get(route('academic-calendar-event.index'));
        $response->assertStatus(403);
    }

    public function test_calendar_event_datatable_ajax_returns_json(): void
    {
        AcademicCalendarEvent::create([
            'academic_year_id' => $this->academicYear->id,
            'title' => 'Ujian Tengah Semester',
            'event_type' => 'Ujian',
            'start_date' => '2024-09-23',
            'end_date' => '2024-09-28',
            'description' => 'UTS Semester Ganjil',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson(route('academic-calendar-event.index'), ['X-Requested-With' => 'XMLHttpRequest']);

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'recordsTotal']);
        $this->assertStringContainsString('Ujian Tengah Semester', $response->getContent());
        $this->assertStringContainsString('UTS Semester Ganjil', $response->getContent());
    }

    public function test_store_creates_calendar_event_successfully(): void
    {
        $response = $this->actingAs($this->admin)->post(route('academic-calendar-event.store'), [
            'academic_year_id' => $this->academicYear->id,
            'title' => 'Libur Maulid Nabi',
            'event_type' => 'Libur',
            'start_date' => '2024-09-16',
            'end_date' => '2024-09-16',
            'description' => 'Libur peringatan Maulid Nabi SAW',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('academic_calendar_events', [
            'academic_year_id' => $this->academicYear->id,
            'title' => 'Libur Maulid Nabi',
            'event_type' => 'Libur',
            'start_date' => '2024-09-16 00:00:00',
            'end_date' => '2024-09-16 00:00:00',
        ]);
    }

    public function test_store_rejects_invalid_date_order(): void
    {
        $response = $this->actingAs($this->admin)->post(route('academic-calendar-event.store'), [
            'academic_year_id' => $this->academicYear->id,
            'title' => 'Event Tanggal Terbalik',
            'event_type' => 'Kegiatan',
            'start_date' => '2024-10-10',
            'end_date' => '2024-10-05',
        ]);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseMissing('academic_calendar_events', [
            'title' => 'Event Tanggal Terbalik',
        ]);
    }

    public function test_store_rejects_invalid_event_type(): void
    {
        $response = $this->actingAs($this->admin)->post(route('academic-calendar-event.store'), [
            'academic_year_id' => $this->academicYear->id,
            'title' => 'Event Tipe Salah',
            'event_type' => 'NonExistentType',
            'start_date' => '2024-10-10',
            'end_date' => '2024-10-11',
        ]);

        $response->assertSessionHasErrors(['event_type']);
    }

    public function test_update_modifies_calendar_event(): void
    {
        $event = AcademicCalendarEvent::create([
            'academic_year_id' => $this->academicYear->id,
            'title' => 'Awal KBM Awal',
            'event_type' => 'Awal Semester',
            'start_date' => '2024-07-20',
            'end_date' => '2024-07-20',
        ]);

        $response = $this->actingAs($this->admin)->put(route('academic-calendar-event.update', $event->id), [
            'title' => 'Awal KBM Direvisi',
            'event_type' => 'Awal Semester',
            'start_date' => '2024-07-22',
            'end_date' => '2024-07-22',
            'description' => 'Diundur 2 hari',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('academic_calendar_events', [
            'id' => $event->id,
            'title' => 'Awal KBM Direvisi',
            'description' => 'Diundur 2 hari',
        ]);
    }

    public function test_destroy_deletes_calendar_event(): void
    {
        $event = AcademicCalendarEvent::create([
            'academic_year_id' => $this->academicYear->id,
            'title' => 'Event Hapus',
            'event_type' => 'Kegiatan',
            'start_date' => '2024-11-01',
            'end_date' => '2024-11-01',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic-calendar-event.destroy', $event->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('academic_calendar_events', [
            'id' => $event->id,
        ]);
    }

    public function test_academic_year_cannot_be_deleted_when_calendar_event_exists(): void
    {
        AcademicCalendarEvent::create([
            'academic_year_id' => $this->academicYear->id,
            'title' => 'Event Pelindung Tahun Ajaran',
            'event_type' => 'Kegiatan',
            'start_date' => '2024-08-01',
            'end_date' => '2024-08-01',
        ]);

        $response = $this->actingAs($this->admin)->delete(route('academic-year.destroy', $this->academicYear->id));
        $response->assertRedirect();
        $this->assertDatabaseHas('academic_years', ['id' => $this->academicYear->id]);
    }
}
