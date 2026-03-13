<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name'          => 'Administrator Baru',
            'username'      => 'admin',
            'email'         => 'admin@gmail.com',
            'password'      => 'admin123',
            'block'         => 0,
            'registerdate'  => now(),
            'lastvisitdate' => now(),
            'activation'    => 'activated',
            'group_id'       => 1
        ]);
    }
}