<?php

namespace Database\Seeders;

use App\Models\ScoringPolicy;
use Illuminate\Database\Seeder;

class ScoringPolicySeeder extends Seeder
{
    public function run(): void
    {
        if (ScoringPolicy::exists()) return;

        ScoringPolicy::create([
            'version'              => 1,
            'is_active'            => true,
            'approval_threshold'   => 1200,
            'rent_bonus'           => 1,
            'marital_bonus'        => 1,
            'per_eligible_person'  => 1,
            'bands'                => [
                ['max' => 500,  'points' => 3],
                ['max' => 1000, 'points' => 2],
                ['max' => null, 'points' => 1],
            ],
            'missing_group_size'   => 3,
            'missing_group_points' => 1,
            'arch_points'          => [0, 1, 2, 3],
            'effective_from'       => now()->toDateString(),
        ]);
    }
}
