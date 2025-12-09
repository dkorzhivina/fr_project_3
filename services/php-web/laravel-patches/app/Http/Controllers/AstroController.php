<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AstroController extends Controller
{
    public function events(Request $r)
    {
        $r->validate([
            'lat'  => ['nullable','numeric'],
            'lon'  => ['nullable','numeric'],
            'days' => ['nullable','integer','min:1','max:30'],
        ]);

        $lat  = (float) $r->query('lat', 55.7558);
        $lon  = (float) $r->query('lon', 37.6176);
        $days = max(1, min(30, (int) $r->query('days', 7)));

        $from = now('UTC')->toDateString();
        $to   = now('UTC')->addDays($days)->toDateString();

        $appId  = env('ASTRO_APP_ID', '');
        $secret = env('ASTRO_APP_SECRET', '');
        if ($appId === '' || $secret === '') {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => 'CONFIG_MISSING',
                    'message' => 'Missing ASTRO_APP_ID/ASTRO_APP_SECRET',
                ],
            ], 200);
        }

        $url  = 'https://api.astronomyapi.com/api/v2/bodies/events?' . http_build_query([
            'latitude'  => $lat,
            'longitude' => $lon,
            'from'      => $from,
            'to'        => $to,
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: monolith-iss/1.0'
            ],
            CURLOPT_USERPWD        => $appId . ':' . $secret, // Basic auth, без ручных заголовков
            CURLOPT_HTTPAUTH       => CURLAUTH_BASIC,
            CURLOPT_TIMEOUT        => 25,
        ]);
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: 0;
        $err  = curl_error($ch);
        curl_close($ch);

        if ($raw === false || $code >= 400) {
            return response()->json([
                'ok' => false,
                'error' => [
                    'code' => $code === 403 ? 'UPSTREAM_403' : 'UPSTREAM_ERROR',
                    'message' => $err ?: ("HTTP " . $code),
                    'status' => $code,
                ],
                'raw' => $raw,
            ], 200);
        }
        $json = json_decode($raw, true);
        return response()->json($json ?? ['raw' => $raw]);
    }
}
