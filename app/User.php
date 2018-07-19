<?php

namespace App;

use App\Traits\MakesHttpRequests;
use Illuminate\Contracts\Auth\Authenticatable;

class User implements Authenticatable
{
    use MakesHttpRequests;

    /**
     * Authenticated username
     * 
     * @var string $username
     */
    public $username;

    /**
     * User password
     * 
     * @var string $password
     */
    public $password;

    /**
     * Get user from Zendesk
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

    /**
     * Get the name of the unique identifier for the user.
     *
     * @return string
     */
    public function getAuthIdentifierName()
    {
        return 'email';
    }

    /**
     * Get the unique identifier for the user.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getAuthIdentifierName();
    }

    /**
     * Get the password for the user.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password;
    }

    /**
     * Get the token value for the "remember me" session.
     *
     * @return string
     */
    public function getRememberToken(){}
    
    /**
     * Set the token value for the "remember me" session.
     *
     * @param  string  $value
     * @return void
     */
    public function setRememberToken($value){}
    
    /**
     * Get the column name for the "remember me" token.
     *
     * @return string
     */
    public function getRememberTokenName(){}
}