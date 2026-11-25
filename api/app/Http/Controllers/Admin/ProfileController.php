<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProfileController extends Controller
{
    private function profile(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'avatar_url' => $user->avatar_url,
            'email_verified_at' => $user->email_verified_at,
            'created_at' => $user->created_at,
        ];
    }

    public function show(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->profile($request->user()),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
        ]);

        $user = $request->user();

        $user->update([
            'name' => trim($data['name']),
            'phone' => $data['phone'] ?? null,
        ]);

        return response()->json([
            'message' => 'Your profile has been updated.',
            'user' => $this->profile($user->fresh()),
        ]);
    }

    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
                'dimensions:min_width=100,min_height=100,max_width=4000,max_height=4000',
            ],
        ]);

        $userId = $request->user()->id;

        $path = $request->file('avatar')->store(
            'avatars/'.$userId,
            'public'
        );

        if (!$path) {
            throw new RuntimeException(
                'Unable to save the profile photograph.'
            );
        }

        try {
            [$user, $oldAvatar] = DB::transaction(
                function () use ($userId, $path) {
                    $user = User::query()
                        ->lockForUpdate()
                        ->findOrFail($userId);

                    $oldAvatar = $user->avatar;

                    $user->update([
                        'avatar' => $path,
                    ]);

                    return [$user, $oldAvatar];
                },
                3
            );
        } catch (Throwable $e) {
            Storage::disk('public')->delete($path);
            throw $e;
        }

        if ($oldAvatar && $oldAvatar !== $path) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return response()->json([
            'message' => 'Profile photograph updated.',
            'user' => $this->profile($user),
        ]);
    }

    public function removeAvatar(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        [$user, $oldAvatar] = DB::transaction(
            function () use ($userId) {
                $user = User::query()
                    ->lockForUpdate()
                    ->findOrFail($userId);

                $oldAvatar = $user->avatar;

                $user->update([
                    'avatar' => null,
                ]);

                return [$user, $oldAvatar];
            },
            3
        );

        if ($oldAvatar) {
            Storage::disk('public')->delete($oldAvatar);
        }

        return response()->json([
            'message' => 'Profile photograph removed.',
            'user' => $this->profile($user),
        ]);
    }

    public function updatePassword(
        Request $request
    ): JsonResponse {
        $data = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],
            'password' => [
                'required',
                'confirmed',
                'different:current_password',
                Password::min(12)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        DB::transaction(function () use ($request, $data) {
            $user = User::query()
                ->lockForUpdate()
                ->findOrFail($request->user()->id);

            if (!Hash::check(
                $data['current_password'],
                $user->password
            )) {
                throw ValidationException::withMessages([
                    'current_password' => [
                        'Your current password is incorrect.',
                    ],
                ]);
            }

            $user->forceFill([
                'password' => Hash::make($data['password']),
                'remember_token' => Str::random(60),
            ])->save();

            // Invalidate outstanding password reset links.
            DB::table('password_reset_tokens')
                ->where('email', $user->email)
                ->delete();

            // Revoke all existing API sessions.
            $user->tokens()->delete();
        }, 3);

        return response()->json([
            'message' => 'Password updated. Please sign in again.',
            'reauthentication_required' => true,
        ]);
    }
}
