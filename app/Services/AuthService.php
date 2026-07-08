<?php

namespace App\Services;

use App\Models\CompanyProfile;
use App\Models\DeveloperProfile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Enregistre un nouvel utilisateur et crée son profil correspondant.
     */
    public function register(array $data): array
    {
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'],
            'role'     => $data['role'],
        ]);

        if ($user->isDeveloper()) {
            DeveloperProfile::create(['user_id' => $user->id]);
        } else {
            CompanyProfile::create(['user_id' => $user->id]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Vérifie les identifiants et retourne un token.
     *
     * @throws ValidationException
     */
    public function login(string $email, string $password): array
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return compact('user', 'token');
    }

    /**
     * Change le mot de passe de l'utilisateur connecté.
     * Révoque tous les autres tokens sauf celui en cours.
     */
    public function changePassword(User $user, string $currentPassword, string $newPassword, int $currentTokenId): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'currentPassword' => ['Le mot de passe actuel est incorrect.'],
            ]);
        }

        $user->update(['password' => Hash::make($newPassword)]);

        // Révoque tous les tokens sauf le token actif
        $user->tokens()->where('id', '!=', $currentTokenId)->delete();
    }
}
