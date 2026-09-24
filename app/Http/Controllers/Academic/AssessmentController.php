<?php

namespace App\Http\Controllers\Academic;

use App\Helpers\ToastrHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicEnrollment;
use App\Models\AcademicYear;
use App\Models\AssessmentComponent;
use App\Models\AssessmentDefinition;
use App\Models\Kelas;
use App\Models\StudentAssessmentScore;
use App\Models\TeachingAssignment;
use App\Services\Academic\AssessmentService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AssessmentController extends Controller
{
    public function __construct(
        protected AssessmentService $assessmentService
    ) {}

    /**
     * Display assessment definitions list.
     */
    public function indexDefinition(Request $request)
    {
        if ($request->ajax()) {
            $query = AssessmentDefinition::with('academicYear')
                ->select('assessment_definitions.*')
                ->orderBy('assessment_definitions.academic_year_id', 'desc')
                ->orderBy('assessment_definitions.name', 'asc');

            if ($request->filled('academic_year_id')) {
                $query->where('assessment_definitions.academic_year_id', $request->input('academic_year_id'));
            }

            if ($request->filled('type')) {
                $query->where('type', $request->input('type'));
            }

            if ($request->filled('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('academic_year_name', function ($row) {
                    return e($row->academicYear?->name.' ('.$row->academicYear?->semester.')');
                })
                ->addColumn('weight_formatted', function ($row) {
                    return $row->weight.'%';
                })
                ->addColumn('status_badge', function ($row) {
                    if ($row->is_active) {
                        return '<span class="badge bg-success">Aktif</span>';
                    }

                    return '<span class="badge bg-secondary">Nonaktif</span>';
                })
                ->addColumn('action', function ($row) {
                    $editBtn = '<button type="button" class="btn btn-sm btn-warning me-1 btn-edit" '
                        .'data-id="'.$row->id.'" '
                        .'data-name="'.e($row->name).'" '
                        .'data-type="'.e($row->type).'" '
                        .'data-weight="'.$row->weight.'" '
                        .'data-is-active="'.($row->is_active ? '1' : '0').'" '
                        .'data-bs-toggle="modal" data-bs-target="#editModal" title="Edit">'
                        .'<i class="bx bx-edit"></i> Edit</button>';

                    $deleteForm = '<form action="'.route('assessment.definition.destroy', $row->id).'" method="POST" class="d-inline" onsubmit="return confirm(\'Apakah Anda yakin ingin menghapus atau menonaktifkan definisi penilaian ini?\')">'
                        .csrf_field()
                        .method_field('DELETE')
                        .'<button type="submit" class="btn btn-sm btn-danger" title="Hapus/Nonaktifkan"><i class="bx bx-trash"></i> Hapus</button>'
                        .'</form>';

                    return $editBtn.$deleteForm;
                })
                ->rawColumns(['status_badge', 'action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $allowedTypes = AssessmentDefinition::ALLOWED_TYPES;

        return view('pages.academic.assessment.definition.index', compact('academicYears', 'activeYear', 'allowedTypes'));
    }

    /**
     * Store a new assessment definition.
     */
    public function storeDefinition(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'name' => 'required|string|max:100',
            'type' => 'required|in:'.implode(',', AssessmentDefinition::ALLOWED_TYPES),
            'weight' => 'required|integer|min:0|max:100',
            'is_active' => 'nullable|boolean',
        ]);

        try {
            $academicYear = AcademicYear::findOrFail($validated['academic_year_id']);
            $this->assessmentService->createDefinition($academicYear, $validated);

            ToastrHelper::success('Definisi penilaian berhasil ditambahkan.');

            return redirect()->route('assessment.definition.index');
        } catch (DomainException $e) {
            ToastrHelper::error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('AssessmentController storeDefinition error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menambahkan definisi penilaian.');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Update an assessment definition.
     */
    public function updateDefinition(Request $request, AssessmentDefinition $assessmentDefinition)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'type' => 'required|in:'.implode(',', AssessmentDefinition::ALLOWED_TYPES),
            'weight' => 'required|integer|min:0|max:100',
            'is_active' => 'required|boolean',
        ]);

        try {
            $this->assessmentService->updateDefinition($assessmentDefinition, $validated);

            ToastrHelper::success('Definisi penilaian berhasil diperbarui.');

            return redirect()->route('assessment.definition.index');
        } catch (DomainException $e) {
            ToastrHelper::error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('AssessmentController updateDefinition error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'definition_id' => $assessmentDefinition->id,
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal memperbarui definisi penilaian.');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Destroy or deactivate assessment definition.
     */
    public function destroyDefinition(AssessmentDefinition $assessmentDefinition)
    {
        try {
            $deleted = $this->assessmentService->deactivateDefinition($assessmentDefinition);

            if ($deleted) {
                ToastrHelper::success('Definisi penilaian berhasil dihapus.');
            } else {
                ToastrHelper::info('Definisi penilaian memiliki riwayat komponen/nilai sehingga dinonaktifkan.');
            }

            return redirect()->route('assessment.definition.index');
        } catch (DomainException $e) {
            ToastrHelper::error($e->getMessage());

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('AssessmentController destroyDefinition error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'definition_id' => $assessmentDefinition->id,
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal memproses penghapusan definisi penilaian.');

            return redirect()->back();
        }
    }

    /**
     * Display assessment components list.
     */
    public function indexComponent(Request $request)
    {
        if ($request->ajax()) {
            $query = AssessmentComponent::with([
                'teachingAssignment.kelas',
                'teachingAssignment.mapel',
                'teachingAssignment.user',
                'teachingAssignment.academicYear',
                'assessmentDefinition',
                'studentAssessmentScores',
            ])
                ->select('assessment_components.*')
                ->orderBy('assessment_components.id', 'desc');

            if ($request->filled('academic_year_id')) {
                $query->whereHas('teachingAssignment', function ($q) use ($request) {
                    $q->where('academic_year_id', $request->input('academic_year_id'));
                });
            }

            if ($request->filled('kelas_id')) {
                $query->whereHas('teachingAssignment', function ($q) use ($request) {
                    $q->where('kelas_id', $request->input('kelas_id'));
                });
            }

            if ($request->filled('teaching_assignment_id')) {
                $query->where('teaching_assignment_id', $request->input('teaching_assignment_id'));
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('kelas_name', function ($row) {
                    $kelas = $row->teachingAssignment?->kelas;

                    return e($kelas ? $kelas->tingkatan.' - '.$kelas->kelas : '-');
                })
                ->addColumn('mapel_name', function ($row) {
                    return e($row->teachingAssignment?->mapel?->name ?? '-');
                })
                ->addColumn('teacher_name', function ($row) {
                    return e($row->teachingAssignment?->user?->name ?? '-');
                })
                ->addColumn('definition_name', function ($row) {
                    $def = $row->assessmentDefinition;

                    return e($def ? $def->name.' ('.$def->type.')' : '-');
                })
                ->addColumn('weight_formatted', function ($row) {
                    return $row->weight.'%';
                })
                ->addColumn('scores_count_badge', function ($row) {
                    $count = $row->studentAssessmentScores->count();
                    if ($count === 0) {
                        return '<span class="badge bg-secondary">0 Dinilai</span>';
                    }

                    return '<span class="badge bg-success">'.$count.' Santri Dinilai</span>';
                })
                ->addColumn('action', function ($row) {
                    $manageUrl = route('assessment.score.manage', $row->id);
                    $btnManage = '<a href="'.$manageUrl.'" class="btn btn-sm btn-primary me-1" title="Input & Kelola Nilai">'
                        .'<i class="bx bx-edit-alt"></i> Kelola Nilai</a>';

                    $btnDelete = '';
                    if ($row->studentAssessmentScores->isEmpty()) {
                        $btnDelete = '<form action="'.route('assessment.component.destroy', $row->id).'" method="POST" class="d-inline" onsubmit="return confirm(\'Apakah Anda yakin ingin menghapus komponen penilaian ini?\')">'
                            .csrf_field()
                            .method_field('DELETE')
                            .'<button type="submit" class="btn btn-sm btn-danger" title="Hapus Komponen"><i class="bx bx-trash"></i></button>'
                            .'</form>';
                    }

                    return $btnManage.$btnDelete;
                })
                ->rawColumns(['scores_count_badge', 'action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();
        $teachingAssignments = TeachingAssignment::with(['kelas', 'mapel', 'user', 'academicYear'])
            ->where('status', TeachingAssignment::STATUS_AKTIF)
            ->get();
        $definitions = AssessmentDefinition::with('academicYear')->where('is_active', true)->get();

        return view('pages.academic.assessment.component.index', compact(
            'academicYears',
            'activeYear',
            'kelasList',
            'teachingAssignments',
            'definitions'
        ));
    }

    /**
     * Store a new assessment component.
     */
    public function storeComponent(Request $request)
    {
        $validated = $request->validate([
            'teaching_assignment_id' => 'required|exists:teaching_assignments,id',
            'assessment_definition_id' => 'required|exists:assessment_definitions,id',
            'weight' => 'nullable|integer|min:0|max:100',
        ]);

        try {
            $assignment = TeachingAssignment::findOrFail($validated['teaching_assignment_id']);
            $definition = AssessmentDefinition::findOrFail($validated['assessment_definition_id']);
            $weight = $request->filled('weight') ? (int) $validated['weight'] : null;

            $this->assessmentService->createComponent($assignment, $definition, $weight);

            ToastrHelper::success('Komponen penilaian berhasil ditambahkan.');

            return redirect()->route('assessment.component.index');
        } catch (DomainException $e) {
            ToastrHelper::error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('AssessmentController storeComponent error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menambahkan komponen penilaian.');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Destroy an assessment component.
     */
    public function destroyComponent(AssessmentComponent $assessmentComponent)
    {
        try {
            $this->assessmentService->deleteComponent($assessmentComponent);

            ToastrHelper::success('Komponen penilaian berhasil dihapus.');

            return redirect()->route('assessment.component.index');
        } catch (DomainException $e) {
            ToastrHelper::error($e->getMessage());

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('AssessmentController destroyComponent error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'component_id' => $assessmentComponent->id,
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menghapus komponen penilaian.');

            return redirect()->back();
        }
    }

    /**
     * Score index: Delegates to indexComponent.
     */
    public function indexScore(Request $request)
    {
        return $this->indexComponent($request);
    }

    /**
     * Manage student scores for a specific assessment component.
     */
    public function manageScore(AssessmentComponent $assessmentComponent)
    {
        $assessmentComponent->load([
            'teachingAssignment.kelas',
            'teachingAssignment.mapel',
            'teachingAssignment.user',
            'teachingAssignment.academicYear',
            'assessmentDefinition',
            'studentAssessmentScores.grader',
        ]);

        $assignment = $assessmentComponent->teachingAssignment;

        $enrollments = AcademicEnrollment::with('santri.user')
            ->where('kelas_id', $assignment->kelas_id)
            ->where('academic_year_id', $assignment->academic_year_id)
            ->where('status', AcademicEnrollment::STATUS_AKTIF)
            ->get();

        $existingScores = $assessmentComponent->studentAssessmentScores->keyBy('academic_enrollment_id');

        return view('pages.academic.assessment.score.manage', compact(
            'assessmentComponent',
            'enrollments',
            'existingScores'
        ));
    }

    /**
     * Store bulk scores for an assessment component.
     */
    public function storeScore(Request $request, AssessmentComponent $assessmentComponent)
    {
        $validated = $request->validate([
            'scores' => 'required|array',
            'scores.*.academic_enrollment_id' => 'required|exists:academic_enrollments,id',
            'scores.*.score' => 'nullable|numeric|min:0|max:100',
            'scores.*.notes' => 'nullable|string|max:255',
        ]);

        try {
            $this->assessmentService->bulkRecordScores(
                component: $assessmentComponent,
                scores: $validated['scores'],
                gradedBy: auth()->user()
            );

            ToastrHelper::success('Nilai santri berhasil disimpan.');

            return redirect()->route('assessment.score.manage', $assessmentComponent->id);
        } catch (DomainException $e) {
            ToastrHelper::error($e->getMessage());

            return redirect()->back()->withInput();
        } catch (\Throwable $th) {
            Log::error('AssessmentController storeScore error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'component_id' => $assessmentComponent->id,
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menyimpan nilai santri.');

            return redirect()->back()->withInput();
        }
    }

    /**
     * Update a single student assessment score.
     */
    public function updateScore(Request $request, StudentAssessmentScore $studentAssessmentScore)
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string|max:255',
        ]);

        try {
            $this->assessmentService->updateScore(
                scoreRecord: $studentAssessmentScore,
                score: (float) $validated['score'],
                notes: $validated['notes'] ?? null,
                gradedBy: auth()->user()
            );

            ToastrHelper::success('Nilai santri berhasil diperbarui.');

            return redirect()->back();
        } catch (DomainException $e) {
            ToastrHelper::error($e->getMessage());

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('AssessmentController updateScore error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'score_id' => $studentAssessmentScore->id,
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal memperbarui nilai santri.');

            return redirect()->back();
        }
    }
}
