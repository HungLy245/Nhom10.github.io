<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class Cors
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        
        if (method_exists($response, 'header')) {
            $response->header('Access-Control-Allow-Origin', 'https://phonevibe.kesug.com');
            $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, PATCH, DELETE, OPTIONS');
            $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN');
            $response->header('Access-Control-Allow-Credentials', 'true');
            $response->header('Access-Control-Max-Age', '86400');
        }

        if ($request->isMethod('OPTIONS')) {
            return response()->json('', 200, [
                'Access-Control-Allow-Origin' => 'https://phonevibe.kesug.com',
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With, X-XSRF-TOKEN',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400'
            ]);
        }

        return $response;
    }
} 