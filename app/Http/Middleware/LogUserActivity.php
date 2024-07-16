<?php

namespace App\Http\Middleware;

use App\helpers\Helper;
use App\Jobs\CheckUserActivityJob;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class LogUserActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            DB::table('access_log')->insert([
                'user_id' => Auth::user()->id,
                'dt_access' => Helper::toMySQL('now', true)
            ]);
        }

        CheckUserActivityJob::dispatch()->delay(now('America/Sao_Paulo')->addMinutes(1));

        return $next($request);
    }
}
