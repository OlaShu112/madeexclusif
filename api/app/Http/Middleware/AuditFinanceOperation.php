<?php

namespace App\Http\Middleware;

use App\Services\Finance\FinanceAuditService;

use Closure;
use RuntimeException;
use Throwable;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Auth\Access\AuthorizationException;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class AuditFinanceOperation
{
    public function __construct(
        private FinanceAuditService $audit
    ) {
    }

    public function handle(
        Request $request,
        Closure $next
    ): Response {

        if (
            !in_array(
                $request->method(),
                ['POST', 'PUT', 'PATCH', 'DELETE'],
                true
            )
        ) {

            return $next($request);
        }

        $request->attributes->set(

            'finance_request_id',

            (string) Str::uuid()
        );

        $entityType = $this->audit->entityType(
            $request
        );

        abort_unless(

            $entityType !== null,

            403,

            'Financial audit configuration is missing.'
        );

        $entityId = $request->route('id')
            ? (int) $request->route('id')
            : null;

        $before = null;

        try {

            return DB::transaction(
                function () use (
                    $request,
                    $next,
                    $entityType,
                    &$entityId,
                    &$before
                ) {

                    $before = $this->audit->snapshot(

                        $entityType,

                        $entityId,

                        true
                    );

                    $response = $next($request);

                    if (
                        $response->getStatusCode() < 200
                        || $response->getStatusCode() >= 300
                    ) {

                        throw new FinanceOperationResponseException(
                            $response
                        );
                    }

                    if (!$entityId) {

                        if ($response instanceof JsonResponse) {

                            $payload =
                                $response->getData(true);

                            $entityId = isset(
                                $payload['data']['id']
                            )
                                ? (int) $payload['data']['id']
                                : null;
                        }
                    }

                    if (!$entityId) {

                        throw new RuntimeException(
                            'Unable to identify the financial resource for auditing.'
                        );
                    }

                    $after = $this->audit->snapshot(

                        $entityType,

                        $entityId
                    );

                    if ($after === null) {

                        throw new RuntimeException(
                            'Unable to capture the financial resource state.'
                        );
                    }

                    $this->audit->record(

                        $request,

                        $entityType,

                        $entityId,

                        $before,

                        $after,

                        'success',

                        $response->getStatusCode()
                    );

                    $response->headers->set(

                        'X-Request-ID',

                        $request->attributes->get(
                            'finance_request_id'
                        )
                    );

                    return $response;

                },
                1
            );

        } catch (
            FinanceOperationResponseException $exception
        ) {

            $this->recordFailure(

                $request,

                $entityType,

                $entityId,

                $before,

                $exception->response->getStatusCode()
            );

            return $exception->response;

        } catch (Throwable $exception) {

            $statusCode = match (true) {

                $exception instanceof ValidationException
                    => 422,

                $exception instanceof ModelNotFoundException
                    => 404,

                $exception instanceof AuthorizationException
                    => 403,

                $exception instanceof HttpExceptionInterface
                    => $exception->getStatusCode(),

                default => 500,
            };

            $this->recordFailure(

                $request,

                $entityType,

                $entityId,

                $before,

                $statusCode
            );

            throw $exception;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Record Failed Operations
    |--------------------------------------------------------------------------
    */

    private function recordFailure(

        Request $request,

        ?string $entityType,

        ?int $entityId,

        ?array $before,

        int $statusCode

    ): void {

        try {

            $this->audit->record(

                $request,

                $entityType,

                $entityId,

                $before,

                null,

                'failed',

                $statusCode
            );

        } catch (Throwable $exception) {

            Log::error(

                'Unable to record Finance audit failure.',

                [

                    'request_id' =>
                        $request->attributes->get(
                            'finance_request_id'
                        ),

                    'exception_type' =>
                        get_class($exception),
                ]
            );
        }
    }
}

/*
|--------------------------------------------------------------------------
| Internal Response Exception
|--------------------------------------------------------------------------
*/

class FinanceOperationResponseException extends RuntimeException
{
    public function __construct(
        public readonly Response $response
    ) {

        parent::__construct(
            'Financial operation returned an unsuccessful response.'
        );
    }
}