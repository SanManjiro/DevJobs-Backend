<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\DeveloperProfile;
use App\Models\JobListing;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeveloperTest extends TestCase
{
    use RefreshDatabase;

    /** A developer with a profile and an authenticating token. */
    private function developer(): array
    {
        $user = User::factory()->create();
        DeveloperProfile::factory()->for($user)->create();

        return [$user, $user->createToken('t')->plainTextToken];
    }

    // ---- Candidatures -----------------------------------------------------

    public function test_a_developer_can_apply_to_a_published_job(): void
    {
        [$user, $token] = $this->developer();
        $job = JobListing::factory()->create();

        $this->withToken($token)
            ->postJson("/api/v1/developer/jobs/{$job->id}/apply", [
                'coverLetter' => 'I would love to build this.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.job.id', $job->id);

        $this->assertDatabaseHas('applications', [
            'developer_id' => $user->id,
            'job_id'       => $job->id,
            'cover_letter' => 'I would love to build this.',
        ]);
    }

    public function test_applying_twice_to_the_same_job_is_rejected(): void
    {
        [$user, $token] = $this->developer();
        $job = JobListing::factory()->create();
        Application::factory()->create(['developer_id' => $user->id, 'job_id' => $job->id]);

        $this->withToken($token)
            ->postJson("/api/v1/developer/jobs/{$job->id}/apply")
            ->assertStatus(422);
    }

    public function test_a_developer_cannot_apply_to_a_draft_job(): void
    {
        [, $token] = $this->developer();
        $job = JobListing::factory()->draft()->create();

        $this->withToken($token)
            ->postJson("/api/v1/developer/jobs/{$job->id}/apply")
            ->assertStatus(422);
    }

    public function test_a_developer_cannot_apply_to_an_expired_job(): void
    {
        [, $token] = $this->developer();
        $job = JobListing::factory()->expired()->create();

        $this->withToken($token)
            ->postJson("/api/v1/developer/jobs/{$job->id}/apply")
            ->assertStatus(422);
    }

    public function test_a_company_cannot_reach_the_developer_apply_route(): void
    {
        $company = User::factory()->company()->create();
        $job = JobListing::factory()->create();

        $this->withToken($company->createToken('t')->plainTextToken)
            ->postJson("/api/v1/developer/jobs/{$job->id}/apply")
            ->assertStatus(403);
    }

    public function test_a_pending_application_can_be_withdrawn(): void
    {
        [$user, $token] = $this->developer();
        $application = Application::factory()->create([
            'developer_id' => $user->id,
            'status'       => 'pending',
        ]);

        $this->withToken($token)
            ->deleteJson("/api/v1/developer/applications/{$application->id}")
            ->assertOk();

        $this->assertDatabaseMissing('applications', ['id' => $application->id]);
    }

    public function test_a_processed_application_cannot_be_withdrawn(): void
    {
        [$user, $token] = $this->developer();
        $application = Application::factory()->create([
            'developer_id' => $user->id,
            'status'       => 'accepted',
        ]);

        $this->withToken($token)
            ->deleteJson("/api/v1/developer/applications/{$application->id}")
            ->assertStatus(422);

        $this->assertDatabaseHas('applications', ['id' => $application->id]);
    }

    public function test_a_developer_cannot_withdraw_someone_elses_application(): void
    {
        [, $token] = $this->developer();
        $other = Application::factory()->create(['status' => 'pending']);

        $this->withToken($token)
            ->deleteJson("/api/v1/developer/applications/{$other->id}")
            ->assertStatus(403);
    }

    public function test_the_applications_list_is_camelcase_and_never_leaks_the_company_email(): void
    {
        [$user, $token] = $this->developer();
        $job = JobListing::factory()->create();
        Application::factory()->create(['developer_id' => $user->id, 'job_id' => $job->id]);

        $response = $this->withToken($token)->getJson('/api/v1/developer/applications');

        $response->assertOk()
            ->assertJsonPath('data.0.job.id', $job->id)
            ->assertJsonStructure(['data' => [['id', 'status', 'appliedAt', 'job']]]);

        $email = $job->company->email;
        $this->assertStringNotContainsString($email, $response->getContent());
    }

    // ---- Favoris ----------------------------------------------------------

    public function test_a_developer_can_save_then_unsave_a_job(): void
    {
        [$user, $token] = $this->developer();
        $job = JobListing::factory()->create();

        $this->withToken($token)->postJson("/api/v1/developer/jobs/{$job->id}/save")->assertOk();
        $this->assertDatabaseHas('saved_jobs', ['developer_id' => $user->id, 'job_id' => $job->id]);

        // Idempotent: saving again does not error or duplicate.
        $this->withToken($token)->postJson("/api/v1/developer/jobs/{$job->id}/save")->assertOk();
        $this->assertSame(1, $user->savedJobs()->count());

        $this->withToken($token)->deleteJson("/api/v1/developer/jobs/{$job->id}/save")->assertOk();
        $this->assertDatabaseMissing('saved_jobs', ['developer_id' => $user->id, 'job_id' => $job->id]);
    }

    public function test_the_saved_ids_endpoint_lists_only_saved_jobs(): void
    {
        [$user, $token] = $this->developer();
        $saved = JobListing::factory()->create();
        JobListing::factory()->create(); // not saved
        $user->savedJobs()->attach($saved->id);

        $this->withToken($token)
            ->getJson('/api/v1/developer/saved-jobs/ids')
            ->assertOk()
            ->assertExactJson(['data' => [$saved->id]]);
    }

    // ---- Profil -----------------------------------------------------------

    public function test_a_developer_updates_the_profile_with_camelcase_keys(): void
    {
        [, $token] = $this->developer();

        $this->withToken($token)
            ->putJson('/api/v1/developer/profile', [
                'bio'             => 'I ship structural interfaces.',
                'location'        => 'Dakar, Senegal',
                'githubUrl'       => 'https://github.com/awa',
                'portfolioUrl'    => 'https://awa.dev',
                'yearsExperience' => 6,
            ])
            ->assertOk()
            ->assertJsonPath('data.githubUrl', 'https://github.com/awa')
            ->assertJsonPath('data.yearsExperience', 6);
    }

    public function test_a_developer_can_add_and_remove_a_skill(): void
    {
        [$user, $token] = $this->developer();
        $skill = Skill::create(['name' => 'Rust']);

        $this->withToken($token)
            ->postJson('/api/v1/developer/profile/skills', [
                'skills' => [['id' => $skill->id, 'level' => 'senior']],
            ])
            ->assertOk()
            ->assertJsonPath('data.skills.0.name', 'Rust')
            ->assertJsonPath('data.skills.0.level', 'senior');

        $this->withToken($token)
            ->deleteJson("/api/v1/developer/profile/skills/{$skill->id}")
            ->assertOk()
            ->assertJsonPath('data.skills', []);
    }
}
