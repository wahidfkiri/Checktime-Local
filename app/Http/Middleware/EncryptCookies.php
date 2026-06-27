<?php

namespace App\Http\Middleware;

use Illuminate\Cookie\Middleware\EncryptCookies as Middleware;
use Illuminate\Contracts\Encryption\DecryptException;

class EncryptCookies extends Middleware
{
    /**
     * The names of the cookies that should not be encrypted.
     *
     * @var array<int, string>
     */
    protected $except = [
        //
    ];

    /**
     * Decrypt the given cookie and return the value.
     * Overrides parent to gracefully handle stale cookies from a previous APP_KEY.
     *
     * @param  string  $name
     * @param  string|array  $cookie
     * @return string|array|null
     */
    protected function decryptCookie($name, $cookie)
    {
        try {
            return parent::decryptCookie($name, $cookie);
        } catch (DecryptException $e) {
            return null;
        }
    }
}
