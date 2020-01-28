<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class APIToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $accessToken = $request->bearerToken();

        $device = DB::table('devices')->where('access_token', $accessToken)->first();

        if($device && $accessToken != null){
            return $next($request);
        }
        return response()->json([
            'message' => 'Not a valid API request.',
        ]);
    }
}
