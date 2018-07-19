<?php

namespace App\Providers;

use App\User;
use Illuminate\Auth\SessionGuard;
use App\Services\Auth\ZendeskGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Extensions\ZendeskUserProvider;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::provider('zendesk', function ($app, array $config) {
            return new ZendeskUserProvider($app->make(User::class));
        });

        Auth::extend('zendesk', function ($app, $name, array $config) {
            return new SessionGuard('session', 
                                    Auth::createUserProvider($config['provider']),
                                    $app->make('session.store'),
                                    $app->make('request'));
        });
    }
}
