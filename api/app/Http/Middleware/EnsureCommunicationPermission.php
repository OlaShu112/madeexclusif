<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Communication\CommunicationPermissionService;

use Closure;
use Illuminate\Http\Request;

use Symfony\Component\HttpFoundation\Response;

class EnsureCommunicationPermission
{
    public function __construct(
        private CommunicationPermissionService $permissions
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Administrator Verification
        |--------------------------------------------------------------------------
        */

        abort_unless(

            $user instanceof User
                && $user->isAdmin(),

            403,

            'Administrator access required.'
        );

        /*
        |--------------------------------------------------------------------------
        | Communication Module Availability
        |--------------------------------------------------------------------------
        */

        abort_unless(

            config('communication.enabled', false),

            503,

            'The Communication module is currently unavailable.'
        );

        /*
        |--------------------------------------------------------------------------
        | Route Permission Mapping
        |--------------------------------------------------------------------------
        */

        $routeName = $request->route()?->getName();

        $mapping = config(
            'communication.route_permissions',
            []
        );

        $permission = $mapping[$routeName] ?? null;

        /*
        |--------------------------------------------------------------------------
        | Deny Unconfigured Routes
        |--------------------------------------------------------------------------
        */

        abort_unless(

            is_string($permission)
                && $permission !== '',

            403,

            'Communication permission has not been configured for this operation.'
        );

        /*
        |--------------------------------------------------------------------------
        | Verify Administrator Permission
        |--------------------------------------------------------------------------
        */

        abort_unless(

            $this->permissions->has(
                $user,
                $permission
            ),

            403,

            'You do not have permission to perform this Communication operation.'
        );

        return $next($request);
    }
}