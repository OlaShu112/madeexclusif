<?php

namespace App\Http\Controllers;

use App\Mail\Customer\WelcomeMail;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class EmailVerificationController extends Controller
{
    public function verify(
        Request $request,
        string $id,
        string $hash
    ): RedirectResponse {
        abort_unless(
            $request->hasValidSignature(),
            403,
            'Invalid or expired verification link.'
        );

        DB::transaction(function () use ($id, $hash) {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($id);

            abort_unless(
                hash_equals(
                    sha1($user->getEmailForVerification()),
                    $hash
                ),
                403,
                'Invalid verification link.'
            );

            if ($user->hasVerifiedEmail()) {
                return;
            }

            if ($user->markEmailAsVerified()) {
                DB::afterCommit(function () use ($user) {
                    event(new Verified($user));

                    Mail::to($user->email)
                        ->queue(new WelcomeMail($user));
                });
            }
        });

        return redirect()->away(
            rtrim(config('app.frontend_url'), '/')
            . '/?email_verified=1'
        );
    }

    public function resend(
        Request $request
    ): JsonResponse {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Your email is already verified.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'A new verification email has been queued.',
        ]);
    }
}