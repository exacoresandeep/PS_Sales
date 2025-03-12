<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GreytHRService;
use Illuminate\Support\Facades\Http;

class GreytHRController extends Controller
{
 
    public function getAccessToken()
    {
        $apiUser = 'demo1';
        $apiKey = 'b223954f-bdc9-406f-b87f-62f158d9734e';

        $response = Http::asForm()->withHeaders([
            'Authorization' => 'Basic ' . base64_encode("$apiUser:$apiKey"),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ])->withOptions([
            'verify' => false, 
        ])->post('https://tousifapisso.greythr.com/uas/v1/oauth2/client-token', [
            'grant_type' => 'client_credentials',
        ]);

        return response()->json($response->json(), $response->status());
    }
}
