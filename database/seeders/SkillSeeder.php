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
