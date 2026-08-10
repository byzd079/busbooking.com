<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckNotGuest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guest()) {
            // Preserve the intended URL (seat ticks live in the query string)
            // so logging in resumes the booking instead of dumping the user home.
            return redirect()->guest(route('login'))
                ->with('error', 'Please sign in to confirm your seats — your selection has been saved.');
        }

        return $next($request);
    }
}
