<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Jobs\SendAccountCreatedSms;
use App\Models\User;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'mobile' => $request->filled('mobile') ? $request->string('mobile')->toString() : null,
            'password' => $request->string('password')->toString(),
        ]);

        $this->assignCustomerRole($user);
        SendAccountCreatedSms::dispatch($user);

        return $this->issueTokenResponse($user);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->only(['email', 'password']);

        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 422);
        }

        /** @var \App\Models\User $user */
        $user = $request->user();

        return $this->issueTokenResponse($user);
    }

    public function social(SocialLoginRequest $request, string $provider): JsonResponse
    {
        $provider = strtolower($provider);

        if (! in_array($provider, ['google', 'facebook'], true)) {
            return response()->json([
                'message' => 'Unsupported provider',
            ], 422);
        }

        $incomingToken = $request->string('access_token', '')->toString() ?: $request->string('id_token', '')->toString();

        try {
            $socialUser = Socialite::driver($provider)
                ->stateless()
                ->userFromToken($incomingToken);
        } catch (Exception $exception) {
            throw ValidationException::withMessages([
                'access_token' => 'Invalid '.$provider.' token.',
            ])->redirectTo(null);
        }

        $user = User::query()
            ->where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if (! $user && $socialUser->getEmail()) {
            $user = User::query()
                ->where('email', $socialUser->getEmail())
                ->first();
        }

        $created = false;

        if (! $user) {
            $user = User::create([
                'name' => $socialUser->getName() ?: $socialUser->getNickname() ?: 'New User',
                'email' => $socialUser->getEmail() ?: Str::uuid().'@'.$provider.'.user',
                'password' => Str::password(32),
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
            ]);

            $created = true;
        } else {
            $user->forceFill([
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
                'avatar_url' => $socialUser->getAvatar(),
            ])->save();
        }

        $this->assignCustomerRole($user);

        if ($created) {
            SendAccountCreatedSms::dispatch($user);
        }

        return $this->issueTokenResponse($user);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out',
        ]);
    }

    private function issueTokenResponse(User $user): JsonResponse
    {
        $user->load('roles');

        return response()->json([
            'token' => $user->createToken('api')->plainTextToken,
            'user' => $user,
        ]);
    }

    private function assignCustomerRole(User $user): void
    {
        $role = Role::query()->firstOrCreate([
            'name' => 'customer',
            'guard_name' => 'web',
        ]);

        if (! $user->hasRole($role)) {
            $user->assignRole($role);
        }
    }
}
