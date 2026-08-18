<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
 
    public function run(): void
    {
        $users = [
            ['name' => 'Jimmy Mbapila', 'email' => 'jimmy@gpitg.com'],
            ['name' => 'James Komba', 'email' => 'james@gpitg.com'],
            ['name' => 'Kenedi Mwiru', 'email' => 'kenedi@gpitg.com'],
        ];

        foreach ($users as $user) {
            User::query()->create([
                ...$user,
                'password' => Hash::make('password'),
            ]);
        }
    }
}
