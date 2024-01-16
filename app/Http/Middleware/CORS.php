<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CORS
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        // $response->headers->set('Access-Control-Allow-Origin', 'https://app.mrcosmeticni.com');
        // $response->headers->set('Access-Control-Allow-Credentials', 'true');
        // $response->headers->set('Access-Control-Allow-Methods', 'GET, OPTIONS, POST, PUT, PAT');
        // $response->headers->set('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, X-Token-Auth, Authorization');
        
        $response->headers->set('Access-Control-Allow-Origin', env('APP_CORS_ACAO', 'https://app.mrcosmeticni.com'));
        $response->headers->set('Access-Control-Allow-Credentials', env('APP_CORS_ACAC', 'true'));
        $response->headers->set('Access-Control-Allow-Methods', env('APP_CORS_ACAM', 'GET, OPTIONS, POST, PUT, PAT, DELETE'));
        $response->headers->set('Access-Control-Allow-Headers', env('APP_CORS_ACAH','X-Requested-With, Content-Type, X-Token-Auth, Authorization, Access-Control-Allow-Origin, Access-Control-Allow-Headers, Referer, Accept'));
        
        return $response; 
    }
}
