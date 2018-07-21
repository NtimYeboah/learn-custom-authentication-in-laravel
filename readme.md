## Custom authentication using Zendesk as user provider

Adding authentication to Laravel application is a matter running ```php artisan make:auth``` and the authentication routes and views will be added to you application. The authentication system will fetch the user to be authenticated from the database when using the default authentication scaffold.

In cases where users may not be in the database configured to be used with your application but are fetched from an external API, we can still use the default authentication system to authenticate these users in our application.

To do this, lets try to understand the authentication system of Laravel. 

Laravel's authentication facilities are made up of "guards" and "providers".

Guards define how users are authenticated for each request. For example, Laravel ships with a [session](https://github.com/laravel/framework/blob/5.6/src/Illuminate/Auth/SessionGuard.php) guard which maintains state using session storage and cookies. There is also a [token](https://github.com/laravel/framework/blob/5.6/src/Illuminate/Auth/TokenGuard.php) guard for token based authentication.

Providers define how users are retrieved from your persistent storage. Laravel ships with support for retrieving users using Eloquent and the database query builder.

In our case, we will be using Zendesk as the provider.

### Using Zendesk as provider

Laravel provides the `Illuminate\Contracts\Auth\UserProvider` and `Illuminate\Contracts\Auth\Authenticatable` contracts. 
The implementation of `Illuminate\Contracts\Auth\Authenticatable` contract will have methods to fetch the user from Zendesk when email and password are provided.

The implementation of `Illuminate\Contracts\Auth\UserProvider` will fetch the user from Zendesk. Therefore the implementation of `Illuminate\Contracts\Auth\Authenticatable` will be injected in the implementation of `Illuminate\Contracts\Auth\UserProvider`.

In the default authentication, the [App\User.php](https://github.com/laravel/laravel/blob/master/app/User.php) class is an implementation of `Illuminate\Contracts\Auth\Authenticatable` contract.

In our case we will modify the `App\User.php` file to suit our needs.

#### Implementing the Authenticatable contract

We will modify the `App\User.php` file and add methods to retrieve the user from Zendesk. The two most important methods are `retrieve` and `getById`. `retrieve` fetches the user from Zendesk and `getById` retrieves the user from session using an identifier. There are other methods defined since we are implementing the `Illuminate\Contracts\Auth\Authenticatable` contract.

[https://github.com/NtimYeboah/laravel-zendesk-authentication/app/User.php](https://github.com/NtimYeboah/laravel-zendesk-authentication/blob/master/app/User.php)

```php
.
.
.

use App\Traits\MakesHttpRequests;
use Illuminate\Contracts\Auth\Authenticatable;

class User extends Authenticatable
{
    use MakesHttpRequests;

    .
    .
    .

    /**
     * Get user from Zendesk
     * 
     * @param array $credentials
     * 
     * @return \App\User
     */
    public function retrieve($credentials)
    {
        $url = 'https://' . config('services.zendesk.subdomain') . '.zendesk.com/api/v2/users/me.json';

        $response = $this->get($url, [
            'auth' => [
                $credentials['email'],
                $credentials['password']
            ] 
        ]);
        
        $user = json_decode((string) $response->getBody())->user;
        session()->put('email', (string) $response->getBody());
        
        $this->username = $user->name;
        $this->password = $credentials['password'];

        return $this;
    }

    /**
     * Retrieve user by identifier
     * 
     * @param string $identifier
     * 
     * @return mixed
     */
    public function getById($identifier)
    {
        return json_decode(session()->get($identifier))->user;
    }

    .
    .
    .
}
```

#### Implementing the UserProvider contract

The implementation of `Illuminate\Contracts\Auth\UserProvider` is responsible for fetching the implementation of `Illuminate\Contracts\Auth\Authenticatable` from Zendesk. The noticable methods are

1. `retrieveById` which delegates the retrieval of the user from the session to the implementation of `Illuminate\Contracts\Auth\Authenticatable` thus the [User.php](https://github.com/NtimYeboah/laravel-zendesk-authentication/blob/master/app/User.php) class.

2. `retrieveByCredentials` which retrieves the user from Zendesk when the email and password are provided. The retrieval of the user is delegated to the [User.php](https://github.com/NtimYeboah/laravel-zendesk-authentication/blob/master/app/User.php) class.

3. `validateCredentials` which validates a user against the provided credentials. In this method, we check the password of the user against the password in the provided credentials.

There are other methods that should be implemented since we are implementing an interface.

[https://github.com/NtimYeboah/laravel-zendesk-authentication/app/Extensions/ZendeskUserProvider.php](https://github.com/NtimYeboah/laravel-zendesk-authentication/blob/master/app/Extensions/ZendeskUserProvider.php)

```php

use Illuminate\Contracts\Auth\UserProvider;

class ZendeskUserProvider implements UserProvider
{

    /**
     * The user to be authenticated
     * 
     * @var $user
     */
    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier)
    {
        return $this->user->getById($identifier);
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (! $credentials) {
            return;
        }

        return $this->user->retrieve($credentials);
    }

     /**
     * Validate a user against the given credentials.
     *
     * @param  \Illuminate\Contracts\Auth\Authenticatable  $user
     * @param  array  $credentials
     * @return bool
     */
    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return $user->password === $credentials['password'];
    }

    .
    .
    .

```

#### Registering the authentication provider

We need to register the authentication provider so that Laravel will be aware and use it for authentication. In the `boot` method of the `AuthServiceProvider` we will register our authentication provider and guard. We will be using the session guard already included with Laravel. We will register both the provider and guard name as `zendesk`.

[https://github.com/NtimYeboah/laravel-zendesk-authentication/app/Providers/AuthServiceProvider.phpp](https://github.com/NtimYeboah/laravel-zendesk-authentication/blob/master/app/Providers/AuthServiceProvider.php)

```php

use App\User;
use Illuminate\Auth\SessionGuard;
use App\Extensions\ZendeskUserProvider;

.
.
.

class AuthServiceProvider extends ServiceProvider
{

    .
    .
    .

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

```

Additionally, after registering the provider and guard, lets switch to use them in the authentication configuration file. Modify the `config/auth.php` to add the provider and guard. 
Under the providers section, add the following

```php

'providers' => [
    'users' => [
        'driver' => 'zendesk'
    ]

```

Under the guard section, append the following provider to the list of guards

```php

'custom' => [
    'driver' => 'zendesk',
    'provider' => 'users',
],

```

And then finally, set the default guard to be used for authentication under the `defaults` key.
```php

'defaults' => [
    'guard' => 'custom',
    'passwords' => 'users',
],

```
