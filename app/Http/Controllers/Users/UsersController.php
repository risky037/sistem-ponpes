<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;
use Toastr;
use Yajra\DataTables\Facades\DataTables;

class UsersController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $users = User::with('roles')->whereNotIn('name', ['Administrator'])->get();

            return DataTables::of($users)
                ->addIndexColumn()
                ->addColumn('action', 'pages.users.include.action')
                ->toJson();
        }

        return view('pages.users.index');
    }

    public function store(Request $request)
    {
        $validate = $request->validate([
            'name' => 'required|min:5|max:255',
            'email' => 'required|email|unique:users,email',
            'role_id' => ['required', Rule::exists('roles', 'id')->where('guard_name', 'web')],
            'password' => 'required|confirmed|string|min:8|regex:/[0-9]+/',
        ]);

        try {
            $roleId = $validate['role_id'];
            unset($validate['role_id']);

            DB::transaction(function () use ($validate, $roleId) {
                $user = User::create($validate);
                $user->syncRoles([(int) $roleId]);
            });

            Toastr::success('Berhasil menambah data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('UsersController store error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menambah data');

            return redirect()->back();
        }
    }

    public function update(Request $request, User $user)
    {
        $validate = $request->validate([
            'name' => 'required|min:5|max:255',
            'email' => "required|email|unique:users,email,$user->id",
            'role_id' => ['required', Rule::exists('roles', 'id')->where('guard_name', 'web')],
        ]);

        try {
            $roleId = (int) $validate['role_id'];
            unset($validate['role_id']);

            if (auth()->id() === $user->id && $user->hasRole('Administrator')) {
                $adminRole = Role::where('name', 'Administrator')->where('guard_name', 'web')->first();
                if ($adminRole && $roleId !== (int) $adminRole->id) {
                    Toastr::error('Tidak dapat menurunkan role Administrator pada akun Anda sendiri');

                    return redirect()->back();
                }
            }

            DB::transaction(function () use ($user, $validate, $roleId) {
                $user->update($validate);
                $user->syncRoles([(int) $roleId]);
            });

            Toastr::success('Berhasil memperbarui data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('UsersController update error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal memperbarui data');

            return redirect()->back();
        }
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            Toastr::error('Tidak dapat menghapus akun Anda sendiri');

            return redirect()->back();
        }

        if ($user->hasRole('Administrator') && User::role('Administrator')->count() <= 1) {
            Toastr::error('Tidak dapat menghapus satu-satunya akun Administrator');

            return redirect()->back();
        }

        try {
            $user->delete();
            Toastr::success('Berhasil menghapus data');

            return redirect()->back();
        } catch (\Throwable $th) {
            Log::error('UsersController destroy error: '.$th->getMessage(), [
                'user_id' => auth()->id(),
                'request_uri' => request()->fullUrl(),
                'method' => request()->method(),
                'ip' => request()->ip(),
                'exception' => $th,
            ]);
            Toastr::error('Gagal menghapus data');

            return redirect()->back();
        }
    }
}
