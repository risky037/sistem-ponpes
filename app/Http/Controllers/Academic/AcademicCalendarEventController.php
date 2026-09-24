<?php

namespace App\Http\Controllers\Academic;

use App\Helpers\ToastrHelper;
use App\Http\Controllers\Controller;
use App\Models\AcademicCalendarEvent;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class AcademicCalendarEventController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = AcademicCalendarEvent::with('academicYear')
                ->select('academic_calendar_events.*')
                ->orderBy('academic_calendar_events.start_date', 'asc');

            if ($request->filled('academic_year_id')) {
                $query->where('academic_calendar_events.academic_year_id', $request->input('academic_year_id'));
            }

            if ($request->filled('event_type')) {
                $query->where('academic_calendar_events.event_type', $request->input('event_type'));
            }

            $eventTypes = AcademicCalendarEvent::EVENT_TYPES;

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('date_range', function ($row) {
                    $start = $row->start_date?->format('d/m/Y') ?? '-';
                    $end = $row->end_date?->format('d/m/Y') ?? '-';

                    return $start === $end ? $start : $start.' - '.$end;
                })
                ->addColumn('type_badge', function ($row) {
                    $badgeClass = match ($row->event_type) {
                        'Awal Semester' => 'bg-primary',
                        'Libur' => 'bg-danger',
                        'Ujian' => 'bg-warning text-dark',
                        'Kegiatan' => 'bg-info text-dark',
                        default => 'bg-secondary',
                    };

                    return '<span class="badge '.$badgeClass.'">'.e($row->event_type).'</span>';
                })
                ->addColumn('academic_year_name', function ($row) {
                    return e($row->academicYear ? $row->academicYear->name.' ('.$row->academicYear->semester.')' : '-');
                })
                ->editColumn('description', function ($row) {
                    return e($row->description ?? '-');
                })
                ->addColumn('action', function ($row) use ($eventTypes) {
                    return view('pages.academic.calendar_event.include.action', [
                        'model' => $row,
                        'eventTypes' => $eventTypes,
                    ]);
                })
                ->filterColumn('academic_year_name', function ($query, $keyword) {
                    $query->whereHas('academicYear', function ($q) use ($keyword) {
                        $q->where('academic_years.name', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('academic_year_name', function ($query, $direction) {
                    $query->join('academic_years', 'academic_years.id', '=', 'academic_calendar_events.academic_year_id')
                        ->orderBy('academic_years.name', $direction)
                        ->select('academic_calendar_events.*');
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('academic_calendar_events.title', 'like', "%{$search}%")
                                ->orWhere('academic_calendar_events.event_type', 'like', "%{$search}%")
                                ->orWhere('academic_calendar_events.description', 'like', "%{$search}%")
                                ->orWhereHas('academicYear', function ($sq) use ($search) {
                                    $sq->where('academic_years.name', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->rawColumns(['type_badge', 'action'])
                ->toJson();
        }

        $academicYears = AcademicYear::orderBy('start_date', 'desc')->get();
        $activeYear = AcademicYear::where('is_active', true)->first();
        $eventTypes = AcademicCalendarEvent::EVENT_TYPES;

        return view('pages.academic.calendar_event.index', [
            'academicYears' => $academicYears,
            'activeYear' => $activeYear,
            'eventTypes' => $eventTypes,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'title' => 'required|string|max:150',
            'event_type' => 'required|in:'.implode(',', AcademicCalendarEvent::EVENT_TYPES),
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:500',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
        ]);

        try {
            AcademicCalendarEvent::create($validated);
            ToastrHelper::success('Berhasil menambahkan agenda kalender akademik');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('AcademicCalendarEventController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menambahkan agenda kalender akademik');

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, AcademicCalendarEvent $academicCalendarEvent)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:150',
            'event_type' => 'required|in:'.implode(',', AcademicCalendarEvent::EVENT_TYPES),
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'description' => 'nullable|string|max:500',
        ], [
            'end_date.after_or_equal' => 'Tanggal akhir harus sama dengan atau setelah tanggal mulai.',
        ]);

        try {
            $academicCalendarEvent->update($validated);
            ToastrHelper::success('Berhasil memperbarui agenda kalender akademik');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('AcademicCalendarEventController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal memperbarui agenda kalender akademik');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(AcademicCalendarEvent $academicCalendarEvent)
    {
        try {
            $academicCalendarEvent->delete();
            ToastrHelper::success('Berhasil menghapus agenda kalender akademik');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('AcademicCalendarEventController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menghapus agenda kalender akademik');

            return redirect()->back();
        }
    }
}
