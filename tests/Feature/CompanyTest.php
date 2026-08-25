<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\CompanyProfile;
use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    /** A company with a profile and a token. */
    private function company(): array
    {
        $user = User::factory()->company()->create();
        CompanyProfile::factory()->for($user)->create();

        return [$user, $user->createToken('t')->plainTextToken];
    }

    // ---- Offres ------------------------------------------------------------

    public function test_a_company_lists_its_own_jobs_including_drafts(): void
    {
        [$user, $token] = $this->company();
        JobListing::factory()->create(['company_id' => $user->id]);
        JobListing::factory()->draft()->create(['company_id' => $user->id]);
        JobListing::factory()->create(); // another company's — must not appear

        $this->withToken($token)
            ->getJson('/api/v1/company/jobs')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'status', 'applicationCount']]]);
    }

    public function test_a_company_creates_a_job_with_camelcase_payload(): void
    {
        [, $token] = $this->company();

        $this->withToken($token)
            ->postJson('/api/v1/jobs', [
                'title'           => 'Staff Engineer',
                'description'     => 'Lead the platform',
                'type'            => 'full_time',
                'experienceLevel' => 'senior',
                'salaryMin'       => 900000,
                'salaryMax'       => 1400000,
            ])
            ->assertCreated()
            ->assertJsonPath('data.salaryMin', 900000)
            ->assertJsonPath('data.experienceLevel', 'senior');

        $this->assertDatabaseHas('job_listings', [
            'title'            => 'Staff Engineer',
            'salary_min'       => 900000,
            'experience_level' => 'senior',
        ]);
    }

    public function test_a_company_cannot_view_another_companys_job(): void
    {
        [, $token] = $this->company();
        $other = JobListing::factory()->create();

        $this->withToken($token)
            ->getJson("/api/v1/company/jobs/{$other->id}")
            ->assertStatus(403);
    }

    // ---- Candidatures reçues ----------------------------------------------

    public function test_a_company_sees_its_applicants_by_name_never_their_email(): void
    {
        [$user, $token] = $this->company();
        $job = JobListing::factory()->create(['company_id' => $user->id]);
        $dev = User::factory()->create(['name' => 'Awa Ndiaye', 'email' => 'awa@secret.test']);
        Application::factory()->create(['developer_id' => $dev->id, 'job_id' => $job->id]);

        $response = $this->withToken($token)
            ->getJson("/api/v1/company/jobs/{$job->id}/applications");

        $response->assertOk()
            ->assertJsonPath('data.0.developer.name', 'Awa Ndiaye')
            ->assertJsonStructure(['data' => [['id', 'status', 'coverLetter', 'developer' => ['id', 'name']]]]);

        $this->assertStringNotContainsString('awa@secret.test', $response->getContent());
    }

    public function test_a_company_cannot_read_applications_on_a_job_it_does_not_own(): void
    {
        [, $token] = $this->company();
        $foreign = JobListing::factory()->create();

        $this->withToken($token)
            ->getJson("/api/v1/company/jobs/{$foreign->id}/applications")
            ->assertStatus(403);
    }

    public function test_a_company_advances_an_application_status(): void
    {
        [$user, $token] = $this->company();
        $job = JobListing::factory()->create(['company_id' => $user->id]);
        $application = Application::factory()->create(['job_id' => $job->id, 'status' => 'pending']);

        $this->withToken($token)
            ->patchJson("/api/v1/company/applications/{$application->id}/status", ['status' => 'accepted'])
            ->assertOk()
            ->assertJsonPath('data.status', 'accepted');

        $this->assertDatabaseHas('applications', ['id' => $application->id, 'status' => 'accepted']);
    }

    public function test_a_company_cannot_set_a_status_back_to_pending(): void
    {
        [$user, $token] = $this->company();
        $job = JobListing::factory()->create(['company_id' => $user->id]);
        $application = Application::factory()->create(['job_id' => $job->id]);

        $this->withToken($token)
            ->patchJson("/api/v1/company/applications/{$application->id}/status", ['status' => 'pending'])
            ->assertStatus(422);
    }

    public function test_a_company_cannot_touch_an_application_on_a_foreign_job(): void
    {
        [, $token] = $this->company();
        $application = Application::factory()->create(); // belongs to some other company's job

        $this->withToken($token)
            ->patchJson("/api/v1/company/applications/{$application->id}/status", ['status' => 'viewed'])
            ->assertStatus(403);
    }

    // ---- Profil ------------------------------------------------------------

    public function test_a_company_updates_its_profile(): void
    {
        [, $token] = $this->company();

        $this->withToken($token)
            ->putJson('/api/v1/company/profile', [
                'description' => 'We build payments rails.',
                'industry'    => 'Fintech',
                'website'     => 'https://wave.com',
                'country'     => 'Senegal',
                'size'        => 'grande_entreprise',
            ])
            ->assertOk()
            ->assertJsonPath('data.industry', 'Fintech')
            ->assertJsonPath('data.size', 'grande_entreprise');
    }

    public function test_a_developer_cannot_reach_the_company_area(): void
    {
        $dev = User::factory()->create();

        $this->withToken($dev->createToken('t')->plainTextToken)
            ->getJson('/api/v1/company/jobs')
            ->assertStatus(403);
    }
}
