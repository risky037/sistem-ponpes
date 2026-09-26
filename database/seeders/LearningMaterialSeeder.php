<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Kelas;
use App\Models\LearningMaterial;
use App\Models\LearningMaterialFile;
use App\Models\Mapel;
use App\Models\User;
use Illuminate\Database\Seeder;

class LearningMaterialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Resolve Academic Year
        $academicYear = AcademicYear::where('is_active', true)->first()
            ?? AcademicYear::first();

        if (! $academicYear) {
            $academicYear = AcademicYear::firstOrCreate(
                ['name' => '2026/2027', 'semester' => 'Ganjil'],
                [
                    'start_date' => '2026-07-15',
                    'end_date' => '2026-12-20',
                    'is_active' => true,
                ]
            );
        }

        // 2. Resolve Teacher (Guru)
        $teacher = User::role('Guru')->first()
            ?? User::where('email', 'guru@digitren.id')->first()
            ?? User::first();

        if (! $teacher) {
            $teacher = User::factory()->create([
                'name' => 'Ustadz Ahmad Fauzi, S.Pd.I',
                'email' => 'guru@digitren.id',
            ]);
            $teacher->assignRole('Guru');
        }

        // 3. Resolve Classes
        $classes = Kelas::take(2)->get();
        if ($classes->isEmpty()) {
            $class1 = Kelas::create(['kode' => 'KLS-VII-A', 'tingkatan' => 'VII', 'kelas' => 'VII-A']);
            $class2 = Kelas::create(['kode' => 'KLS-VII-B', 'tingkatan' => 'VII', 'kelas' => 'VII-B']);
            $classes = collect([$class1, $class2]);
        }

        $primaryClass = $classes->first();
        $targetClassIds = $classes->pluck('id')->toArray();

        // 4. Resolve Mapel: Fiqih, Nahwu, Hadits
        $mapelFiqih = Mapel::where('name', 'like', '%Fiqih%')
            ->orWhere('name', 'like', '%Fathul Qorib%')
            ->first()
            ?? Mapel::firstOrCreate(
                ['code' => 'MPL-FIQ-01'],
                [
                    'kelas_id' => $primaryClass->id,
                    'name' => 'Fiqih (Fathul Qorib)',
                    'description' => 'Kajian Hukum Islam dan Fiqih Ibadah',
                    'is_active' => true,
                ]
            );

        $mapelNahwu = Mapel::where('name', 'like', '%Nahwu%')
            ->orWhere('name', 'like', '%Alfiyah%')
            ->first()
            ?? Mapel::firstOrCreate(
                ['code' => 'MPL-NHW-01'],
                [
                    'kelas_id' => $primaryClass->id,
                    'name' => 'Nahwu (Alfiyah Ibnu Malik)',
                    'description' => 'Kajian Kaidah Gramatika Bahasa Arab',
                    'is_active' => true,
                ]
            );

        $mapelHadits = Mapel::where('name', 'like', '%Hadits%')
            ->orWhere('name', 'like', '%Arba%')
            ->first()
            ?? Mapel::firstOrCreate(
                ['code' => 'MPL-HDT-01'],
                [
                    'kelas_id' => $primaryClass->id,
                    'name' => 'Hadits (Arba\'in An-Nawawiyah)',
                    'description' => 'Kajian Hadits Pokok Ajaran Islam',
                    'is_active' => true,
                ]
            );

        // 5. Create Demo Materials

        // Material 1: Fiqih - Bab Thaharah (PDF Attachment + Multi-Class Target)
        $fiqihTitle = 'Bab Thaharah: Wudhu, Mandi Wajib, dan Ketentuan Tayammum';
        $matFiqih = LearningMaterial::updateOrCreate(
            ['slug' => 'bab-thaharah-wudhu-mandi-wajib-dan-tayammum'],
            [
                'teacher_id' => $teacher->id,
                'mapel_id' => $mapelFiqih->id,
                'kelas_id' => $primaryClass->id,
                'academic_year_id' => $academicYear->id,
                'title' => $fiqihTitle,
                'description' => 'Ringkasan rukun, syarat sah, dan hal-hal yang membatalkan wudhu serta mandi wajib merujuk pada Kitab Fathul Qorib.',
                'content_type' => LearningMaterial::CONTENT_TYPE_PDF,
                'content' => "## Muqaddimah Bab Thaharah\n\nThaharah secara bahasa bermakna membersihkan diri. Secara istilah syariat, thaharah adalah perbuatan yang menjadikan sahnya ibadah shalat seperti wudhu, mandi, dan tayammum.\n\n### Pokok Pembahasan:\n1. Tujuh macam air yang sah untuk bersuci.\n2. Rukun wudhu yang enam (Niat, membasuh wajah, membasuh kedua tangan, mengusap sebagian kepala, membasuh kedua kaki, dan tertib).\n3. Rukun mandi janabah.",
                'video_url' => null,
                'external_url' => null,
                'status' => LearningMaterial::STATUS_PUBLISHED,
                'published_at' => now()->subDays(3),
                'created_by' => $teacher->id,
            ]
        );
        $matFiqih->targets()->sync($targetClassIds);

        // File Attachment for Fiqih
        LearningMaterialFile::firstOrCreate(
            [
                'learning_material_id' => $matFiqih->id,
                'file_name' => 'Kitab_Fathul_Qorib_Bab_Thoharoh.pdf',
            ],
            [
                'file_path' => 'materials/demo/Kitab_Fathul_Qorib_Bab_Thoharoh.pdf',
                'file_type' => 'application/pdf',
                'file_extension' => 'pdf',
                'file_size' => 2457600, // 2.34 MB
                'download_count' => 38,
            ]
        );

        // Material 2: Nahwu - Bab Kalam (Text & Nadhom)
        $nahwuTitle = 'Kajian Nadhom Alfiyah: Pengertian Kalam dan Tanda-Tanda Isim';
        $matNahwu = LearningMaterial::updateOrCreate(
            ['slug' => 'kajian-nadhom-alfiyah-bab-kalam-dan-isim'],
            [
                'teacher_id' => $teacher->id,
                'mapel_id' => $mapelNahwu->id,
                'kelas_id' => $primaryClass->id,
                'academic_year_id' => $academicYear->id,
                'title' => $nahwuTitle,
                'description' => 'Uraian bait nadhom pertama Alfiyah Ibnu Malik mengenai definisi kalam mufid dan ciri pembeda kalimat isim.',
                'content_type' => LearningMaterial::CONTENT_TYPE_TEXT,
                'content' => "## Nadhom 1: Definisi Kalam\n\n> كلامنا لفظ مفيد كاستقم\n> واسم وفعل ثم حرف الكلم\n\nKalam menurut ulama Nahwu adalah lafadz yang tersusun dan memberi faedah sempurna seperti lafadz 'Istaqim'. Kalimat terbagi menjadi tiga: Isim, Fi'il, dan Huruf.",
                'video_url' => null,
                'external_url' => 'https://shamela.ws',
                'status' => LearningMaterial::STATUS_PUBLISHED,
                'published_at' => now()->subDays(2),
                'created_by' => $teacher->id,
            ]
        );
        $matNahwu->targets()->sync([$primaryClass->id]);

        // Material 3: Hadits - Niat & Ikhlas (Video Content)
        $haditsTitle = 'Hadits Arba\'in Ke-1: Niat dan Hakikat Keikhlasan Beramal';
        $matHadits = LearningMaterial::updateOrCreate(
            ['slug' => 'hadits-arbain-ke-1-niat-dan-keikhlasan'],
            [
                'teacher_id' => $teacher->id,
                'mapel_id' => $mapelHadits->id,
                'kelas_id' => $primaryClass->id,
                'academic_year_id' => $academicYear->id,
                'title' => $haditsTitle,
                'description' => 'Rekaman video penjelasan Hadits Innamal A\'malu Binniyat dari Sayyidina Umar bin Khattab RA.',
                'content_type' => LearningMaterial::CONTENT_TYPE_VIDEO,
                'content' => "## Makna Hadits Niat\n\nSegala amal perbuatan bergantung pada niatnya, dan setiap orang akan mendapatkan balasan sesuai apa yang ia niatkan.",
                'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'external_url' => null,
                'status' => LearningMaterial::STATUS_PUBLISHED,
                'published_at' => now()->subDay(),
                'created_by' => $teacher->id,
            ]
        );
        $matHadits->targets()->sync($targetClassIds);

        // Material 4: Fiqih - Draft Material (Not Published)
        $draftTitle = 'Bab Sholat Berjamaah dan Ketentuan Makmum Masbuq';
        $matDraft = LearningMaterial::updateOrCreate(
            ['slug' => 'bab-sholat-berjamaah-dan-ketentuan-makmum-masbuq'],
            [
                'teacher_id' => $teacher->id,
                'mapel_id' => $mapelFiqih->id,
                'kelas_id' => $primaryClass->id,
                'academic_year_id' => $academicYear->id,
                'title' => $draftTitle,
                'description' => 'Materi persiapan untuk pertemuan KBM pekan depan mengenai adab shalat berjamaah.',
                'content_type' => LearningMaterial::CONTENT_TYPE_TEXT,
                'content' => 'Materi sedang disiapkan oleh pengampu.',
                'video_url' => null,
                'external_url' => null,
                'status' => LearningMaterial::STATUS_DRAFT,
                'published_at' => null,
                'created_by' => $teacher->id,
            ]
        );
        $matDraft->targets()->sync([$primaryClass->id]);
    }
}
