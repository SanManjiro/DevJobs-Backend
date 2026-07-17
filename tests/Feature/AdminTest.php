<?php

namespace Tests\Feature;

use App\Models\JobListing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): array
    {
        $user = User::factory()->admin()->create();

        return [$user, $user->createToken('t')->plainTextToken];
    }

    // ---- Utilisateurs ------------------------------------------------------

    public function test_an_admin_lists_users_in_the_contract_shape(): void
    {
        [, $token] = $this->admin();
        User::factory()->count(3)->create();

        $this->withToken($token)
            ->getJson('/api/v1/admin/users')
            ->assertOk()
            ->assertJsonStructure(['data' => [['id', 'name', 'email', 'role', 'isActive', 'joined']]]);
    }

    public function test_an_admin_toggles_another_account(): void
    {
        [, $token] = $this->admin();
        $victim = User::factory()->create(); // active

        $this->withToken($token)
            ->patchJson("/api/v1/admin/users/{$victim->id}/toggle")
            ->assertOk()
            ->assertJsonPath('data.isActive', false);

        $this->assertDatabaseHas('users', ['id' => $victim->id, 'is_active' => false]);
    }

    public function test_an_admin_cannot_disable_their_own_account(): void
    {
        [$admin, $token] = $this->admin();

        $this->withToken($token)
            ->patchJson("/api/v1/admin/users/{$admin->id}/toggle")
            ->assertStatus(422);

        $this->assertDatabaseHas('users', ['id' => $admin->id, 'is_active' => true]);
    }

    public function test_an_admin_deletes_a_user_but_not_themselves(): void
    {
        [$admin, $token] = $this->admin();
        $victim = User::factory()->create();

        $this->withToken($token)
            ->deleteJson("/api/v1/admin/users/{$victim->id}")
            ->assertOk();
        $this->assertDatabaseMissing('users', ['id' => $victim->id]);

        $this->withToken($token)
            ->deleteJson("/api/v1/admin/users/{$admin->id}")
            ->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    /**
     * The end-to-end promise of EnsureActive: an admin disabling an account
     * cuts off that account's live session on the very next request.
     */
    public function test_disabling_an_account_kills_its_session(): void
    {
        [, $adminToken] = $this->admin();
        $victim = User::factory()->create();
        $victimToken = $victim->createToken('v')->plainTextToken;

        $this->withToken($victimToken)->getJson('/api/v1/auth/me')->assertOk();

        // The Sanctum guard is a singleton across test requests and caches the
        // user it just resolved (the victim). Production re-resolves per HTTP
        // process; forget it before each identity switch so the guard reflects
        // the token actually sent, as it would in production.
        $this->app['auth']->forgetGuards();
        $this->withToken($adminToken)
            ->patchJson("/api/v1/admin/users/{$victim->id}/toggle")
            ->assertOk();

        $this->app['auth']->forgetGuards();
        $this->withToken($victimToken)->getJson('/api/v1/auth/me')->assertStatus(403);
    }

    // ---- Offres ------------------------------------------------------------

    public function test_an_admin_lists_every_job_including_drafts(): void
    {
        [, $token] = $this->admin();
        JobListing::factory()->create();
        JobListing::factory()->draft()->create();

        $this->withToken($token)
            ->getJson('/api/v1/admin/jobs')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => [['id', 'title', 'status', 'applicationCount', 'company']]]);
    }

    public function test_an_admin_deletes_a_job(): void
    {
        [, $token] = $this->admin();
        $job = JobListing::factory()->create();

        $this->withToken($token)
            ->deleteJson("/api/v1/admin/jobs/{$job->id}")
            ->assertOk();

        $this->assertDatabaseMissing('job_listings', ['id' => $job->id]);
    }

    public function test_a_company_cannot_reach_the_admin_area(): void
    {
        $company = User::factory()->company()->create();

        $this->withToken($company->createToken('t')->plainTextToken)
            ->getJson('/api/v1/admin/users')
            ->assertStatus(403);
    }
}
