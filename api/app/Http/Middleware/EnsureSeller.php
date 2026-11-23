<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Lets a request through only for a user who owns an approved (active) store. */
class EnsureSeller
{
    public function handle(Request $request, Closure $next): Response
    {
        $store = $request->user()?->store;

        if (! $store) {
            abort(403, 'You do not have a store yet.');
        }
        if ($store->status === 'pending') {
            abort(403, 'Your store is awaiting approval.');
        }
        if ($store->status !== 'active') {
            abort(403, 'Your store has been suspended. Please contact support.');
        }

        $request->attributes->set('store', $store);

        return $next($request);
    }
}