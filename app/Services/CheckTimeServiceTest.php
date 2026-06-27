<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class CheckTimeServiceTest
{
    private string $baseUrl = "http://54.37.15.111";
    private string $username = "CICA";
    private string $password = "CICA@2025";

    private ?string $jwtToken = null;
    private ?string $generalToken = null;

    // Test api 

    public function testCredentials(string $login, string $password)
    {
        $response = Http::post($this->baseUrl . '/api-token-auth/', [
            "username" => $login,
            "password" => $password
        ]);

        if ($response->failed()) {
            throw new \Exception('Invalid CheckTime credentials.');
        }

        return true;
    }
    /**
     * Get JWT Token (for GET only)
     */
    public function getJwtToken()
    {
        if ($this->jwtToken) return $this->jwtToken;

        $response = Http::post($this->baseUrl . '/jwt-api-token-auth/', [
            "username" => $this->username,
            "password" => $this->password
        ]);

        $this->jwtToken = $response->json('token');
        return $this->jwtToken;
    }

    /**
     * Get GENERAL TOKEN (used for POST/PATCH/DELETE)
     */
    public function getGeneralToken()
    {
        if ($this->generalToken) return $this->generalToken;

        $response = Http::post($this->baseUrl . '/api-token-auth/', [
            "username" => $this->username,
            "password" => $this->password
        ]);

        $this->generalToken = $response->json('token');
        return $this->generalToken;
    }

    /**
     * Staff JWT Token
     */
    public function getStaffJwtToken()
    {
        $response = Http::post($this->baseUrl . '/staff-jwt-api-token-auth/', [
            "username" => $this->username,
            "password" => $this->password
        ]);

        return $response->json('token');
    }

    /**
     * Staff General Token
     */
    public function getStaffGeneralToken()
    {
        $response = Http::post($this->baseUrl . '/staff-api-token-auth/', [
            "username" => $this->username,
            "password" => $this->password
        ]);

        return $response->json('token');
    }

    /**
     * GET uses JWT
     */
    public function get(string $endpoint, array $params = [])
    {
        return Http::withHeaders([
            "Authorization" => "JWT " . $this->getJwtToken(),
            "Accept" => "application/json"
        ])->get($this->baseUrl . $endpoint, $params)->json();
    }

    /**
     * POST uses GENERAL TOKEN
     */
    public function post(string $endpoint, array $data = [])
    {
        return Http::withHeaders([
            "Authorization" => "Token " . $this->getGeneralToken(),
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])->post($this->baseUrl . $endpoint, $data)->json();
    }

    /**
     * PATCH uses GENERAL TOKEN
     */
    public function patch(string $endpoint, array $data = [])
    {
        return Http::withHeaders([
            "Authorization" => "Token " . $this->getGeneralToken(),
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])->patch($this->baseUrl . $endpoint, $data)->json();
    }

    /**
     * PUT uses GENERAL TOKEN
     */
    public function put(string $endpoint, array $data = [])
    {
        return Http::withHeaders([
            "Authorization" => "Token " . $this->getGeneralToken(),
            "Accept" => "application/json",
            "Content-Type" => "application/json"
        ])->put($this->baseUrl . $endpoint, $data)->json();
    }

    /**
     * DELETE uses GENERAL TOKEN
     */
    public function delete(string $endpoint)
    {
        return Http::withHeaders([
            "Authorization" => "Token " . $this->getGeneralToken(),
        ])->delete($this->baseUrl . $endpoint)->json();
    }

    /**
     * GET with Staff Token (for staff-specific endpoints)
     */
    public function getWithStaffToken(string $endpoint, array $params = [])
    {
        return Http::withHeaders([
            "Authorization" => "JWT " . $this->getStaffJwtToken(),
            "Accept" => "application/json"
        ])->get($this->baseUrl . $endpoint, $params)->json();
    }
}