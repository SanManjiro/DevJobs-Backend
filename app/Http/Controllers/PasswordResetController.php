<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Services\AuthService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{
    public function __construct(private AuthService $authService) {}

    /**
     * Change le mot de passe de l'utilisateur connecté.
     */
    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $data = $request->validated();

        $this->authService->changePassword(
            user: $request->user(),
            currentPassword: $data['currentPassword'],
            newPassword: $data['newPassword'],
            currentTokenId: $request->user()->currentAccessToken()->id,
        );

        return response()->json(['message' => 'Mot de passe mis à jour avec succès.']);
    }

    /**
     * Envoie un lien de réinitialisation par e-mail.
     */
    public function sendResetLink(ForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Lien de réinitialisation envoyé.']);
        }

        return response()->json(['message' => "Impossible d'envoyer le lien."], 422);
    }

    /**
     * Réinitialise le mot de passe via le token reçu par e-mail.
     */
    public function reset(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            // Extrait uniquement les champs nécessaires de la requête HTTP
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Révoque tous les tokens Sanctum existants
                $user->tokens()->delete();

                // Déclenche un événement (ex: e-mail de confirmation)
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
        }

        return response()->json(['message' => 'Le token est invalide ou expiré.'], 422);
    }
}
