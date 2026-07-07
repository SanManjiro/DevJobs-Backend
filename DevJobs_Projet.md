# DevJobs — Guide d'implémentation Laravel
## Plateforme d'offres d'emploi pour développeurs

---

> **Règle du jeu** : ce fichier est ton seul point de référence.
> Tu codes, tu testes avec Postman/Insomnia, tu avances étape par étape.
> Ne passe pas à l'étape suivante si la précédente ne fonctionne pas.

---

# SETUP INITIAL

```bash
cd ~/DEV/Laravel
composer create-project laravel/laravel devjobs
cd devjobs
```

Configure le fichier `.env` :

```env
APP_NAME=DevJobs
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=devjobs
DB_USERNAME=root
DB_PASSWORD=ton_mot_de_passe
```

Crée la base de données :

```sql
CREATE DATABASE devjobs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Lance le serveur :

```bash
php artisan serve
```

---

---

# ÉTAPE 1 — Migrations

> Crée les migrations dans cet ordre strict.
> Les FK doivent toujours référencer une table déjà créée.

## 1.1 Installer l'API Sanctum

```bash
php artisan install:api
```

Cela crée `routes/api.php` et installe Sanctum automatiquement.

---

## 1.2 Modifier la migration users existante

La migration `create_users_table` existe déjà. Ouvre-la dans `database/migrations/`
et ajoute les colonnes manquantes dans `up()` :

```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('name', 100);
    $table->string('email', 150)->unique();
    $table->timestamp('email_verified_at')->nullable();
    $table->string('password');
    $table->enum('role', ['developer', 'company', 'admin']);
    $table->boolean('is_active')->default(true);
    $table->rememberToken();
    $table->timestamps();
});
```

---

## 1.3 Créer les migrations

```bash
php artisan make:migration create_developer_profiles_table
php artisan make:migration create_company_profiles_table
php artisan make:migration create_skills_table
php artisan make:migration create_developer_skill_table
php artisan make:migration create_job_listings_table
php artisan make:migration create_job_skill_table
php artisan make:migration create_applications_table
php artisan make:migration create_saved_jobs_table
```

---

## 1.4 Contenu de chaque migration

### developer_profiles

```php
Schema::create('developer_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->text('bio')->nullable();
    $table->string('location', 100)->nullable();
    $table->string('github_url')->nullable();
    $table->string('portfolio_url')->nullable();
    $table->string('cv_path')->nullable();
    $table->unsignedTinyInteger('years_experience')->default(0);
    $table->timestamps();
});
```

### company_profiles

```php
Schema::create('company_profiles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->text('description')->nullable();
    $table->string('industry', 100)->nullable();
    $table->string('website')->nullable();
    $table->string('logo_path')->nullable();
    $table->string('country', 100)->nullable();
    $table->enum('size', ['startup', 'pme', 'grande_entreprise'])->nullable();
    $table->timestamps();
});
```

### skills

```php
Schema::create('skills', function (Blueprint $table) {
    $table->id();
    $table->string('name', 80)->unique();
    $table->timestamps();
});
```

### developer_skill (table pivot)

```php
Schema::create('developer_skill', function (Blueprint $table) {
    $table->foreignId('developer_profile_id')->constrained()->cascadeOnDelete();
    $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
    $table->enum('level', ['junior', 'intermediaire', 'senior'])->default('junior');
    $table->unique(['developer_profile_id', 'skill_id']);
});
```

### job_listings

```php
Schema::create('job_listings', function (Blueprint $table) {
    $table->id();
    $table->foreignId('company_id')->constrained('users')->cascadeOnDelete();
    $table->string('title', 150);
    $table->text('description');
    $table->string('location', 100)->nullable();
    $table->enum('type', ['full_time', 'part_time', 'freelance', 'stage']);
    $table->enum('remote', ['on_site', 'remote', 'hybrid'])->default('on_site');
    $table->unsignedInteger('salary_min')->nullable();
    $table->unsignedInteger('salary_max')->nullable();
    $table->enum('experience_level', ['junior', 'intermediaire', 'senior']);
    $table->enum('status', ['draft', 'published'])->default('draft');
    $table->timestamp('expires_at')->nullable();
    $table->timestamps();
});
```

### job_skill (table pivot)

```php
Schema::create('job_skill', function (Blueprint $table) {
    $table->foreignId('job_listing_id')->constrained()->cascadeOnDelete();
    $table->foreignId('skill_id')->constrained()->cascadeOnDelete();
    $table->boolean('required')->default(true);
    $table->unique(['job_listing_id', 'skill_id']);
});
```

### applications

```php
Schema::create('applications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('developer_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('job_id')->constrained('job_listings')->cascadeOnDelete();
    $table->text('cover_letter')->nullable();
    $table->enum('status', ['pending', 'viewed', 'accepted', 'rejected'])
          ->default('pending');
    $table->timestamps();
    $table->unique(['developer_id', 'job_id']);
});
```

### saved_jobs

```php
Schema::create('saved_jobs', function (Blueprint $table) {
    $table->foreignId('developer_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('job_id')->constrained('job_listings')->cascadeOnDelete();
    $table->timestamp('created_at')->useCurrent();
    $table->unique(['developer_id', 'job_id']);
});
```

---

## 1.5 Exécuter les migrations

```bash
php artisan migrate
```

Si une erreur FK survient, vérifie que l'ordre des migrations (le timestamp
dans le nom de fichier) respecte bien l'ordre de création ci-dessus.

Pour tout recommencer proprement :

```bash
php artisan migrate:fresh
```

---

---

# ÉTAPE 2 — Seeders

## 2.1 Créer le SkillSeeder

```bash
php artisan make:seeder SkillSeeder
```

Dans `database/seeders/SkillSeeder.php` :

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SkillSeeder extends Seeder
{
    public function run(): void
    {
        $skills = [
            'PHP', 'Laravel', 'JavaScript', 'TypeScript', 'Vue.js',
            'React', 'Node.js', 'Python', 'Django', 'Docker',
            'MySQL', 'PostgreSQL', 'MongoDB', 'Redis', 'Git',
            'Linux', 'REST API', 'GraphQL', 'Flutter', 'Dart',
        ];

        foreach ($skills as $skill) {
            DB::table('skills')->insertOrIgnore(['name' => $skill]);
        }
    }
}
```

Dans `database/seeders/DatabaseSeeder.php` :

```php
public function run(): void
{
    $this->call([
        SkillSeeder::class,
    ]);
}
```

Exécute :

```bash
php artisan db:seed
```

---

---

# ÉTAPE 3 — Models

```bash
php artisan make:model User                  # existe déjà, à modifier
php artisan make:model DeveloperProfile
php artisan make:model CompanyProfile
php artisan make:model Skill
php artisan make:model JobListing
php artisan make:model Application
```

---

## 3.1 User

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'is_active',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active'         => 'boolean',
        'password'          => 'hashed',
    ];

    // Relations
    public function developerProfile(): HasOne
    {
        return $this->hasOne(DeveloperProfile::class);
    }

    public function companyProfile(): HasOne
    {
        return $this->hasOne(CompanyProfile::class);
    }

    public function jobListings(): HasMany
    {
        return $this->hasMany(JobListing::class, 'company_id');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'developer_id');
    }

    public function savedJobs(): BelongsToMany
    {
        return $this->belongsToMany(JobListing::class, 'saved_jobs', 'developer_id', 'job_id')
                    ->withTimestamps();
    }

    // Helpers
    public function isDeveloper(): bool { return $this->role === 'developer'; }
    public function isCompany(): bool   { return $this->role === 'company'; }
    public function isAdmin(): bool     { return $this->role === 'admin'; }
}
```

---

## 3.2 DeveloperProfile

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class DeveloperProfile extends Model
{
    protected $fillable = [
        'user_id', 'bio', 'location', 'github_url',
        'portfolio_url', 'cv_path', 'years_experience',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'developer_skill')
                    ->withPivot('level');
    }
}
```

---

## 3.3 CompanyProfile

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyProfile extends Model
{
    protected $fillable = [
        'user_id', 'description', 'industry',
        'website', 'logo_path', 'country', 'size',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

---

## 3.4 Skill

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Skill extends Model
{
    protected $fillable = ['name'];

    public function developers(): BelongsToMany
    {
        return $this->belongsToMany(DeveloperProfile::class, 'developer_skill')
                    ->withPivot('level');
    }

    public function jobListings(): BelongsToMany
    {
        return $this->belongsToMany(JobListing::class, 'job_skill')
                    ->withPivot('required');
    }
}
```

---

## 3.5 JobListing

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class JobListing extends Model
{
    protected $fillable = [
        'company_id', 'title', 'description', 'location',
        'type', 'remote', 'salary_min', 'salary_max',
        'experience_level', 'status', 'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'salary_min' => 'integer',
        'salary_max' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(User::class, 'company_id');
    }

    public function skills(): BelongsToMany
    {
        return $this->belongsToMany(Skill::class, 'job_skill')
                    ->withPivot('required');
    }

    public function applications(): HasMany
    {
        return $this->hasMany(Application::class, 'job_id');
    }

    public function savedByDevelopers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'saved_jobs', 'job_id', 'developer_id')
                    ->withTimestamps();
    }

    // Scopes
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published');
    }

    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
}
```

---

## 3.6 Application

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Application extends Model
{
    protected $fillable = [
        'developer_id', 'job_id', 'cover_letter', 'status',
    ];

    public function developer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'developer_id');
    }

    public function job(): BelongsTo
    {
        return $this->belongsTo(JobListing::class, 'job_id');
    }
}
```

---

---

# ÉTAPE 4 — Authentification (Sanctum)

## 4.1 Créer le AuthController

```bash
php artisan make:controller AuthController
```

```php
<?php

namespace App\Http\Controllers;

use App\Models\DeveloperProfile;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|unique:users',
            'password'              => 'required|string|min:8|confirmed',
            'role'                  => 'required|in:developer,company',
        ]);

        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => $data['password'], // hashé automatiquement via $casts
            'role'     => $data['role'],
        ]);

        // Créer le profil correspondant au rôle
        if ($user->isDeveloper()) {
            DeveloperProfile::create(['user_id' => $user->id]);
        } else {
            CompanyProfile::create(['user_id' => $user->id]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Les identifiants sont incorrects.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Déconnecté avec succès.']);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user());
    }
}
```

---

## 4.2 Middlewares de rôle

```bash
php artisan make:middleware EnsureRole
php artisan make:middleware EnsureActive
```

### EnsureRole

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!in_array($request->user()->role, $roles)) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        return $next($request);
    }
}
```

### EnsureActive

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user()->is_active) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        return $next($request);
    }
}
```

### Enregistrer les middlewares dans bootstrap/app.php

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->alias([
        'role'   => \App\Http\Middleware\EnsureRole::class,
        'active' => \App\Http\Middleware\EnsureActive::class,
    ]);
})
```

---

## 4.3 Routes d'authentification

Dans `routes/api.php` :

```php
<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Auth
Route::prefix('v1')->group(function () {

    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);

        Route::middleware(['auth:sanctum', 'active'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
        });
    });

});
```

**Test dans Postman :**

```
POST http://localhost:8000/api/v1/auth/register
Content-Type: application/json

{
    "name": "Karmel Dev",
    "email": "karmel@dev.bj",
    "password": "password123",
    "password_confirmation": "password123",
    "role": "developer"
}
```

---

---

# ÉTAPE 5 — Endpoints publics

## 5.1 Controllers

```bash
php artisan make:controller JobController
php artisan make:controller CompanyController
```

### JobController

```php
<?php

namespace App\Http\Controllers;

use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobs = JobListing::published()
            ->notExpired()
            ->with('company.companyProfile', 'skills')
            ->when($request->type, fn($q) => $q->where('type', $request->type))
            ->when($request->remote, fn($q) => $q->where('remote', $request->remote))
            ->when($request->experience_level, fn($q) => $q->where('experience_level', $request->experience_level))
            ->when($request->location, fn($q) => $q->where('location', 'like', "%{$request->location}%"))
            ->when($request->skill, fn($q) => $q->whereHas('skills', fn($s) => $s->where('name', $request->skill)))
            ->paginate(15);

        return response()->json($jobs);
    }

    public function show(JobListing $job): JsonResponse
    {
        if ($job->status !== 'published') {
            return response()->json(['message' => 'Offre introuvable.'], 404);
        }

        $job->load('company.companyProfile', 'skills');

        return response()->json($job);
    }
}
```

### CompanyController

```php
<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller
{
    public function index(): JsonResponse
    {
        $companies = User::where('role', 'company')
            ->where('is_active', true)
            ->with('companyProfile')
            ->get();

        return response()->json($companies);
    }

    public function show(User $company): JsonResponse
    {
        if ($company->role !== 'company') {
            return response()->json(['message' => 'Entreprise introuvable.'], 404);
        }

        $company->load('companyProfile', 'jobListings' => fn($q) => $q->published()->notExpired());

        return response()->json($company);
    }
}
```

## 5.2 Routes

```php
// Routes publiques
Route::get('jobs',                [JobController::class, 'index']);
Route::get('jobs/{job}',          [JobController::class, 'show']);
Route::get('companies',           [CompanyController::class, 'index']);
Route::get('companies/{company}', [CompanyController::class, 'show']);
```

---

---

# ÉTAPE 6 — Espace Entreprise

```bash
php artisan make:controller Company/JobController
php artisan make:controller Company/ApplicationController
```

## 6.1 Company/JobController

```php
<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $jobs = $request->user()->jobListings()->with('skills')->paginate(15);
        return response()->json($jobs);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'            => 'required|string|max:150',
            'description'      => 'required|string',
            'location'         => 'nullable|string|max:100',
            'type'             => 'required|in:full_time,part_time,freelance,stage',
            'remote'           => 'required|in:on_site,remote,hybrid',
            'salary_min'       => 'nullable|integer|min:0',
            'salary_max'       => 'nullable|integer|min:0|gte:salary_min',
            'experience_level' => 'required|in:junior,intermediaire,senior',
            'expires_at'       => 'nullable|date|after:today',
            'skills'           => 'nullable|array',
            'skills.*.id'      => 'required|exists:skills,id',
            'skills.*.required'=> 'nullable|boolean',
        ]);

        $job = $request->user()->jobListings()->create($data);

        if (!empty($data['skills'])) {
            $skillsSync = collect($data['skills'])->mapWithKeys(fn($s) => [
                $s['id'] => ['required' => $s['required'] ?? true]
            ])->all();
            $job->skills()->sync($skillsSync);
        }

        return response()->json($job->load('skills'), 201);
    }

    public function show(Request $request, JobListing $job): JsonResponse
    {
        $this->authorizeJob($request, $job);
        return response()->json($job->load('skills', 'applications'));
    }

    public function update(Request $request, JobListing $job): JsonResponse
    {
        $this->authorizeJob($request, $job);

        $data = $request->validate([
            'title'            => 'sometimes|string|max:150',
            'description'      => 'sometimes|string',
            'location'         => 'nullable|string|max:100',
            'type'             => 'sometimes|in:full_time,part_time,freelance,stage',
            'remote'           => 'sometimes|in:on_site,remote,hybrid',
            'salary_min'       => 'nullable|integer|min:0',
            'salary_max'       => 'nullable|integer|min:0|gte:salary_min',
            'experience_level' => 'sometimes|in:junior,intermediaire,senior',
            'expires_at'       => 'nullable|date|after:today',
        ]);

        $job->update($data);
        return response()->json($job->load('skills'));
    }

    public function publish(Request $request, JobListing $job): JsonResponse
    {
        $this->authorizeJob($request, $job);
        $job->update(['status' => 'published']);
        return response()->json(['message' => 'Offre publiée.', 'job' => $job]);
    }

    public function unpublish(Request $request, JobListing $job): JsonResponse
    {
        $this->authorizeJob($request, $job);
        $job->update(['status' => 'draft']);
        return response()->json(['message' => 'Offre dépubliée.', 'job' => $job]);
    }

    public function destroy(Request $request, JobListing $job): JsonResponse
    {
        $this->authorizeJob($request, $job);
        $job->delete();
        return response()->json(['message' => 'Offre supprimée.']);
    }

    // Vérifie que l'offre appartient bien à l'entreprise connectée
    private function authorizeJob(Request $request, JobListing $job): void
    {
        if ($job->company_id !== $request->user()->id) {
            abort(403, 'Accès refusé.');
        }
    }
}
```

## 6.2 Company/ApplicationController

```php
<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request, JobListing $job): JsonResponse
    {
        if ($job->company_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $applications = $job->applications()->with('developer')->paginate(20);
        return response()->json($applications);
    }

    public function updateStatus(Request $request, Application $application): JsonResponse
    {
        // Vérifie que l'application appartient à une offre de cette entreprise
        if ($application->job->company_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        $data = $request->validate([
            'status' => 'required|in:viewed,accepted,rejected',
        ]);

        $application->update($data);
        return response()->json($application);
    }
}
```

## 6.3 Routes entreprise

```php
Route::middleware(['auth:sanctum', 'active', 'role:company'])->prefix('company')->group(function () {
    // Offres
    Route::get('jobs',                        [Company\JobController::class, 'index']);
    Route::post('jobs',                       [Company\JobController::class, 'store']);
    Route::get('jobs/{job}',                  [Company\JobController::class, 'show']);
    Route::put('jobs/{job}',                  [Company\JobController::class, 'update']);
    Route::patch('jobs/{job}/publish',        [Company\JobController::class, 'publish']);
    Route::patch('jobs/{job}/unpublish',      [Company\JobController::class, 'unpublish']);
    Route::delete('jobs/{job}',               [Company\JobController::class, 'destroy']);

    // Candidatures reçues
    Route::get('jobs/{job}/applications',           [Company\ApplicationController::class, 'index']);
    Route::patch('applications/{application}/status',[Company\ApplicationController::class, 'updateStatus']);
});
```

---

---

# ÉTAPE 7 — Espace Développeur

```bash
php artisan make:controller Developer/ProfileController
php artisan make:controller Developer/ApplicationController
php artisan make:controller Developer/SavedJobController
```

## 7.1 Developer/ProfileController

```php
<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $profile = $request->user()->developerProfile()->with('skills')->first();
        return response()->json($profile);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'bio'              => 'nullable|string',
            'location'         => 'nullable|string|max:100',
            'github_url'       => 'nullable|url',
            'portfolio_url'    => 'nullable|url',
            'years_experience' => 'nullable|integer|min:0|max:50',
        ]);

        $profile = $request->user()->developerProfile;
        $profile->update($data);

        return response()->json($profile);
    }

    public function addSkills(Request $request): JsonResponse
    {
        $data = $request->validate([
            'skills'         => 'required|array',
            'skills.*.id'    => 'required|exists:skills,id',
            'skills.*.level' => 'nullable|in:junior,intermediaire,senior',
        ]);

        $profile = $request->user()->developerProfile;

        $skillsSync = collect($data['skills'])->mapWithKeys(fn($s) => [
            $s['id'] => ['level' => $s['level'] ?? 'junior']
        ])->all();

        $profile->skills()->syncWithoutDetaching($skillsSync);

        return response()->json($profile->load('skills'));
    }

    public function removeSkill(Request $request, int $skillId): JsonResponse
    {
        $request->user()->developerProfile->skills()->detach($skillId);
        return response()->json(['message' => 'Compétence retirée.']);
    }
}
```

## 7.2 Developer/ApplicationController

```php
<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $applications = $request->user()
            ->applications()
            ->with('job.company')
            ->paginate(15);

        return response()->json($applications);
    }

    public function store(Request $request, JobListing $job): JsonResponse
    {
        // Vérifications métier
        if ($job->status !== 'published') {
            return response()->json(['message' => 'Cette offre n\'est pas disponible.'], 422);
        }

        if ($job->expires_at && $job->expires_at->isPast()) {
            return response()->json(['message' => 'Cette offre a expiré.'], 422);
        }

        $alreadyApplied = Application::where('developer_id', $request->user()->id)
            ->where('job_id', $job->id)
            ->exists();

        if ($alreadyApplied) {
            return response()->json(['message' => 'Vous avez déjà postulé à cette offre.'], 422);
        }

        $data = $request->validate([
            'cover_letter' => 'nullable|string|max:3000',
        ]);

        $application = Application::create([
            'developer_id' => $request->user()->id,
            'job_id'       => $job->id,
            'cover_letter' => $data['cover_letter'] ?? null,
        ]);

        return response()->json($application->load('job'), 201);
    }

    public function destroy(Request $request, Application $application): JsonResponse
    {
        if ($application->developer_id !== $request->user()->id) {
            return response()->json(['message' => 'Accès refusé.'], 403);
        }

        if ($application->status !== 'pending') {
            return response()->json(['message' => 'Impossible de retirer une candidature déjà traitée.'], 422);
        }

        $application->delete();
        return response()->json(['message' => 'Candidature retirée.']);
    }
}
```

## 7.3 Developer/SavedJobController

```php
<?php

namespace App\Http\Controllers\Developer;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SavedJobController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $saved = $request->user()->savedJobs()->with('company.companyProfile')->paginate(15);
        return response()->json($saved);
    }

    public function store(Request $request, JobListing $job): JsonResponse
    {
        $request->user()->savedJobs()->syncWithoutDetaching([$job->id]);
        return response()->json(['message' => 'Offre sauvegardée.']);
    }

    public function destroy(Request $request, JobListing $job): JsonResponse
    {
        $request->user()->savedJobs()->detach($job->id);
        return response()->json(['message' => 'Offre retirée des favoris.']);
    }
}
```

## 7.4 Routes développeur

```php
Route::middleware(['auth:sanctum', 'active', 'role:developer'])->prefix('developer')->group(function () {
    // Profil
    Route::get('profile',                          [Developer\ProfileController::class, 'show']);
    Route::put('profile',                          [Developer\ProfileController::class, 'update']);
    Route::post('profile/skills',                  [Developer\ProfileController::class, 'addSkills']);
    Route::delete('profile/skills/{skill}',        [Developer\ProfileController::class, 'removeSkill']);

    // Candidatures
    Route::get('applications',                     [Developer\ApplicationController::class, 'index']);
    Route::post('jobs/{job}/apply',                [Developer\ApplicationController::class, 'store']);
    Route::delete('applications/{application}',    [Developer\ApplicationController::class, 'destroy']);

    // Favoris
    Route::get('saved-jobs',                       [Developer\SavedJobController::class, 'index']);
    Route::post('jobs/{job}/save',                 [Developer\SavedJobController::class, 'store']);
    Route::delete('jobs/{job}/save',               [Developer\SavedJobController::class, 'destroy']);
});
```

---

---

# ÉTAPE 8 — Espace Admin

```bash
php artisan make:controller Admin/UserController
php artisan make:controller Admin/JobController
```

## 8.1 Admin/UserController

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(User::paginate(20));
    }

    public function toggle(User $user): JsonResponse
    {
        $user->update(['is_active' => !$user->is_active]);
        $status = $user->is_active ? 'activé' : 'désactivé';
        return response()->json(['message' => "Compte {$status}.", 'user' => $user]);
    }

    public function destroy(User $user): JsonResponse
    {
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé.']);
    }
}
```

## 8.2 Admin/JobController

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\JobListing;
use Illuminate\Http\JsonResponse;

class JobController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(JobListing::with('company')->paginate(20));
    }

    public function destroy(JobListing $job): JsonResponse
    {
        $job->delete();
        return response()->json(['message' => 'Offre supprimée.']);
    }
}
```

## 8.3 Routes admin

```php
Route::middleware(['auth:sanctum', 'active', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('users',                  [Admin\UserController::class, 'index']);
    Route::patch('users/{user}/toggle',  [Admin\UserController::class, 'toggle']);
    Route::delete('users/{user}',        [Admin\UserController::class, 'destroy']);

    Route::get('jobs',                   [Admin\JobController::class, 'index']);
    Route::delete('jobs/{job}',          [Admin\JobController::class, 'destroy']);
});
```

---

---

# ÉTAPE 9 — routes/api.php complet

Voici le fichier `routes/api.php` final avec tous les imports :

```php
<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\JobController;
use App\Http\Controllers\Admin;
use App\Http\Controllers\Company;
use App\Http\Controllers\Developer;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ── Auth ───────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);

        Route::middleware(['auth:sanctum', 'active'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me',      [AuthController::class, 'me']);
        });
    });

    // ── Public ─────────────────────────────────────────────────────────────
    Route::get('jobs',                [JobController::class, 'index']);
    Route::get('jobs/{job}',          [JobController::class, 'show']);
    Route::get('companies',           [CompanyController::class, 'index']);
    Route::get('companies/{company}', [CompanyController::class, 'show']);

    // ── Entreprise ─────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'active', 'role:company'])->prefix('company')->group(function () {
        Route::get('jobs',                         [Company\JobController::class, 'index']);
        Route::post('jobs',                        [Company\JobController::class, 'store']);
        Route::get('jobs/{job}',                   [Company\JobController::class, 'show']);
        Route::put('jobs/{job}',                   [Company\JobController::class, 'update']);
        Route::patch('jobs/{job}/publish',         [Company\JobController::class, 'publish']);
        Route::patch('jobs/{job}/unpublish',       [Company\JobController::class, 'unpublish']);
        Route::delete('jobs/{job}',                [Company\JobController::class, 'destroy']);
        Route::get('jobs/{job}/applications',      [Company\ApplicationController::class, 'index']);
        Route::patch('applications/{application}/status', [Company\ApplicationController::class, 'updateStatus']);
    });

    // ── Développeur ────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'active', 'role:developer'])->prefix('developer')->group(function () {
        Route::get('profile',                      [Developer\ProfileController::class, 'show']);
        Route::put('profile',                      [Developer\ProfileController::class, 'update']);
        Route::post('profile/skills',              [Developer\ProfileController::class, 'addSkills']);
        Route::delete('profile/skills/{skill}',    [Developer\ProfileController::class, 'removeSkill']);
        Route::get('applications',                 [Developer\ApplicationController::class, 'index']);
        Route::post('jobs/{job}/apply',            [Developer\ApplicationController::class, 'store']);
        Route::delete('applications/{application}',[Developer\ApplicationController::class, 'destroy']);
        Route::get('saved-jobs',                   [Developer\SavedJobController::class, 'index']);
        Route::post('jobs/{job}/save',             [Developer\SavedJobController::class, 'store']);
        Route::delete('jobs/{job}/save',           [Developer\SavedJobController::class, 'destroy']);
    });

    // ── Admin ──────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'active', 'role:admin'])->prefix('admin')->group(function () {
        Route::get('users',                  [Admin\UserController::class, 'index']);
        Route::patch('users/{user}/toggle',  [Admin\UserController::class, 'toggle']);
        Route::delete('users/{user}',        [Admin\UserController::class, 'destroy']);
        Route::get('jobs',                   [Admin\JobController::class, 'index']);
        Route::delete('jobs/{job}',          [Admin\JobController::class, 'destroy']);
    });

});
```

---

---

# TESTS POSTMAN — Séquence complète

```
1. POST /api/v1/auth/register          → créer un compte developer
2. POST /api/v1/auth/register          → créer un compte company
3. POST /api/v1/auth/login             → login company → copier le token
4. POST /api/v1/company/jobs           → créer une offre (status: draft)
5. PATCH /api/v1/company/jobs/{id}/publish → publier l'offre
6. GET  /api/v1/jobs                   → vérifier que l'offre apparaît
7. POST /api/v1/auth/login             → login developer → copier le token
8. POST /api/v1/developer/jobs/{id}/apply  → postuler
9. GET  /api/v1/developer/applications → voir ses candidatures
10. POST /api/v1/auth/login            → login company
11. GET  /api/v1/company/jobs/{id}/applications → voir les candidatures reçues
12. PATCH /api/v1/company/applications/{id}/status → changer en "viewed"
```

**Header à inclure sur toutes les routes protégées :**
```
Authorization: Bearer {ton_token}
Accept: application/json
```

---

---

# ERREURS COURANTES

| Erreur | Cause probable | Solution |
|--------|---------------|----------|
| `Route not found` | Préfixe `/api/v1` oublié | Vérifie l'URL complète |
| `Unauthenticated` | Token manquant ou expiré | Ajoute le header Authorization |
| `403 Accès refusé` | Mauvais rôle pour la route | Connecte-toi avec le bon compte |
| `SQLSTATE FK constraint` | Ordre des migrations incorrect | `php artisan migrate:fresh` dans le bon ordre |
| `Mass assignment exception` | Colonne absente de $fillable | Ajoute la colonne dans $fillable |
| `Class not found` | Namespace incorrect | Vérifie namespace = chemin du fichier |
| `Call to undefined method` | Relation non déclarée dans le model | Ajoute la méthode de relation |
```
