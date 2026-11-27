<?php

namespace App\Http\Middleware;

use App\Services\Finance\FinancePermissionService;

use Closure;

use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;

class EnsureFinancePermission
{
    public function __construct(
        private FinancePermissionService $permissions
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $user = $request->user();

        abort_unless(

            $user && $user->isAdmin(),

            403,

            'Administrator access required.'
        );

        $routeName = $request->route()?->getName();

        $mapping = config(
            'finance.route_permissions',
            []
        );

        $permission = $mapping[$routeName]
            ?? null;

        abort_unless(

            $permission !== null,

            403,

            'Finance permission has not been configured for this operation.'
        );

        abort_unless(

            $this->permissions->has(
                $user,
                $permission
            ),

            403,

            'You do not have permission to perform this Finance operation.'
        );

        return $next($request);
    }
}