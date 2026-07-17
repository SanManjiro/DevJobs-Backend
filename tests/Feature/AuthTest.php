<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\DeveloperProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_developer_can_register_and_receives_a_token_and_a_profile(): void
    {
        $response = $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa',
            'email' => 'awa@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'developer',
        ]);

        $response->assertCreated()
            ->assertJsonPath('user.role', 'developer')
            ->assertJsonPath('user.isActive', true)
            ->assertJsonStructure(['user' => ['id', 'name', 'email'], 'token']);

        $user = User::where('email', 'awa@example.com')->first();
        $this->assertNotNull($user->developerProfile);
        $this->assertNull($user->companyProfile);
    }

    public function test_a_company_registration_creates_a_company_profile_not_a_developer_one(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Wave',
            'email' => 'wave@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'company',
        ])->assertCreated();

        $user = User::where('email', 'wave@example.com')->first();
        $this->assertNotNull($user->companyProfile);
        $this->assertNull($user->developerProfile);
    }

    public function test_login_returns_a_token_and_never_the_password(): void
    {
        User::factory()->create(['email' => 'a@b.com']); // password = 'password'

        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'a@b.com',
            'password' => 'password',
        ]);

        $response->assertOk()->assertJsonStructure(['user' => ['id'], 'token']);
        $this->assertStringNotContainsString('password', $response->getContent());
    }

    public function test_a_disabled_account_cannot_log_in(): void
    {
        User::factory()->disabled()->create(['email' => 'off@b.com']);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'off@b.com',
            'password' => 'password',
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    /**
     * The point of EnsureActive: a token issued before the account was disabled
     * must stop working. Login alone can't cover this — the token already exists.
     */
    public function test_a_token_stops_working_once_the_account_is_disabled(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('t')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertOk();

        $user->update(['is_active' => false]);

        // The Sanctum guard is a singleton across test requests and caches the
        // user it resolved above; a real HTTP request re-resolves per process.
        // Forget it so this second call reflects the DB, as production would.
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/v1/auth/me')->assertStatus(403);
    }

    public function test_a_developer_cannot_create_a_job_listing(): void
    {
        $dev = User::factory()->create();
        DeveloperProfile::factory()->for($dev)->create();

        $this->withToken($dev->createToken('t')->plainTextToken)
            ->postJson('/api/v1/jobs', [
                'title' => 'x',
                'description' => 'y',
                'type' => 'full_time',
                'experienceLevel' => 'junior',
            ])
            ->assertStatus(403);
    }

    public function test_a_company_can_create_a_job_listing(): void
    {
        $company = User::factory()->company()->create();
        CompanyProfile::factory()->for($company)->create();

        $this->withToken($company->createToken('t')->plainTextToken)
            ->postJson('/api/v1/jobs', [
                'title' => 'Senior Engineer',
                'description' => 'Build things',
                'type' => 'full_time',
                'experienceLevel' => 'senior',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Senior Engineer');
    }

    public function test_the_oauth_exchange_rejects_an_unknown_code(): void
    {
        $this->postJson('/api/v1/auth/exchange', ['code' => 'does-not-exist'])
            ->assertStatus(422);
    }

    public function test_the_oauth_exchange_trades_a_valid_code_once(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('oauth')->plainTextToken;
        cache()->put('oauth_code:abc', $token, 60);

        $this->postJson('/api/v1/auth/exchange', ['code' => 'abc'])
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('token', $token);

        // Single use: the code is gone.
        $this->postJson('/api/v1/auth/exchange', ['code' => 'abc'])->assertStatus(422);
    }
}
