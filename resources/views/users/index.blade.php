@extends('layouts.app')
@section('title', 'Users')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Users</h5>
    <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-person-plus"></i> Add User
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Branch</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $u)
                <tr>
                    <td class="text-muted small">{{ $u->id }}</td>
                    <td class="fw-semibold">{{ $u->name }}</td>
                    <td class="text-muted small">{{ $u->email }}</td>
                    <td>
                        <span class="badge {{ $u->role === 'admin' ? 'bg-dark' : ($u->role === 'manager' ? 'bg-primary' : 'bg-secondary') }}">
                            {{ ucfirst($u->role) }}
                        </span>
                    </td>
                    <td class="text-muted small">{{ $u->branch->name ?? '—' }}</td>
                    <td class="text-end">
                        <a href="{{ route('users.show', $u) }}" class="btn btn-sm btn-outline-primary btn-action">
                            <i class="bi bi-eye"></i>
                        </a>
                        <a href="{{ route('users.edit', $u) }}" class="btn btn-sm btn-outline-secondary btn-action">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($u->id !== auth()->id())
                            <form action="{{ route('users.destroy', $u) }}" method="POST" class="d-inline"
                                  onsubmit="return confirm('Delete this user?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger btn-action">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">
                        No users found.
                        <a href="{{ route('users.create') }}">Add one</a>
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        Showing {{ $users->firstItem() ?? 0 }}–{{ $users->lastItem() ?? 0 }}
        of {{ $users->total() }} results
    </div>
    {{ $users->links() }}
</div>

@endsection
