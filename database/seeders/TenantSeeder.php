<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('tenants')->insert([
            [
                'name' => 'General Users',
                'email' => 'gus3@bnsg.com',
                'phone' => '1234567890',
                'category' => 'Citizen',
                'code' => 'citezen',
                'address' => 'Citizens.',
                'status' => 'Active',
            ],
            [
                'name' => 'Office of the Vice Chancellor',
                'email' => 'hos@bnsg.com',
                'phone' => '08034522453',
                'category' => 'Ministry',
                'code' => 'VC',
                'address' => 'Responsible for UAST.',
                'status' => 'Active',
            ],
             [
                'name' => 'Office of the Registrar.',
                'email' => 'me11@bnsg.com',
                'phone' => '08034522453',
                'category' => 'Agency',
                'code' => 'Registry',
                'address' => 'Responsible for UAST Registry.',
                'status' => 'Active',
            ],
            [
                'name' => 'Office of The DVC-Academic',
                'email' => 'mes@bnsg.com',
                'phone' => '08034522453',
                'category' => 'Ministry',
                'code' => 'DVC-Academic',
                'address' => 'Responsible for Academics',
                'status' => 'Active',
            ],
            [
                'name' => 'Office of the DVC-Administration',
                'email' => 'mxe@bnsg.com',
                'phone' => '08034522453',
                'category' => 'Ministry',
                'code' => 'DVC-Administration',
                'address' => 'Responsible for Administration.',
                'status' => 'Active',
            ],
           
            [
                'name' => 'Office of the Bursar.',
                'email' => 'bsbs@bnsg.com',
                'phone' => '08034522453',
                'category' => 'Agency',
                'code' => 'Bursary',
                'address' => 'Responsible for UAST Bursary.',
                'status' => 'Active',
            ],
             [
                'name' => 'Student Affairs Division',
                'email' => 'mec@bnsg.com',
                'phone' => '08034522453',
                'category' => 'Ministry',
                'code' => 'UAST',
                'address' => 'Responsible for Student Affairs.',
                'status' => 'Active',
            ],
            [
                'name' => 'Directorate of Academic Planning',
                'email' => 'me@bnsg.com',
                'phone' => '08034522453',
                'category' => 'Ministry',
                'code' => 'DAP',
                'address' => 'Responsible for Academic Planning.',
                'status' => 'Active',
            ],
            [
                'name' => 'Procurement Unit',
                'email' => 'meq@bnsg.com',
                'phone' => '08034522453',
                'category' => 'Ministry',
                'code' => 'Procurement',
                'address' => 'Responsible for Procurement.',
                'status' => 'Active',
            ],
        ]);
    }
}
