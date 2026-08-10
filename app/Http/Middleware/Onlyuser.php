<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Onlyuser
{
    /**
     * Reason codes a route can pass as a middleware parameter, so the login
     * page can explain *why* the visitor was bounced here. Keyed rather than
     * free text because middleware parameters split on commas.
     */
    private const REASONS = [
        'booking' => 'Please sign in to confirm your seats — your selection has been saved.',
    ];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ?string $reason = null): Response
    {
        if (Auth::check()) {
            return $next($request);
        }

        // redirect()->guest() stores the current URL in the session as the
        // "intended" target, so the user is returned to the page they asked for
        // after logging in. A plain redirect()->route('login') would drop it —
        // which for the booking flow means losing the whole seat selection.
        return redirect()->guest(route('login'))
            ->with('error', self::REASONS[$reason] ?? 'Please sign in to continue.');
    }
}
