<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Sends a user whose trial or subscription has ended to the subscription page
 * instead of letting them add, change or delete records.
 */
class EnsureAccountIsWritable
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasFullAccess() === false) {
            Inertia::flash('status', 'حسابك للقراءة فقط. اشترك لتضيف وتعدّل.');

            return redirect()->route('subscription');
        }

        return $next($request);
    }
}
