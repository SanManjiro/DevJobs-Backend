<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crée l'unique compte admin de production à partir des variables
 * d'environnement — jamais de mot de passe en dur.
 *
 * Utilisation (une seule fois, après le déploiement) :
 *   ADMIN_EMAIL=... ADMIN_PASSWORD=... php artisan db:seed --class=AdminSeeder
 *
 * ou en renseignant ADMIN_EMAIL / ADMIN_PASSWORD dans le .env du serveur.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if (! $email || ! $password) {
            $this->command->error(
                'ADMIN_EMAIL et ADMIN_PASSWORD doivent être définis avant de lancer AdminSeeder.'
            );
            return;
        }

        // updateOrCreate : rejouable sans créer de doublon. Le mot de passe est
        // fourni en clair — le cast 'hashed' du modèle User le hache tout seul.
        $admin = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => env('ADMIN_NAME', 'Admin'),
                'password'          => $password,
                'role'              => 'admin',
                'is_active'         => true,
                'email_verified_at' => now(),
            ],
        );

        $this->command->info("Admin prêt : {$admin->email}");
    }
}
