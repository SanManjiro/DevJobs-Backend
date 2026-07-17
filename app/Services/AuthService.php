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
            'name'      => $data['name'],
            'email'     => $data['email'],
            'password'  => $data['password'],
            'role'      => $data['role'],
            // Set explicitly: the DB default doesn't hydrate the in-memory model,
            // so the resource would serialize isActive as null otherwise.
            'is_active' => true,
        ]);

        if ($user->isCompany()) {
            CompanyProfile::create(['user_id' => $user->id]);
        } else {
            // Developer and admin both get a developer profile; only a company
            // gets a company profile. The reverse would give an admin one.
            DeveloperProfile::create(['user_id' => $user->id]);
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

        if (! $user || ! $user->password || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        // Un compte désactivé ne se connecte pas — sinon EnsureActive n'a rien
        // à protéger puisque le token serait déjà émis.
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Compte désactivé.'],
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
