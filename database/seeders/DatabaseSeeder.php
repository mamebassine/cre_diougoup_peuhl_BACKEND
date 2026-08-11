<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

// ==========================
// ADMIN
// ==========================

User::create([

    'nom' => 'Niang',

    'prenom' => 'Bassine',

    'email' => 'bassinen13@gmail.com',

    'telephone' => '771065156',

    'password' => Hash::make('password123'),

    'role' => 'admin',

    'photo' => 'users/bassine.png',

    'is_active' => true,

    'email_verified_at' => now(),

    'created_at' => now(),

    'updated_at' => now(),

]);





// ==========================
// GESTIONNAIRE
// ==========================

User::create([

    'nom' => 'FALL',

    'prenom' => 'Omar',

    'email' => 'mamebassine06@gmail.com',

    'telephone' => '779785151',

    'password' => Hash::make('password123'),

    'role' => 'gestionnaire',

    'photo' => 'users/omar.jpeg',

    'is_active' => true,

    'email_verified_at' => now(),

    'created_at' => now(),

    'updated_at' => now(),

]);

    }
}