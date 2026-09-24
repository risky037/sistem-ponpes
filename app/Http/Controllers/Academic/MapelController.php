<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Models\Kelas;
use App\Models\Mapel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class MapelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Mapel::with('kelas')
                ->select('mapels.*')
                ->orderBy('mapels.name', 'asc');

            if ($request->filled('kelas_id')) {
                $query->where('mapels.kelas_id', $request->input('kelas_id'));
            }

            if ($request->filled('is_active')) {
                $query->where('mapels.is_active', $request->boolean('is_active'));
            }

            $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('code_badge', function ($row) {
                    return '<span class="badge bg-light text-dark border">'.e($row->code).'</span>';
                })
                ->addColumn('kelas_name', function ($row) {
                    return e($row->kelas ? $row->kelas->tingkatan.' - '.$row->kelas->kelas : '-');
                })
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="badge bg-success">Aktif</span>'
                        : '<span class="badge bg-secondary">Nonaktif</span>';
                })
                ->addColumn('action', function ($row) use ($kelasList) {
                    return view('pages.academic.mapel.include.action', [
                        'model' => $row,
                        'kelasList' => $kelasList,
                    ]);
                })
                ->filterColumn('kelas_name', function ($query, $keyword) {
                    $query->whereHas('kelas', function ($q) use ($keyword) {
                        $q->where('kelas.kelas', 'like', "%{$keyword}%")
                            ->orWhere('kelas.tingkatan', 'like', "%{$keyword}%");
                    });
                })
                ->orderColumn('kelas_name', function ($query, $direction) {
                    $query->join('kelas', 'kelas.id', '=', 'mapels.kelas_id')
                        ->orderBy('kelas.tingkatan', $direction)
                        ->orderBy('kelas.kelas', $direction)
                        ->select('mapels.*');
                })
                ->filter(function ($query) use ($request) {
                    if ($request->has('search') && ! empty($request->search['value'])) {
                        $search = $request->search['value'];
                        $query->where(function ($q) use ($search) {
                            $q->where('mapels.name', 'like', "%{$search}%")
                                ->orWhere('mapels.code', 'like', "%{$search}%")
                                ->orWhere('mapels.description', 'like', "%{$search}%")
                                ->orWhereHas('kelas', function ($sq) use ($search) {
                                    $sq->where('kelas.kelas', 'like', "%{$search}%")
                                        ->orWhere('kelas.tingkatan', 'like', "%{$search}%");
                                });
                        });
                    }
                })
                ->rawColumns(['code_badge', 'is_active', 'action'])
                ->toJson();
        }

        $kelasList = Kelas::orderBy('tingkatan')->orderBy('kelas')->get();

        return view('pages.academic.mapel.index', compact('kelasList'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'code' => 'required|string|max:50|unique:mapels,code',
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mapels')->where(fn ($query) => $query->where('kelas_id', $request->input('kelas_id'))),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ], [
            'code.unique' => 'Kode mata pelajaran sudah digunakan.',
            'name.unique' => 'Mata pelajaran dengan nama tersebut sudah ada di kelas yang dipilih.',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active', true);
            Mapel::create($validated);
            Toastr::success('Berhasil menambahkan mata pelajaran');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('MapelController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menambahkan mata pelajaran');

            return redirect()->back()->withInput();
        }
    }

    public function update(Request $request, Mapel $mapel)
    {
        $validated = $request->validate([
            'kelas_id' => 'required|exists:kelas,id',
            'code' => 'required|string|max:50|unique:mapels,code,'.$mapel->id,
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('mapels')
                    ->where(fn ($query) => $query->where('kelas_id', $request->input('kelas_id')))
                    ->ignore($mapel->id),
            ],
            'description' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ], [
            'code.unique' => 'Kode mata pelajaran sudah digunakan.',
            'name.unique' => 'Mata pelajaran dengan nama tersebut sudah ada di kelas yang dipilih.',
        ]);

        try {
            $validated['is_active'] = $request->boolean('is_active');
            $mapel->update($validated);
            Toastr::success('Berhasil memperbarui mata pelajaran');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('MapelController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal memperbarui mata pelajaran');

            return redirect()->back()->withInput();
        }
    }

    public function destroy(Mapel $mapel)
    {
        if ($mapel->teaching_assignments()->exists()) {
            Toastr::error('Tidak dapat menghapus mata pelajaran yang memiliki riwayat penugasan pengajar.');

            return redirect()->back();
        }

        try {
            $mapel->delete();
            Toastr::success('Berhasil menghapus mata pelajaran');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('MapelController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menghapus mata pelajaran');

            return redirect()->back();
        }
    }
}
