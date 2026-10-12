<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => config('portfolio.admin.email')],
            [
                'name' => config('portfolio.admin.name'),
                'password' => config('portfolio.admin.password'),
                'email_verified_at' => now(),
            ],
        );

        $this->call(DemoProjectSeeder::class);
    }
}
