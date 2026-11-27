<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureFinanceTestMode
{
    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $request->validate([
            'livemode' => [
                'sometimes',
                'required',
                'boolean',
            ],
        ]);

        abort_if(
            $request->boolean('livemode'),
            403,
            'Live financial operations are not enabled.'
        );

        return $next($request);
    }
}