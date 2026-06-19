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
        User::create([
            'nom' => 'Admin',
            'prenom' => 'System',
            'email' => 'admin@cre.com',
            'telephone' => '771111111',
            'password' => Hash::make('password123'),
            'role' => 'admin',
            'is_active' => true,
        ]);

        User::create([
            'nom' => 'Gestionnaire',
            'prenom' => 'Centre',
            'email' => 'gestionnaire@cre.com',
            'telephone' => '772222222',
            'password' => Hash::make('password123'),
            'role' => 'gestionnaire',
            'is_active' => true,
        ]);

        User::create([
            'nom' => 'Apprenant',
            'prenom' => 'Test',
            'email' => 'apprenant@cre.com',
            'telephone' => '773333333',
            'password' => Hash::make('password123'),
            'role' => 'apprenant',
            'is_active' => true,
        ]);
    }
}