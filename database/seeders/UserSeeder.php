<?php

namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superadmin = User::create([
            'name' => 'John Agi',
            'email' => 'paulben.ajene@gmail.com',
            'password' => bcrypt('BdicDev2025'),
            'status' => 'active',
            'default_role' => 'superadmin',
            'email_verified_at' => Carbon::now()
        ]);
        $superadmin->assignRole('superadmin');
        

        // Create an Admin User
        $admin = User::create([
            'name' => 'Prof. Qrisstuberg Amua',
            'email' => 'testuser1@bdic.ng',
            'password' => bcrypt('BdicDev2025'),
            'default_role' => 'Admin',
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);
        $admin->assignRole('Admin');


        // Create an Staff User
        $admin = User::create([
            'name' => 'Dr. J. Echor',
            'email' => 'testuser2@bdic.ng',
            'password' => bcrypt('BdicDev2025'),
            'default_role' => 'Admin',
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);
        $admin->assignRole('Admin');

        // Create a Regular User
        $user = User::create([
            'name' => 'Terver Adam',
            'email' => 'testuser3@bdic.ng',
            'password' => bcrypt('BdicDev2025'),
            'default_role' => 'Admin',
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);
        $user->assignRole('Admin');

        // Create an Admin User
        $admin = User::create([
            'name' => 'Ephraim Tarfa',
            'email' => 'testuser4@bdic.ng',
            'password' => bcrypt('BdicDev2025'),
            'default_role' => 'Admin',
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);
        $admin->assignRole('Admin');

         // Create an Admin User
         $admin = User::create([
            'name' => 'John Tar',
            'email' => 'testuser5@bdic.ng',
            'password' => bcrypt('BdicDev2025'),
            'default_role' => 'Admin',
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);
        $admin->assignRole('Admin');

        // Create an Admin User
        $admin = User::create([
            'name' => 'Terwase John',
            'email' => 'testuser6@bdic.ng',
            'password' => bcrypt('BdicDev2025'),
            'default_role' => 'Admin',
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);
        $admin->assignRole('Admin');

         // Create an Admin User
         $admin = User::create([
            'name' => 'Solomon Tange',
            'email' => 'testuser7@bdic.ng',
            'password' => bcrypt('BdicDev2025'),
            'default_role' => 'Admin',
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);
        $admin->assignRole('Admin');

         // Create an Admin User
         $admin = User::create([
            'name' => 'Shishi Tange',
            'email' => 'testuser8@bdic.ng',
            'password' => bcrypt('BdicDev2025'),
            'default_role' => 'Admin',
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);
        $admin->assignRole('Admin');

           // Create an Admin User
           $admin = User::create([
            'name' => 'Shishi Tarnge',
            'email' => 'testuser9@bdic.ng',
            'password' => bcrypt('BdicDev2025'),
            'default_role' => 'Admin',
            'status' => 'active',
            'email_verified_at' => Carbon::now()
        ]);
        $admin->assignRole('Admin');

    }
}
