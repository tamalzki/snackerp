@extends('layouts.app')
@section('title', 'Edit User')
@section('content')

<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pencil"></i> Edit User: {{ $user->name }}
            </div>
            <div class="card-body">
                <form action="{{ route('users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Name</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email</label>
                        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                               value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">New password</label>
                            <input type="password" name="password" class="form-control @error('password') is-invalid @enderror"
                                   placeholder="Leave blank to keep current" autocomplete="new-password">
                            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-semibold">Confirm password</label>
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Role</label>
                        <select name="role" id="user_role" class="form-select @error('role') is-invalid @enderror" required>
                            <option value="admin" @selected(old('role', $user->role) === 'admin')>Admin</option>
                            <option value="manager" @selected(old('role', $user->role) === 'manager')>Manager</option>
                            <option value="branch" @selected(old('role', $user->role) === 'branch')>Branch</option>
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-4" id="branch_wrap">
                        <label class="form-label fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select @error('branch_id') is-invalid @enderror">
                            <option value="">— Select branch —</option>
                            @foreach($branches as $b)
                                <option value="{{ $b->id }}" @selected(old('branch_id', $user->branch_id) == $b->id)>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('branch_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Update</button>
                        <a href="{{ route('users.show', $user) }}" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const role = document.getElementById('user_role');
    const wrap = document.getElementById('branch_wrap');
    function toggle() {
        wrap.style.display = role.value === 'branch' ? 'block' : 'none';
    }
    // Manager role does not require a branch assignment.
    role.addEventListener('change', toggle);
    toggle();
})();
</script>
@endpush
@endsection
