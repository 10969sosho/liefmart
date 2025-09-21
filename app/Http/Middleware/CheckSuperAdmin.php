<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSuperAdmin
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
        if (Auth::check() && Auth::user()->isSuperAdmin()) {
            return $next($request);
        }
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['error' => 'Unauthorized. Super Admin permission required.'], 403);
        }
        
        return redirect()->back()->with('error', 'Anda tidak memiliki izin untuk melakukan tindakan ini. Hanya Super Admin yang diperbolehkan.');
    }
} 