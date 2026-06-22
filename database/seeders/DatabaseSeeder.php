<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name'  => 'Test User',
            'email' => 'test@example.com',
        ]);

        // السياسة الافتراضية (v1) لازمة لإنشاء أي تقييم.
        $this->call(ScoringPolicySeeder::class);
    }
}
