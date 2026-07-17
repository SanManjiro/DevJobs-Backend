<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\DeveloperProfile;
use App\Models\JobListing;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call(SkillSeeder::class);
        $skills = Skill::all();

        // Comptes connus, mot de passe « password ».
        $admin = User::factory()->admin()->create([
            'name'  => 'Admin',
            'email' => 'admin@devjobs.test',
        ]);

        $mainCompany = User::factory()->company()->create([
            'name'  => 'Wave',
            'email' => 'company@devjobs.test',
        ]);
        CompanyProfile::factory()->for($mainCompany)->create([
            'description' => 'Mobile money for everyone. Wave is building the cash app for Africa — bringing radically affordable financial services to millions.',
            'industry'    => 'Fintech',
            'country'     => 'Senegal',
            'website'     => 'https://wave.com',
            'size'        => 'grande_entreprise',
        ]);

        $mainDev = User::factory()->create([
            'name'  => 'Awa Ndiaye',
            'email' => 'dev@devjobs.test',
        ]);
        DeveloperProfile::factory()->for($mainDev)->create([
            'location'         => 'Dakar, Senegal',
            'years_experience' => 6,
        ]);

        // Un compte désactivé, pour vérifier EnsureActive en phase 2.
        User::factory()->disabled()->create([
            'name'  => 'Disabled Dev',
            'email' => 'disabled@devjobs.test',
        ]);

        $companies = User::factory(5)->company()->create()
            ->each(fn (User $c) => CompanyProfile::factory()->for($c)->create())
            ->push($mainCompany);

        $developers = User::factory(7)->create()
            ->each(fn (User $d) => DeveloperProfile::factory()->for($d)->create())
            ->push($mainDev);

        foreach ($companies as $company) {
            // Le mélange est délibéré : il faut des brouillons et des offres
            // périmées pour prouver que le public ne les voit jamais.
            $published = JobListing::factory(4)->create(['company_id' => $company->id]);
            JobListing::factory(1)->draft()->create(['company_id' => $company->id]);
            JobListing::factory(1)->expired()->create(['company_id' => $company->id]);
            JobListing::factory(1)->withoutSalary()->create(['company_id' => $company->id]);

            foreach ($published as $job) {
                $job->skills()->attach(
                    $skills->random(rand(3, 5))->mapWithKeys(
                        fn (Skill $s) => [$s->id => ['required' => fake()->boolean(60)]],
                    )->all(),
                );
            }
        }

        foreach ($developers as $dev) {
            $dev->developerProfile->skills()->attach(
                $skills->random(rand(2, 5))->mapWithKeys(
                    fn (Skill $s) => [$s->id => [
                        'level' => fake()->randomElement(['junior', 'intermediaire', 'senior']),
                    ]],
                )->all(),
            );
        }

        // Candidatures : unique(developer_id, job_id) impose de ne pas tirer
        // deux fois la même paire.
        $openJobs = JobListing::query()->published()->notExpired()->get();
        foreach ($developers as $dev) {
            foreach ($openJobs->random(min(4, $openJobs->count())) as $job) {
                Application::factory()->create([
                    'developer_id' => $dev->id,
                    'job_id'       => $job->id,
                ]);
            }
        }

        $this->command->info(sprintf(
            'Seeded: %d users, %d jobs (%d public), %d applications. Login: admin@ / company@ / dev@devjobs.test — password',
            User::count(),
            JobListing::count(),
            $openJobs->count(),
            Application::count(),
        ));
    }
}
