<?php

namespace Tests\Feature;

use App\Models\CompanyProfile;
use App\Models\JobListing;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicApiTest extends TestCase
{
    use RefreshDatabase;

    private function company(): User
    {
        $company = User::factory()->company()->create();
        CompanyProfile::factory()->for($company)->create();

        return $company;
    }

    /**
     * La régression qui a motivé les API Resources : GET /jobs chargeait
     * company.companyProfile et publiait l'email de l'entreprise sur une route
     * ouverte. $hidden ne couvre que password/remember_token.
     */
    public function test_public_job_listing_never_exposes_company_credentials(): void
    {
        $company = $this->company();
        JobListing::factory()->create(['company_id' => $company->id]);

        $response = $this->getJson('/api/v1/jobs');

        $response->assertOk();
        $body = $response->getContent();

        $this->assertStringNotContainsString($company->email, $body);
        $this->assertStringNotContainsString('email', $body);
        $this->assertStringNotContainsString('is_active', $body);
        $this->assertStringNotContainsString('password', $body);
    }

    public function test_company_endpoints_never_expose_credentials(): void
    {
        $company = $this->company();

        foreach (['/api/v1/companies', "/api/v1/companies/{$company->id}"] as $url) {
            $body = $this->getJson($url)->assertOk()->getContent();

            $this->assertStringNotContainsString($company->email, $body, "leaked in {$url}");
            $this->assertStringNotContainsString('is_active', $body, "leaked in {$url}");
        }
    }

    public function test_draft_and_expired_jobs_are_not_publicly_readable(): void
    {
        $company = $this->company();

        $draft = JobListing::factory()->draft()->create(['company_id' => $company->id]);
        $expired = JobListing::factory()->expired()->create(['company_id' => $company->id]);
        $published = JobListing::factory()->create(['company_id' => $company->id]);

        $this->getJson("/api/v1/jobs/{$draft->id}")->assertNotFound();
        $this->getJson("/api/v1/jobs/{$expired->id}")->assertNotFound();
        $this->getJson("/api/v1/jobs/{$published->id}")->assertOk();

        // Ni dans la liste.
        $this->getJson('/api/v1/jobs')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $published->id);
    }

    public function test_job_payload_is_camel_case_and_flattens_the_skill_pivot(): void
    {
        $company = $this->company();
        $job = JobListing::factory()->create(['company_id' => $company->id]);
        $skill = Skill::create(['name' => 'Laravel']);
        $job->skills()->attach($skill->id, ['required' => true]);

        $this->getJson("/api/v1/jobs/{$job->id}")
            ->assertOk()
            ->assertJsonPath('data.experienceLevel', $job->experience_level)
            ->assertJsonPath('data.salaryMin', $job->salary_min)
            ->assertJsonPath('data.company.id', $company->id)
            // Le client ne doit jamais voir `pivot`.
            ->assertJsonPath('data.skills.0.required', true)
            ->assertJsonMissingPath('data.skills.0.pivot')
            ->assertJsonMissingPath('data.experience_level');
    }

    public function test_job_count_only_counts_publicly_visible_listings(): void
    {
        $company = $this->company();
        JobListing::factory(3)->create(['company_id' => $company->id]);
        JobListing::factory()->draft()->create(['company_id' => $company->id]);
        JobListing::factory()->expired()->create(['company_id' => $company->id]);

        $this->getJson('/api/v1/companies')
            ->assertOk()
            ->assertJsonPath('data.0.jobCount', 3);
    }

    public function test_skills_taxonomy_is_public(): void
    {
        Skill::create(['name' => 'React']);

        $this->getJson('/api/v1/skills')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'React');
    }

    public function test_a_job_without_a_salary_is_served_as_null(): void
    {
        $company = $this->company();
        $job = JobListing::factory()->withoutSalary()->create(['company_id' => $company->id]);

        $this->getJson("/api/v1/jobs/{$job->id}")
            ->assertOk()
            ->assertJsonPath('data.salaryMin', null)
            ->assertJsonPath('data.salaryMax', null);
    }
}
