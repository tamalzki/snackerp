<form method="GET" action="{{ $action }}" class="d-flex gap-2 mb-4">
    <div class="input-group" style="max-width: 400px;">
        <span class="input-group-text bg-white">
            <i class="bi bi-search text-muted"></i>
        </span>
        <input type="text"
               name="search"
               class="form-control border-start-0"
               placeholder="{{ $placeholder ?? 'Search...' }}"
               value="{{ request('search') }}">
        @if(request('search'))
            <a href="{{ $action }}" class="btn btn-outline-secondary">
                <i class="bi bi-x"></i>
            </a>
        @endif
    </div>
    <button type="submit" class="btn btn-primary">Search</button>
</form>