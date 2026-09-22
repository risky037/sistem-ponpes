<?php

namespace Database\Seeders;

use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AlamatSantri;
use App\Models\AssessmentComponent;
use App\Models\AssessmentDefinition;
use App\Models\AttendanceRecord;
use App\Models\ClassSchedule;
use App\Models\Kamar;
use App\Models\KamarSantri;
use App\Models\Kelas;
use App\Models\KelasSantri;
use App\Models\Mapel;
use App\Models\Santri;
use App\Models\StudentAssessmentScore;
use App\Models\StudentBatch;
use App\Models\Tabungan;
use App\Models\TeachingAssignment;
use App\Models\TeachingSession;
use App\Models\TransaksiTabungan;
use App\Models\User;
use App\Models\WaliKelasAssignment;
use App\Models\WaliSantri;
use App\Services\Academic\AcademicPerformanceService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    public static int $defaultSantriCount = 20;

    protected int $santriCount;

    public function __construct(?int $santriCount = null)
    {
        $this->santriCount = $santriCount ?? static::$defaultSantriCount;
    }

    public function run(): void
    {
        // 1. Ensure Roles & Permissions exist
        $this->call(RolePermissionSeeder::class);

        // 2. Base Users for all Roles
        $users = $this->seedCoreUsers();

        // 3. Academic Foundation (Years & Batches)
        $academicData = $this->seedAcademicFoundation();
        $activeYear = $academicData['active_year'];
        $batch2023 = $academicData['batch_2023'];
        $batch2024 = $academicData['batch_2024'];

        // 4. Academic Structure (Classes & Rooms)
        $structure = $this->seedAcademicStructure();
        $classes = $structure['classes'];
        $rooms = $structure['rooms'];

        // 5. Assign Wali Kelas
        $this->seedWaliKelas($classes, $users['teachers'], $activeYear);

        // 6. Curriculum & Subjects (Mapel)
        $subjects = $this->seedSubjects($classes);

        // 7. Teaching Assignments
        $assignments = $this->seedTeachingAssignments($classes, $subjects, $users['teachers'], $activeYear);

        // 8. Class Schedules
        $this->seedClassSchedules($assignments, $activeYear);

        // 9. Teaching Sessions
        $sessions = $this->seedTeachingSessions($assignments);

        // 10. Assessment Definitions
        $definitions = $this->seedAssessmentDefinitions($activeYear);

        // 11. Assessment Components
        $components = $this->seedAssessmentComponents($assignments, $definitions);

        // 12. Santri Population & Enrollments
        $enrollments = $this->seedSantriPopulation(
            $classes,
            $rooms,
            [$batch2023, $batch2024],
            $activeYear
        );

        // 13. Attendance Records for Sessions
        $this->seedAttendanceRecords($sessions, $enrollments);

        // 14. Student Assessment Scores
        $this->seedAssessmentScores($components, $enrollments, $users['teachers']);

        // 15. Generate Academic Performance Summaries
        $this->generatePerformanceSummaries($activeYear);
    }

    protected function seedCoreUsers(): array
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@pesantren.test'],
            [
                'name' => 'Administrator Sistem',
                'password' => Hash::make('password'),
            ]
        );
        if (! $admin->hasRole('Administrator')) {
            $admin->assignRole('Administrator');
        }

        $pengurus = User::firstOrCreate(
            ['email' => 'pengurus@pesantren.test'],
            [
                'name' => 'Ust. H. Abdullah Mansur (Pengurus)',
                'password' => Hash::make('password'),
            ]
        );
        if (! $pengurus->hasRole('Pengurus')) {
            $pengurus->assignRole('Pengurus');
        }

        $keuangan = User::firstOrCreate(
            ['email' => 'keuangan@pesantren.test'],
            [
                'name' => 'Hj. Siti Mariam (Bendahara)',
                'password' => Hash::make('password'),
            ]
        );
        if (! $keuangan->hasRole('Keuangan')) {
            $keuangan->assignRole('Keuangan');
        }

        $teachers = [
            'ahmad' => User::firstOrCreate(
                ['email' => 'guru.ahmad@pesantren.test'],
                [
                    'name' => 'Ust. Ahmad Fauzi, S.Pd.I',
                    'password' => Hash::make('password'),
                ]
            ),
            'hasan' => User::firstOrCreate(
                ['email' => 'guru.hasan@pesantren.test'],
                [
                    'name' => 'Ust. Muhammad Hasan, Lc.',
                    'password' => Hash::make('password'),
                ]
            ),
            'nur' => User::firstOrCreate(
                ['email' => 'guru.nur@pesantren.test'],
                [
                    'name' => 'Usth. Nur Laili, M.Pd.',
                    'password' => Hash::make('password'),
                ]
            ),
        ];

        foreach ($teachers as $teacher) {
            if (! $teacher->hasRole('Guru')) {
                $teacher->assignRole('Guru');
            }
        }

        return [
            'admin' => $admin,
            'pengurus' => $pengurus,
            'keuangan' => $keuangan,
            'teachers' => $teachers,
        ];
    }

    protected function seedAcademicFoundation(): array
    {
        // Deactivate existing years first to keep exactly one active year
        AcademicYear::where('is_active', true)->update(['is_active' => false]);

        $activeYear = AcademicYear::firstOrCreate(
            ['name' => '2024/2025', 'semester' => 'Genap'],
            [
                'start_date' => '2025-01-06',
                'end_date' => '2025-06-20',
                'is_active' => true,
            ]
        );
        $activeYear->update(['is_active' => true]);

        AcademicYear::firstOrCreate(
            ['name' => '2024/2025', 'semester' => 'Ganjil'],
            [
                'start_date' => '2024-07-15',
                'end_date' => '2024-12-20',
                'is_active' => false,
            ]
        );

        $batch2023 = StudentBatch::firstOrCreate(
            ['year' => 2023],
            [
                'name' => 'Angkatan 2023 (1444 H)',
                'description' => 'Santri masuk angkatan tahun 2023',
            ]
        );

        $batch2024 = StudentBatch::firstOrCreate(
            ['year' => 2024],
            [
                'name' => 'Angkatan 2024 (1445 H)',
                'description' => 'Santri masuk angkatan tahun 2024',
            ]
        );

        return [
            'active_year' => $activeYear,
            'batch_2023' => $batch2023,
            'batch_2024' => $batch2024,
        ];
    }

    protected function seedAcademicStructure(): array
    {
        $classes = [
            '1A' => Kelas::firstOrCreate(
                ['kode' => 'KLS-1A-WST'],
                [
                    'tingkatan' => 'WUSTHO',
                    'kelas' => '1A Wustho',
                    'keterangan' => 'Kelas 1 Wustho Putra - Regular',
                ]
            ),
            '1B' => Kelas::firstOrCreate(
                ['kode' => 'KLS-1B-WST'],
                [
                    'tingkatan' => 'WUSTHO',
                    'kelas' => '1B Wustho',
                    'keterangan' => 'Kelas 1 Wustho Putri - Regular',
                ]
            ),
            '2A' => Kelas::firstOrCreate(
                ['kode' => 'KLS-2A-WST'],
                [
                    'tingkatan' => 'WUSTHO',
                    'kelas' => '2A Wustho',
                    'keterangan' => 'Kelas 2 Wustho - Lanjutan',
                ]
            ),
        ];

        $rooms = [
            'abu_bakar' => Kamar::firstOrCreate(
                ['kode' => 'KMR-ABU-01'],
                [
                    'nama' => 'Kamar Abu Bakar',
                    'blok' => 'A',
                    'maksimal_santri' => 12,
                    'jumlah_santri' => 0,
                ]
            ),
            'umar' => Kamar::firstOrCreate(
                ['kode' => 'KMR-UMR-02'],
                [
                    'nama' => 'Kamar Umar bin Khattab',
                    'blok' => 'B',
                    'maksimal_santri' => 12,
                    'jumlah_santri' => 0,
                ]
            ),
            'khadijah' => Kamar::firstOrCreate(
                ['kode' => 'KMR-KHD-03'],
                [
                    'nama' => 'Kamar Khadijah',
                    'blok' => 'C',
                    'maksimal_santri' => 12,
                    'jumlah_santri' => 0,
                ]
            ),
        ];

        return ['classes' => $classes, 'rooms' => $rooms];
    }

    protected function seedWaliKelas(array $classes, array $teachers, AcademicYear $year): void
    {
        WaliKelasAssignment::firstOrCreate(
            [
                'kelas_id' => $classes['1A']->id,
                'academic_year_id' => $year->id,
            ],
            ['user_id' => $teachers['ahmad']->id]
        );

        WaliKelasAssignment::firstOrCreate(
            [
                'kelas_id' => $classes['1B']->id,
                'academic_year_id' => $year->id,
            ],
            ['user_id' => $teachers['hasan']->id]
        );

        WaliKelasAssignment::firstOrCreate(
            [
                'kelas_id' => $classes['2A']->id,
                'academic_year_id' => $year->id,
            ],
            ['user_id' => $teachers['nur']->id]
        );
    }

    protected function seedSubjects(array $classes): array
    {
        $subjects = [];

        // Subjects for 1A Wustho
        $subjects['1A_fiqih'] = Mapel::firstOrCreate(
            ['code' => 'MPL-FIQ-1A'],
            [
                'kelas_id' => $classes['1A']->id,
                'name' => 'Fiqih (Fathul Qorib)',
                'description' => 'Kajian Fiqih Ibadah dan Muamalah',
                'is_active' => true,
            ]
        );

        $subjects['1A_nahwu'] = Mapel::firstOrCreate(
            ['code' => 'MPL-NHW-1A'],
            [
                'kelas_id' => $classes['1A']->id,
                'name' => 'Nahwu (Al-Jurumiyyah)',
                'description' => 'Tata Bahasa Arab Dasar',
                'is_active' => true,
            ]
        );

        $subjects['1A_akhlak'] = Mapel::firstOrCreate(
            ['code' => 'MPL-AKH-1A'],
            [
                'kelas_id' => $classes['1A']->id,
                'name' => 'Akhlak (Ta\'lim Muta\'allim)',
                'description' => 'Etika dan Adab Penuntut Ilmu',
                'is_active' => true,
            ]
        );

        // Subjects for 1B Wustho
        $subjects['1B_fiqih'] = Mapel::firstOrCreate(
            ['code' => 'MPL-FIQ-1B'],
            [
                'kelas_id' => $classes['1B']->id,
                'name' => 'Fiqih (Fathul Qorib)',
                'description' => 'Kajian Fiqih Ibadah Putri',
                'is_active' => true,
            ]
        );

        // Subjects for 2A Wustho
        $subjects['2A_nahwu'] = Mapel::firstOrCreate(
            ['code' => 'MPL-NHW-2A'],
            [
                'kelas_id' => $classes['2A']->id,
                'name' => 'Nahwu (Imrithi)',
                'description' => 'Kajian Nadhom Imrithi',
                'is_active' => true,
            ]
        );

        return $subjects;
    }

    protected function seedTeachingAssignments(
        array $classes,
        array $subjects,
        array $teachers,
        AcademicYear $year
    ): array {
        return [
            '1A_fiqih' => TeachingAssignment::firstOrCreate(
                [
                    'kelas_id' => $classes['1A']->id,
                    'mapel_id' => $subjects['1A_fiqih']->id,
                    'academic_year_id' => $year->id,
                ],
                [
                    'user_id' => $teachers['ahmad']->id,
                    'status' => 'Aktif',
                    'notes' => 'Pengampu Fiqih Kelas 1A',
                ]
            ),
            '1A_nahwu' => TeachingAssignment::firstOrCreate(
                [
                    'kelas_id' => $classes['1A']->id,
                    'mapel_id' => $subjects['1A_nahwu']->id,
                    'academic_year_id' => $year->id,
                ],
                [
                    'user_id' => $teachers['hasan']->id,
                    'status' => 'Aktif',
                    'notes' => 'Pengampu Nahwu Kelas 1A',
                ]
            ),
            '1A_akhlak' => TeachingAssignment::firstOrCreate(
                [
                    'kelas_id' => $classes['1A']->id,
                    'mapel_id' => $subjects['1A_akhlak']->id,
                    'academic_year_id' => $year->id,
                ],
                [
                    'user_id' => $teachers['nur']->id,
                    'status' => 'Aktif',
                    'notes' => 'Pengampu Akhlak Kelas 1A',
                ]
            ),
            '1B_fiqih' => TeachingAssignment::firstOrCreate(
                [
                    'kelas_id' => $classes['1B']->id,
                    'mapel_id' => $subjects['1B_fiqih']->id,
                    'academic_year_id' => $year->id,
                ],
                [
                    'user_id' => $teachers['ahmad']->id,
                    'status' => 'Aktif',
                    'notes' => 'Pengampu Fiqih Kelas 1B',
                ]
            ),
            '2A_nahwu' => TeachingAssignment::firstOrCreate(
                [
                    'kelas_id' => $classes['2A']->id,
                    'mapel_id' => $subjects['2A_nahwu']->id,
                    'academic_year_id' => $year->id,
                ],
                [
                    'user_id' => $teachers['hasan']->id,
                    'status' => 'Aktif',
                    'notes' => 'Pengampu Nahwu Kelas 2A',
                ]
            ),
        ];
    }

    protected function seedClassSchedules(array $assignments, AcademicYear $year): void
    {
        $scheduleDefs = [
            [
                'assignment' => $assignments['1A_fiqih'],
                'day' => 'Senin',
                'start' => '07:30:00',
                'end' => '09:00:00',
                'room' => 'Ruang 1A',
            ],
            [
                'assignment' => $assignments['1A_nahwu'],
                'day' => 'Selasa',
                'start' => '07:30:00',
                'end' => '09:00:00',
                'room' => 'Ruang 1A',
            ],
            [
                'assignment' => $assignments['1A_akhlak'],
                'day' => 'Rabu',
                'start' => '07:30:00',
                'end' => '09:00:00',
                'room' => 'Ruang 1A',
            ],
            [
                'assignment' => $assignments['1B_fiqih'],
                'day' => 'Kamis',
                'start' => '07:30:00',
                'end' => '09:00:00',
                'room' => 'Ruang 1B',
            ],
            [
                'assignment' => $assignments['2A_nahwu'],
                'day' => 'Jumat',
                'start' => '07:30:00',
                'end' => '09:00:00',
                'room' => 'Ruang 2A',
            ],
        ];

        foreach ($scheduleDefs as $s) {
            ClassSchedule::firstOrCreate(
                [
                    'kelas_id' => $s['assignment']->kelas_id,
                    'teaching_assignment_id' => $s['assignment']->id,
                    'academic_year_id' => $year->id,
                    'day_of_week' => $s['day'],
                    'start_time' => $s['start'],
                ],
                [
                    'end_time' => $s['end'],
                    'room' => $s['room'],
                    'notes' => 'Jadwal Reguler',
                ]
            );
        }
    }

    protected function seedTeachingSessions(array $assignments): array
    {
        $sessions = [];
        $assignment1AFiqih = $assignments['1A_fiqih'];

        $sessionDates = [
            '2025-01-13' => 'Materi Bab 1: Thaharah dan Macam-macam Air',
            '2025-01-20' => 'Materi Bab 2: Wudhu dan Syarat Sahnya',
            '2025-01-27' => 'Praktik Wudhu dan Tayammum',
            '2025-02-03' => 'Materi Bab 3: Shalat Wajib dan Rukun-rukunnya',
        ];

        foreach ($sessionDates as $date => $topic) {
            $sessions[] = TeachingSession::firstOrCreate(
                [
                    'teaching_assignment_id' => $assignment1AFiqih->id,
                    'session_date' => $date,
                ],
                [
                    'status' => 'Completed',
                    'notes' => $topic,
                ]
            );
        }

        return $sessions;
    }

    protected function seedAssessmentDefinitions(AcademicYear $year): array
    {
        return [
            'tugas' => AssessmentDefinition::firstOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'name' => 'Tugas Harian',
                ],
                [
                    'type' => AssessmentDefinition::TYPE_TUGAS,
                    'weight' => 20,
                    'is_active' => true,
                ]
            ),
            'harian' => AssessmentDefinition::firstOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'name' => 'Ulangan Harian',
                ],
                [
                    'type' => AssessmentDefinition::TYPE_HARIAN,
                    'weight' => 20,
                    'is_active' => true,
                ]
            ),
            'uts' => AssessmentDefinition::firstOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'name' => 'Penilaian Tengah Semester (PTS)',
                ],
                [
                    'type' => AssessmentDefinition::TYPE_UTS,
                    'weight' => 30,
                    'is_active' => true,
                ]
            ),
            'uas' => AssessmentDefinition::firstOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'name' => 'Penilaian Akhir Semester (PAS)',
                ],
                [
                    'type' => AssessmentDefinition::TYPE_UAS,
                    'weight' => 30,
                    'is_active' => true,
                ]
            ),
        ];
    }

    protected function seedAssessmentComponents(array $assignments, array $definitions): array
    {
        $components = [];
        $assignment = $assignments['1A_fiqih'];

        foreach ($definitions as $key => $def) {
            $components[$key] = AssessmentComponent::firstOrCreate(
                [
                    'teaching_assignment_id' => $assignment->id,
                    'assessment_definition_id' => $def->id,
                ],
                [
                    'weight' => $def->weight,
                ]
            );
        }

        return $components;
    }

    protected function seedSantriPopulation(
        array $classes,
        array $rooms,
        array $batches,
        AcademicYear $year
    ): array {
        $boyNames = [
            'Muhammad Ali Zainal',
            'Ahmad Fauzan Robbani',
            'Muhammad Rizky Pratama',
            'M. Hasan Bashri',
            'Bilal Ibnu Rabah',
            'Salman Al-Farisi',
            'Zaid bin Tsabit',
            'Abdullah Manshur',
            'Farhan Al-Ghifari',
            'Yusuf Hamdani',
            'Hanif Nur Rahman',
            'Ilham Bintang Ramadhan',
            'Fadhil Makarim',
            'Rayhan Kurniawan',
            'Ridho Maulana',
        ];

        $girlNames = [
            'Fatimah Az-Zahra',
            'Siti Aisyah Humaira',
            'Khadijah Al-Kubra',
            'Maryam Jamilah',
            'Nabila Zahratun Nisa',
            'Salma Salsabila',
            'Annisa Rahmawati',
            'Yasmin Al-Munawwarah',
            'Hafshah Nur Azizah',
            'Halimah As-Sa\'diyah',
            'Laila Majidah',
            'Safiyyah binti Huyay',
            'Rahmatika Putri',
            'Dewi Sartika Maulida',
            'Zulaikha Azzahra',
        ];

        $classList = array_values($classes);
        $roomList = array_values($rooms);
        $enrollments = [];

        $totalSantri = max(5, $this->santriCount);

        for ($i = 0; $i < $totalSantri; $i++) {
            $isBoy = ($i % 2 === 0);
            $gender = $isBoy ? 'Laki-Laki' : 'Perempuan';
            $namePool = $isBoy ? $boyNames : $girlNames;
            $nameIdx = (int) floor($i / 2) % count($namePool);
            $suffix = ($i >= count($boyNames) * 2) ? ' '.($i + 1) : '';
            $fullName = $namePool[$nameIdx].$suffix;

            $email = 'santri.'.($i + 1).'@pesantren.test';

            $user = User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $fullName,
                    'password' => Hash::make('password'),
                ]
            );

            if (! $user->hasRole('Santri')) {
                $user->assignRole('Santri');
            }

            // Assign batch: earlier half to 2023, later half to 2024
            $batch = ($i % 3 === 0) ? $batches[0] : $batches[1];

            // Assign class: first 10 go to 1A Wustho, next to 1B, then 2A
            if ($i < 10) {
                $assignedClass = $classes['1A'];
            } elseif ($i < 15) {
                $assignedClass = $classes['1B'];
            } else {
                $assignedClass = $classes['2A'];
            }

            // Assign room based on gender / distribution
            if ($isBoy) {
                $assignedRoom = ($i % 4 === 0) ? $rooms['abu_bakar'] : $rooms['umar'];
            } else {
                $assignedRoom = $rooms['khadijah'];
            }

            $noInduk = '1445'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT);
            $nik = '3529'.str_pad((string) ($i + 1), 12, '0', STR_PAD_LEFT);
            $kk = '3529'.str_pad((string) ($i + 100), 12, '0', STR_PAD_LEFT);

            $santri = Santri::firstOrCreate(
                ['no_induk' => $noInduk],
                [
                    'user_id' => $user->id,
                    'student_batch_id' => $batch->id,
                    'nik' => $nik,
                    'kk' => $kk,
                    'jenis_kelamin' => $gender,
                    'tempat_lahir' => $isBoy ? 'Surabaya' : 'Malang',
                    'tanggal_lahir' => Carbon::now()->subYears(14 + ($i % 3))->format('Y-m-d'),
                    'status' => 'Santri Aktif',
                    'tahun_masuk' => '2024-07-15',
                    'tahun_masuk_hijriyah' => '1445-01-01',
                    'whatsapp' => (int) ('6281234567'.str_pad((string) $i, 3, '0', STR_PAD_LEFT)),
                    'foto' => 'santri.png',
                ]
            );

            // Wali Santri
            WaliSantri::firstOrCreate(
                ['santri_id' => $santri->id],
                [
                    'nama_ayah' => 'Bapak '.explode(' ', $fullName)[0],
                    'nama_ibu' => 'Ibu '.($isBoy ? 'Siti Aminah' : 'Khadijah'),
                ]
            );

            // Alamat Santri
            AlamatSantri::firstOrCreate(
                ['santri_id' => $santri->id],
                [
                    'alamat_lengkap' => 'Jl. Pesantren No. '.($i + 10).', RT 02/RW 01, Jawa Timur',
                ]
            );

            // Kelas Santri
            KelasSantri::updateOrCreate(
                ['santri_id' => $santri->id],
                ['kelas_id' => $assignedClass->id]
            );

            // Kamar Santri
            KamarSantri::updateOrCreate(
                ['santri_id' => $santri->id],
                ['kamar_id' => $assignedRoom->id]
            );

            // Tabungan & Transaksi Tabungan (Initial balance)
            $saldoAwal = 350000 + (($i % 5) * 150000);
            $tabungan = Tabungan::firstOrCreate(
                ['santri_id' => $santri->id],
                [
                    'saldo' => $saldoAwal,
                    'keterangan' => 'Rekening Tabungan Santri',
                ]
            );

            TransaksiTabungan::firstOrCreate(
                [
                    'santri_id' => $santri->id,
                    'jenis_transaksi' => 'Setoran',
                    'tanggal_transaksi' => '2025-01-10',
                ],
                [
                    'tujuan' => 'Setoran Awal Tabungan',
                    'jumlah_transaksi' => $saldoAwal,
                    'saldo_sebelumnya' => 0,
                    'saldo_saatini' => $saldoAwal,
                    'keterangan' => 'Pembukaan rekening tabungan santri baru',
                ]
            );

            // Academic Enrollment
            $enrollment = AcademicEnrollment::firstOrCreate(
                [
                    'academic_year_id' => $year->id,
                    'santri_id' => $santri->id,
                ],
                [
                    'kelas_id' => $assignedClass->id,
                    'status' => 'Aktif',
                    'enrolled_at' => '2025-01-06',
                    'notes' => 'Pendaftaran Semester Genap',
                ]
            );

            $enrollments[] = $enrollment;
        }

        // Update kamar occupancy count
        foreach ($rooms as $room) {
            $count = KamarSantri::where('kamar_id', $room->id)->count();
            $room->update(['jumlah_santri' => $count]);
        }

        return $enrollments;
    }

    protected function seedAttendanceRecords(array $sessions, array $enrollments): void
    {
        // Enrolled students in 1A Wustho
        $class1AEnrollments = array_filter($enrollments, function ($e) {
            return $e->kelas?->kode === 'KLS-1A-WST' || $e->kelas_id === 1;
        });

        foreach ($sessions as $sessionIdx => $session) {
            foreach ($class1AEnrollments as $idx => $enrollment) {
                // Determine realistic status:
                // Mostly Hadir (~90%), occasionally Izin or Sakit
                $status = AttendanceRecord::STATUS_HADIR;
                $notes = null;

                if (($idx + $sessionIdx) % 7 === 0) {
                    $status = AttendanceRecord::STATUS_IZIN;
                    $notes = 'Izin keperluan keluarga mendesak';
                } elseif (($idx + $sessionIdx) % 11 === 0) {
                    $status = AttendanceRecord::STATUS_SAKIT;
                    $notes = 'Istirahat di poskestren karena demam';
                }

                AttendanceRecord::firstOrCreate(
                    [
                        'teaching_session_id' => $session->id,
                        'academic_enrollment_id' => $enrollment->id,
                    ],
                    [
                        'status' => $status,
                        'notes' => $notes,
                        'marked_at' => Carbon::parse($session->session_date)->setTime(8, 30),
                        'marked_by' => $session->teachingAssignment?->user_id,
                    ]
                );
            }
        }
    }

    protected function seedAssessmentScores(array $components, array $enrollments, array $teachers): void
    {
        $class1AEnrollments = array_filter($enrollments, function ($e) {
            return $e->kelas?->kode === 'KLS-1A-WST' || $e->kelas_id === 1;
        });

        $baseScores = [
            'tugas' => 88.00,
            'harian' => 84.00,
            'uts' => 82.00,
            'uas' => 86.00,
        ];

        foreach ($components as $key => $comp) {
            $base = $baseScores[$key] ?? 85.00;

            foreach ($class1AEnrollments as $idx => $enrollment) {
                // Varied score between 76 and 96
                $variance = (($idx * 3) % 15) - 6;
                $score = min(98.0, max(75.0, $base + $variance));

                StudentAssessmentScore::firstOrCreate(
                    [
                        'assessment_component_id' => $comp->id,
                        'academic_enrollment_id' => $enrollment->id,
                    ],
                    [
                        'score' => $score,
                        'notes' => 'Penilaian reguler semester genap',
                        'graded_by' => $teachers['ahmad']->id,
                        'graded_at' => Carbon::now()->subDays(10 - $idx),
                    ]
                );
            }
        }
    }

    protected function generatePerformanceSummaries(AcademicYear $year): void
    {
        try {
            $service = app(AcademicPerformanceService::class);
            $service->generateForYear($year);
        } catch (\Throwable $e) {
            // Non-blocking log if performance summaries cannot be generated
            report($e);
        }
    }
}
