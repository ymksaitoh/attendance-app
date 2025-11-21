<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name' => 'テスト管理者１',
            'email' => 'admin1@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        for ($i = 3; $i <= 7; $i++) {
            User::create([
                'name' => "テストユーザー{$i}",
                'email' => "test{$i}@example.com",
                'password' => Hash::make('password'),
                'role' => 'user',
                'email_verified_at' => now(),
            ]);
        }
    }
}
