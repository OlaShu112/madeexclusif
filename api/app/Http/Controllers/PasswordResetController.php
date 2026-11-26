
<?php

namespace App\Http\Controllers;

use App\Models\Store;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Services\StoreCustomerService;
use App\Services\TransactionalEmailService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Throwable;

class PasswordResetController extends Controller
{
    public function forgotPassword(
        Request $request,
        StoreCustomerService $customers
    ): JsonResponse {

        $data = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'store' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $store = ! empty($data['store'])
            ? $customers->resolveStore($data['store'])
            : null;

        Password::sendResetLink(
            $request->only('email'),

            function (
                User $user,
                string $token
            ) use ($store): void {

                $user->notify(
                    new ResetPasswordNotification(
                        $token,
                        $store?->id
                    )
                );
            }
        );

        return response()->json([
            'message' =>
                'If an account exists with this email address, a password reset link will be sent.',
        ]);
    }

    public function resetPassword(
        Request $request,
        TransactionalEmailService $emails,
        StoreCustomerService $customers
    ): JsonResponse {

        $data = $request->validate([
            'token' => [
                'required',
                'string',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'password' => [
                'required',
                'confirmed',
                PasswordRule::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],

            'store' => [
                'nullable',
                'string',
                'max:255',
            ],
        ]);

        $store = ! empty($data['store'])
            ? $customers->resolveStore($data['store'])
            : null;

        $resetUser = null;

        $notificationId = (string) Str::uuid();

        $status = Password::reset(
            $request->only(
                'email',
                'password',
                'password_confirmation',
                'token'
            ),

            function (
                User $user,
                string $password
            ) use (&$resetUser): void {

                DB::transaction(function () use (
                    $user,
                    $password
                ) {
                    $user->forceFill([
                        'password' => Hash::make($password),

                        'remember_token' => Str::random(60),

                    ])->save();

                    $user->tokens()->delete();
                });

                $resetUser = $user;

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {

            return response()->json([
                'message' =>
                    'The password reset link is invalid or has expired.',
            ], 422);
        }

        if ($resetUser instanceof User) {

            try {

                $businessName =
                    $store?->name ?? 'Made Exclusif';

                $emails->queue(

                    notificationKey:
                        "user:{$resetUser->id}:password-changed:{$notificationId}",

                    emailType:
                        'password_changed',

                    recipientEmail:
                        $resetUser->email,

                    subject:
                        "Security Alert: Your Password Has Been Changed | {$businessName}",

                    recipientName:
                        $resetUser->name,

                    userId:
                        $resetUser->id,

                    storeId:
                        $store?->id
                );

            } catch (Throwable $exception) {

                report($exception);
            }
        }

        return response()->json([
            'message' =>
                'Your password has been successfully reset. Please log in with your new password.',
        ]);
    }
}
