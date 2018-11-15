<?php

namespace App\Http\Middleware;

use Closure;

class UnserializeFilterConditionFromRequest
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->isMethod('GET') && $request->has('filters')) {

        }
        return $next($request);
    }
}
