<?php

namespace App\Http\Middleware;

use App\Models\ConfigurationApp;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\UnauthorizedException;

class RoleValidation
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if($request->user()){
            $dataCierre = ConfigurationApp::first();

            if ($dataCierre->cierre == 1) {
                if (!$request->user()->hasAnyRole(["administrador"])){
                    return response()-> json(['status' => 401,"message"=> "Unauthorized"],401);
                } 
            } 

            return $next($request);
        }

        return response()->json(['status' => 'No tiene un rol permitido']);
    }
}
