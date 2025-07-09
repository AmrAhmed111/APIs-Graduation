<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! User::where('email', 'ahmed004@gmail.com')->exists()) {
            User::create([
                'name' => 'ahmed',
                'email' => 'ahmed004@gmail.com',
                'password' => Hash::make('Ahmed$&123456'),
                'role' => 'owner',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (! User::where('email', 'ali004@gmail.com')->exists()) {
            User::create([
                'name' => 'ali',
                'email' => 'ali004@gmail.com',
                'password' => Hash::make('Ali$&123456'),
                'role' => 'moderator',
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
