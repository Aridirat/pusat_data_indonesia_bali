<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Group;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Group::create([
            'group_id' => 1,
            'title' => 'Administrator'
        ]);

        Group::create([
            'group_id' => 2,
            'title' => 'Pengelola'
        ]);

        Group::create([
            'group_id' => 3,
            'title' => 'Customer'
        ]);
    }
}