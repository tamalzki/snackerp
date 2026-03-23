@extends('layouts.app')
@section('title', $user->name)
@section('content')

<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="fw-bold mb-0">{{ $user->name }}</h5>
    <div class="d-flex gap-2">
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary btn-sm btn-action">
            <i class="bi bi-pencil"></i> Edit
        </a>
        <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
            <i class="bi bi-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Email</div>
                <div class="fw-bold">{{ $user->email }}</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Role</div>
                <div>
                    <span class="badge {{ $user->role === 'admin' ? 'bg-dark' : 'bg-secondary' }} fs-6">
                        {{ ucfirst($user->role) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-muted small">Branch</div>
                <div class="fw-bold">{{ $user->branch->name ?? '—' }}</div>
            </div>
        </div>
    </div>
</div>

@endsection
