<?php

namespace App\Http\Controllers;

use App\Models\User;
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
    /**
     * Send a password reset link.
     */
    public function forgotPassword(
        Request $request
    ): JsonResponse {

        $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ]);

        Password::sendResetLink(
            $request->only('email')
        );

        return response()->json([
            'message' =>
                'If an account exists with this email address, a password reset link will be sent.',
        ]);
    }

    /**
     * Reset the user's password and register
     * an account security notification.
     */
    public function resetPassword(
        Request $request,
        TransactionalEmailService $emails
    ): JsonResponse {

        $request->validate([
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
        ]);

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

                    // Revoke existing Sanctum API tokens.

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

        /*
         * Register the password changed notification
         * only after a successful password reset.
         *
         * An email queue failure must not prevent
         * the customer from using their new password.
         */

        if ($resetUser instanceof User) {

            try {

                $emails->queue(

                    notificationKey:
                        "user:{$resetUser->id}:password-changed:{$notificationId}",

                    emailType:
                        'password_changed',

                    recipientEmail:
                        $resetUser->email,

                    subject:
                        'Security Alert: Your Password Has Been Changed | Made Exclusif',

                    recipientName:
                        $resetUser->name,

                    userId:
                        $resetUser->id
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
