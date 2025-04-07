<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\Product;
use App\Policies\ProductPolicy;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Product::class => ProductPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Define product management permissions
        Gate::define('add_products', function ($user) {
            return $user->hasAnyRole(['Admin', 'Employee']);
        });

        Gate::define('edit_products', function ($user) {
            return $user->hasAnyRole(['Admin', 'Employee']);
        });

        Gate::define('delete_products', function ($user) {
            return $user->hasAnyRole(['Admin', 'Employee']);
        });
    }
} 