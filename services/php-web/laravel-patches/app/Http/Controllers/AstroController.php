<?php

namespace App\Http\Controllers;

use App\Http\Requests\AstroEventsRequest;

class AstroController extends Controller
{
    public function events(AstroEventsRequest $r)
    {
        $body = $r->query('body', 'sun'); // Default to 'sun', also supports 'moon'
        $body = strtolower($body);
        if (!in_array($body, ['sun', 'moon'])) {
            $body = 'sun'; // Fallback to sun if invalid
        }
        
        $lat  = (float) $r->query('lat', 55.7558);
        $lon  = (float) $r->query('lon', 37.6176);
        
        // Date range: support both from_date/to_date and days parameter
        $fromDate = $r->query('from_date');
        $toDate = $r->query('to_date');
        $days = max(1, min(30, (int) $r->query('days', 7)));

        // If from_date/to_date provided, use them; otherwise calculate from days
        if ($fromDate && $toDate) {
            try {
                $from = \Carbon\Carbon::parse($fromDate)->toDateString();
                $to = \Carbon\Carbon::parse($toDate)->toDateString();
                $now = now('UTC')->toDateString();
                
                // Check that from_date is before to_date
                if ($from > $to) {
                    return response()->json([
                        'error' => 'Invalid date range',
                        'message' => 'From date must be before to date'
                    ], 422);
                }
                
                // Check that to_date is not in the future
                if ($to > $now) {
                    return response()->json([
                        'error' => 'Invalid date',
                        'message' => 'To date cannot be in the future',
                        'provided' => $to,
                        'today' => $now
                    ], 422);
                }
            } catch (\Exception $e) {
                return response()->json([
                    'error' => 'Invalid date format',
                    'message' => 'Dates must be in YYYY-MM-DD format',
                    'example' => '2020-12-20'
                ], 422);
            }
        } else {
            // Use days parameter
            $from = now('UTC')->toDateString();
            $to = now('UTC')->addDays($days)->toDateString();
            // Ensure to_date is not in the future
            $now = now('UTC')->toDateString();
            if ($to > $now) {
                $to = $now;
            }
        }

        $appId  = env('ASTRO_APP_ID', '');
        $secret = env('ASTRO_APP_SECRET', '');
        if ($appId === '' || $secret === '') {
            return response()->json([
                'error' => 'Missing ASTRO_APP_ID/ASTRO_APP_SECRET',
                'appId_length' => strlen($appId),
                'secret_length' => strlen($secret),
                'note' => 'Check environment variables in docker-compose.yml'
            ], 500);
        }

        // AstronomyAPI authorization according to official documentation:
        // https://docs.astronomyapi.com/endpoints/bodies/events
        // Endpoint: GET /api/v2/bodies/events/:body
        // Required parameters: from_date, to_date, elevation, time, latitude, longitude
        // Optional parameter: output (rows or table, default is table)
        // According to docs, output=rows returns data.rows[].events[] structure
        $time = now('UTC')->format('H:i:s'); // Format: HH:MM:SS
        
        // Create auth string exactly as per documentation
        // Format: base64_encode("applicationId:applicationSecret")
        $authString = base64_encode($appId . ':' . $secret);
        
        // Use GET request with output=rows to get data.rows[].events[] structure
        // As per documentation example: https://docs.astronomyapi.com/endpoints/bodies/events
        $url = 'https://api.astronomyapi.com/api/v2/bodies/events/' . urlencode($body) . '?' . http_build_query([
            'latitude'  => $lat,
            'longitude' => $lon,
            'from_date' => $from,
            'to_date'   => $to,
            'elevation' => 0,
            'time'      => $time,
            'output'    => 'rows', // Use 'rows' format to get data.rows[].events[] structure
        ]);
        
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $authString,
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: monolith-iss/1.0'
            ],
            CURLOPT_TIMEOUT        => 25,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        
        $raw  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_RESPONSE_CODE) ?: 0;
        $err  = curl_error($ch);
        curl_close($ch);
        
        if ($raw === false || $code >= 400) {
            $errorData = json_decode($raw, true);
            $errorMsg = $errorData['message'] ?? ($err ?: "HTTP " . $code);
            
            // Parse validation errors from API
            $validationErrors = [];
            if (isset($errorData['errors']) && is_array($errorData['errors'])) {
                foreach ($errorData['errors'] as $err) {
                    $field = $err['path'][0] ?? 'unknown';
                    $message = $err['message'] ?? 'Validation error';
                    $validationErrors[$field] = $message;
                }
            }
            
            return response()->json([
                'error' => $errorMsg,
                'code' => $code,
                'raw' => $raw,
                'validation_errors' => $validationErrors,
                'app_id_preview' => substr($appId, 0, 8) . '...',
                'note' => $code === 422 ? 'Validation error: Check your input parameters (latitude: -90 to 90, longitude: -180 to 180)' : 'AstronomyAPI request failed.',
                'hint' => $code === 422 ? 'Ensure latitude is between -90 and 90, longitude is between -180 and 180' : 'Verify 1) Origin in Dashboard = http://localhost:8080, 2) App is active, 3) Credentials are correct.',
                'docs' => 'https://docs.astronomyapi.com/'
            ], $code >= 400 ? $code : 500);
        }
        $json = json_decode($raw, true);
        
        // Add debug info to help diagnose empty results
        $response = $json ?? ['raw' => $raw];
        
        // Check if we have data structure
        if (isset($response['data']['table']['rows']) && count($response['data']['table']['rows']) > 0) {
            $firstRow = $response['data']['table']['rows'][0];
            $hasCells = !empty($firstRow['cells']) && count($firstRow['cells']) > 0;
            
            // If cells are empty, it might mean no events in range, but API key works
            if (!$hasCells) {
                // Try to find events in alternative structures
                $foundEvents = false;
                if (isset($response['data']['table']['header']) && count($response['data']['table']['header']) > 0) {
                    // Headers exist but cells empty - might be a different structure
                    $foundEvents = true;
                }
                
            }
        }
        
        return response()->json($response);
    }
}
