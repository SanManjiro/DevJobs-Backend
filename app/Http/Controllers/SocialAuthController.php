<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\DeveloperProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;
use Laravel\Socialite\Facades\Socialite;

/**
 * OAuth via GitHub and Google.
 *
 * Stateless (this is a token API, there is no session to hold OAuth state).
 * The provider redirects the browser to our `callback`; we then hand the
 * browser a short-lived one-time code so the token never rides in a URL. The
 * Next frontend trades that code for the token through `exchange`, server-side.
 */
class SocialAuthController extends Controller
{
    private const CODE_TTL_SECONDS = 60;

    public function redirect(string $provider): RedirectResponse
    {
        return Socialite::driver($provider)
            ->stateless()
            ->scopes($provider === 'github' ? ['user:email'] : [])
            ->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (\Throwable) {
            return $this->frontendError('oauth_failed');
        }

        $email = $socialUser->getEmail();

        $user = User::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        // Link to an existing account by email — GitHub and Google both verify
        // it, so an email match is the same person.
        if (! $user && $email) {
            $user = User::where('email', $email)->first();
            $user?->update([
                'provider'    => $provider,
                'provider_id' => $socialUser->getId(),
            ]);
        }

        if (! $user) {
            // GitHub can withhold the email when it is private; without it we
            // cannot create an account keyed on a unique email.
            if (! $email) {
                return $this->frontendError('email_unavailable');
            }

            $user = User::create([
                'name'              => $socialUser->getName() ?: $socialUser->getNickname() ?: 'Developer',
                'email'             => $email,
                'password'          => null,
                'role'              => 'developer',
                'is_active'         => true,
                'provider'          => $provider,
                'provider_id'       => $socialUser->getId(),
                'email_verified_at' => now(),
            ]);
            DeveloperProfile::create(['user_id' => $user->id]);
        }

        if (! $user->is_active) {
            return $this->frontendError('account_disabled');
        }

        $token = $user->createToken('oauth_token')->plainTextToken;

        // One-time, short-lived. Pulled (deleted) on first exchange.
        $code = Str::random(64);
        Cache::put("oauth_code:{$code}", $token, self::CODE_TTL_SECONDS);

        return redirect(rtrim(config('services.frontend_url'), '/')."/auth/callback?code={$code}");
    }

    /**
     * The frontend trades the one-time code for the token, server-side.
     */
    public function exchange(Request $request): JsonResponse
    {
        $data = $request->validate(['code' => ['required', 'string']]);

        $token = Cache::pull("oauth_code:{$data['code']}");
        if (! $token) {
            return response()->json(['message' => 'Code invalide ou expiré.'], 422);
        }

        $user = PersonalAccessToken::findToken($token)?->tokenable;
        if (! $user) {
            return response()->json(['message' => 'Code invalide ou expiré.'], 422);
        }

        return response()->json([
            'user'  => new UserResource($user),
            'token' => $token,
        ]);
    }

    private function frontendError(string $reason): RedirectResponse
    {
        return redirect(rtrim(config('services.frontend_url'), '/')."/login?error={$reason}");
    }
}
