<div class="btn-group pull-right">
    <button data-bs-toggle="modal" data-bs-target="#editModal-{{ $model->id }}" class="btn btn-sm btn-primary">
        <span class="bx bx-edit"> </span>
    </button>

    <x-edit-modal title="Edit data pengguna" id="{{ $model->id }}" fn="{{ route('users.update', $model->id) }}"
        method="POST">
        @csrf
        @method('PATCH')
        <div class="mb-3">
            <x-input type='text' name='name' id="name" label='Nama Lengkap' placeholder='Nama Lengkap'
                value="{{ $model->name }}" attribute='required'></x-input>
        </div>
        <div class="mb-3">
            <x-input type='email' name='email' id="email" label='Email' placeholder='Email'
                value="{{ $model->email }}" attribute='required'></x-input>
        </div>
        <div class="mb-3">
            <x-select-option id="role_id" name="role_id" label="Jabatan">
                <option value="" selected disabled>Pilih
                    jabatan</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}" {{ $model->roles->first()->id == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}</option>
                @endforeach
            </x-select-option>
        </div>
    </x-edit-modal>

    <button data-bs-toggle="modal" data-bs-target="#resetPasswordModal-{{ $model->id }}" class="btn btn-sm btn-warning text-white" title="Reset Password">
        <span class="bx bx-key"> </span>
    </button>
    <x-modal-form id="resetPasswordModal-{{ $model->id }}" title="Reset Password Pengguna" fn="{{ route('users.reset-password', $model->id) }}" method="POST" icon="bx-key">
        @csrf
        @method('PATCH')
        <div class="alert alert-info border-0 bg-light-info py-2 mb-3">
            <small class="text-dark">Atur ulang password untuk akun <strong>{{ $model->name }}</strong> ({{ $model->email }}).</small>
        </div>
        <div class="mb-3">
            <x-input type="password" name="password" id="reset_password_{{ $model->id }}" label="Password Baru" placeholder="Minimal 8 karakter" attribute="required minlength=8"></x-input>
        </div>
        <div class="mb-3">
            <x-input type="password" name="password_confirmation" id="reset_password_confirmation_{{ $model->id }}" label="Konfirmasi Password Baru" placeholder="Ulangi password baru" attribute="required minlength=8"></x-input>
        </div>
    </x-modal-form>

    <button data-bs-toggle="modal" data-bs-target="#deleteModal-{{ $model->id }}" class="btn btn-sm btn-danger">
        <span class="bx bx-trash"> </span>
    </button>
    <x-delete-modal title='Hapus Pengguna' id="{{ $model->id }}" fn="{{ route('users.destroy', $model->id) }}"
        entity="Pengguna: {{ $model->name }} ({{ $model->email }})" method="POST">
        @csrf
        @method('DELETE')
    </x-delete-modal>
</div>
