<?php

namespace App\Http\Controllers\Role;

use App\Helpers\ToastrHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::all();

        return view('pages.role.index', compact('roles'));
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'name' => 'required|min:5|max:255|unique:roles,name',
        ]);
        try {
            $role = Role::create($validate);
            if (isset($request->index)) {
                $role->givePermissionTo($request->index);
            }
            if (isset($request->view)) {
                $role->givePermissionTo($request->view);
            }
            if (isset($request->store)) {
                $role->givePermissionTo($request->store);
            }
            if (isset($request->update)) {
                $role->givePermissionTo($request->update);
            }
            if (isset($request->destroy)) {
                $role->givePermissionTo($request->destroy);
            }
            ToastrHelper::success('Berhasil menambah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('RoleController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menambah data');

            return redirect()->back();
        }
    }

    public function update(Request $request, Role $role)
    {
        $validate = $request->validate([
            'name' => "required|min:5|max:255|unique:roles,name,$role->id",
        ]);
        try {
            $role->update($validate);
            ToastrHelper::success('Berhasil memperbarui data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('RoleController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal memperbarui data');

            return redirect()->back();
        }
    }

    public function destroy(Role $role)
    {
        $protectedRoles = config('permission.protected_roles', ['Administrator', 'Keuangan', 'Santri']);

        if (in_array($role->name, $protectedRoles, true)) {
            ToastrHelper::error('Role sistem default tidak dapat dihapus');

            return redirect()->back();
        }

        try {
            $role->delete();
            ToastrHelper::success('Berhasil menghapus data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('RoleController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            ToastrHelper::error('Gagal menghapus data');

            return redirect()->back();
        }
    }
}
