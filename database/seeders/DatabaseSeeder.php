<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'info@arsus.nl'],
            [
                'name' => 'Arsus',
                'password' => Hash::make('arsus@29'),
                'email_verified_at' => now(),
            ]
        );
    }
}
