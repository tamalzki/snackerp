<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Gate::define('view-reports',    fn(User $u) => $u->isAdmin() || $u->isBranchUser());
        Gate::define('manage-users',    fn(User $u) => $u->isAdmin());
        Gate::define('manage-branches', fn(User $u) => $u->isAdmin());
        Gate::define('manage-bank',     fn(User $u) => $u->isAdmin() || $u->isBranchUser());
    }
}