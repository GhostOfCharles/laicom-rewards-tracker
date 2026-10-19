<?php

namespace App\Services;

use Illuminate\Http\Request;

class PulseResponse
{
    public static function make(Request $r, array $data)
    {
        $etag = '"'.hash('sha256', json_encode($data)).'"';
        $response = response()->json($data)->header('Cache-Control', 'no-store, private')->header('Vary', 'Cookie, Authorization')->setEtag(trim($etag, '"'));
        if ($r->headers->get('If-None-Match') === $etag) {
            $response->setNotModified();
        }
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }
}
