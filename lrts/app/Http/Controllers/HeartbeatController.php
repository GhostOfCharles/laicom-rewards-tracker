<?php

namespace App\Http\Controllers;

use App\Services\DashboardHeartbeat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeartbeatController extends Controller
{
    public function __invoke(Request $request, DashboardHeartbeat $heartbeat): JsonResponse
    {
        $snapshot = $heartbeat->snapshot($request->user());
        $response = response()->json($snapshot)->header('Cache-Control', 'private, no-cache');
        $response->setEtag($snapshot['fingerprint']);
        $response->isNotModified($request);

        return $response;
    }
}
