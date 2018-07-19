<?php

namespace App\Traits;

use GuzzleHttp\Client;
use Psr\Http\Message\ResponseInterface;

trait MakesHttpRequests
{
    /**
     * Make guzzle client instance
     * 
     * @return \GuzzleHttp\Client
     */
    public function createClient()
    {
        return new Client();
    }

    /**
     * Make a get request for a resource
     * 
     * @param string $url
     * @param array $parameters
     * 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function get(string $url, array $parameters)
    {
        return $this->createClient()->request('GET', $url, $parameters);
    }

    /**
     * Make a post request for a resource
     * 
     * @param string $url
     * @param array $parameters
     * 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function post(string $url, array $parameters)
    {
        return $this->createClient()->request('POST', $url, $parameters);
    }

    /**
     * Update a resource
     * 
     * @param string $url
     * @param array $parameters
     * 
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function update(string $url, array $parameters)
    {
        return $this->createClient()->request('PUT', $url, $parameters);
    }
}