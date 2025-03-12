<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\GreytHRService;

class GreytHRController extends Controller
{
    protected $greytHRService;

    public function __construct(GreytHRService $greytHRService)
    {
        $this->greytHRService = $greytHRService;
    }

    public function getLoginToken()
    {
        $response = $this->greytHRService->getAccessToken();
        return response()->json($response);
    }
}
