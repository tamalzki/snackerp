@extends('layouts.app')
@section('title', 'Branches')
@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0">Branches</h5>
    <a href="{{ route('branches.create') }}" class="btn btn-primary btn-sm btn-action">
        <i class="bi bi-plus-lg"></i> Add Branch
    </a>
</div>

<form method="GET" action="{{ route('branches.index') }}" class="d-flex gap-2 mb-4">
    <div class="input-group" style="max-width: 400px;">
        <span class="input-group-text bg-white">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text" name="search" class="form-control border-start-0"
               placeholder="Search by name or address..."
               value="{{ request('search') }}">
        @if(request('search'))
            <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i>
            </a>
        @endif
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
</form>

<div class="card">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Branch Name</th>
                    <th>Address</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($branches as $b)
                <tr>
                    <td class="text-muted small">{{ $b->id }}</td>
                    <td class="fw-semibold">{{ $b->name }}</td>
                    <td class="text-muted">{{ $b->address ?? '—' }}</td>
                    <td>
                        @if($b->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-secondary">Inactive</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('branches.show', $b) }}"
                           class="btn btn-sm btn-outline-primary btn-action">
                            <i class="bi bi-eye"></i> View Inventory
                        </a>
                        <a href="{{ route('transfers.create', ['branch_id' => $b->id]) }}"
                           class="btn btn-sm btn-outline-success btn-action">
                            <i class="bi bi-arrow-left-right"></i> Transfer Stock
                        </a>
                        <a href="{{ route('branches.edit', $b) }}"
                           class="btn btn-sm btn-outline-secondary btn-action">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                        @if($b->name !== 'Main Warehouse')
                        <form action="{{ route('branches.destroy', $b) }}"
                              method="POST" class="d-inline"
                              onsubmit="return confirm('Delete this branch?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger btn-action">
                                <i class="bi bi-trash"></i> Delete
                            </button>
                        </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="text-center text-muted py-4">
                        @if($search)
                            No results for "<strong>{{ $search }}</strong>".
                            <a href="{{ route('branches.index') }}">Clear search</a>
                        @else
                            No branches yet.
                            <a href="{{ route('branches.create') }}">Add one now</a>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-between align-items-center">
    <div class="text-muted small">
        Showing {{ $branches->firstItem() ?? 0 }}–{{ $branches->lastItem() ?? 0 }}
        of {{ $branches->total() }} results
    </div>
    {{ $branches->links() }}
</div>

@endsection