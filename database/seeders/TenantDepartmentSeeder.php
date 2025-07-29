<?php

namespace Database\Seeders;

use App\Models\TenantDepartment;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantDepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = TenantDepartment::create([
            'name' => 'General Users',
            'email' => 'gus33@bngh.ng',
            'phone' => '09087767543',
            'status' => 'active',
            'tenant_id' => 1
        ]);
        $tenant = TenantDepartment::create([
            'name' => 'Office of the Vice Chancellor',
            'email' => 'bmdt@bnsg.com',
            'phone' => '09087767543',
            'status' => 'active',
            'tenant_id' => 2

        ]);
        $tenant = TenantDepartment::create([
            'name' => 'Office of the Registrar',
            'email' => 'hmb@bnsg.com',
            'phone' => '09087767543',
            'status' => 'active',
            'tenant_id' => 3

        ]);
        $tenant = TenantDepartment::create([
            'name' => 'Offoce of the DVC-Academic',
            'email' => 'gh1@bngh.ng',
            'phone' => '09087767543',
            'status' => 'active',
            'tenant_id' => 4
        ]);
        $tenant = TenantDepartment::create([
            'name' => 'Office of the DVC-Administration',
            'email' => 'ba@bnsg.com',
            'phone' => '09087767543',
            'status' => 'active',
            'tenant_id' => 5
        ]);
        $tenant = TenantDepartment::create([
            'name' => 'Office of the Bursar',
            'email' => 'bsw@bnsg.com',
            'phone' => '09087767543',
            'status' => 'active',
            'tenant_id' => 6
        ]);
        $tenant = TenantDepartment::create([
            'name' => 'Student Affairs Division',
            'email' => 'bmdt@bnsg.com',
            'phone' => '09087767543',
            'status' => 'active',
            'tenant_id' => 7

        ]);       
        $tenant = TenantDepartment::create([
            'name' => 'Office of the Director Academic Planning',
            'email' => 'hmb@bnsg.com',
            'phone' => '09087767543',
            'status' => 'active',
            'tenant_id' => 8

        ]);
        $tenant = TenantDepartment::create([
            'name' => 'Office of the Director Procurement',
            'email' => 'md@bdic.ng',
            'phone' => '09087767543',
            'status' => 'active',
            'tenant_id' => 9
        ]);
       
    }
}
