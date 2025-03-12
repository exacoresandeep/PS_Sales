<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GreytHRService
{
    protected $apiUser;
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiUser = 'demo1';
        $this->apiKey = 'b223954f-bdc9-406f-b87f-62f158d9734e';
        $this->baseUrl = 'https://api.greythr.com';
    }

    public function getAccessToken()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Api-Key' => $this->apiKey,
            'Api-User' => $this->apiUser,
        ])->post("{$this->baseUrl}/auth/v2/token", [
            'grant_type' => 'client_credentials'
        ]);

        if ($response->successful()) {
            return $response->json();
        }

        return [
            'error' => 'Failed to fetch access token',
            'details' => $response->json(),
        ];
    }
}
