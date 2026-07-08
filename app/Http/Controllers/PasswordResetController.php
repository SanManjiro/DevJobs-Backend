<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Password;

class PasswordResetController extends Controller
{

    public function changePassword(Request $request)
    {
        $data = $request->validate([
            'currentPassword' => 'required',
            'newPassword' => 'required|string|min:8|confirmed'
        ]);
        $user = $request->user();
        if (!Hash::check($data['currentPassword'], $user->password)) {
            return response()->json([
                'message' => 'Le mot de passe actuel est incorrect.'
            ], 422);
        }
        $user->update([
            'password' => Hash::make($request->password),
        ]);
        $currentTokenId = $request->user()->currentAccessToken()->id;
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();

        return response()->json([
            'message' => 'Mot de passe mis à jour avec succès.'
        ]);
    }

    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json(['message' => 'Lien de réinitialisation envoyé.']);
        }

        return response()->json([
            'message' => 'Impossible d\'envoyer le lien.'
        ], 422);
    }



    public function reset(Request $request)
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $status = Password::reset(
            //Extrait uniquement les champs email, password et password_confirmation de la requete http
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password),
                ])->save();

                // Révoque tous les tokens Sanctum existants
                $user->tokens()->delete();
                //Declenche un evenement qui peut etre utilise pour l'envoie de mail de reussite de modification du mots de passe
                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Mot de passe réinitialisé avec succès.']);
        }

        return response()->json([
            'message' => 'Le token est invalide ou expiré.'
        ], 422);
    }
}
