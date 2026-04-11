<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Default credentials:
     *   Email:    admin@premaxautocare.co.ke
     *   Password: Premax@2024!
     *
     * ⚠️  Change the password immediately after first login.
     */
    public function run(): void
    {
         $now = Carbon::now();
 
        // 1. Create User (auth)
        $userId = DB::table('users')->insertGetId([
            'name'       => 'James Mwangi',
            'email'      => 'admin@premaxautocare.co.ke',
            'password'   => Hash::make('Premax@2024!'),
            'role'       => 'super_admin',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
 
        // 2. Get management department
        $deptId = DB::table('departments')->where('slug', 'management')->value('id');
 
        // 3. Create Employee record
        DB::table('employees')->insert([
            'user_id'          => $userId,
            'department_id'    => $deptId,
            'employee_number'  => 'EMP-001',
            'first_name'       => 'James',
            'last_name'        => 'Mwangi',
            'phone'            => '+254742091794',
            'role'             => 'super_admin',
            'job_title'        => 'Founder & Lead Technician',
            'bio'              => 'With over 15 years of experience in automotive care, James founded Premax Autocare with a vision to deliver world-class service.',
            'employment_type'  => 'full_time',
            'employment_status'=> 'active',
            'start_date'       => '2014-01-01',
            'show_on_website'  => true,
            'sort_order'       => 1,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        if ($roleId = Role::query()->where('slug', 'super_admin')->value('id')) {
            DB::table('role_user')->updateOrInsert([
                'role_id' => $roleId,
                'user_id' => $userId,
            ]);
        }
 
        // 4. Second user — staff
        $staffId = DB::table('users')->insertGetId([
            'name'       => 'Grace Akinyi',
            'email'      => 'grace@premaxautocare.co.ke',
            'password'   => Hash::make('Staff@2024!'),
            'role'       => 'receptionist',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
 
        $workshopDeptId = DB::table('departments')->where('slug', 'workshop')->value('id');
 
        DB::table('employees')->insert([
            'user_id'          => $staffId,
            'department_id'    => $workshopDeptId,
            'employee_number'  => 'EMP-002',
            'first_name'       => 'Grace',
            'last_name'        => 'Akinyi',
            'phone'            => null,
            'role'             => 'staff',
            'job_title'        => 'Head of Detailing',
            'bio'              => 'Grace leads our detailing team with precision and passion.',
            'employment_type'  => 'full_time',
            'employment_status'=> 'active',
            'start_date'       => '2018-03-01',
            'show_on_website'  => true,
            'sort_order'       => 2,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);

        if ($roleId = Role::query()->where('slug', 'receptionist')->value('id')) {
            DB::table('role_user')->updateOrInsert([
                'role_id' => $roleId,
                'user_id' => $staffId,
            ]);
        }
 
        $this->command->info('✓ Admin users + employee records seeded.');
        $this->command->table(
            ['Name', 'Email', 'Role', 'Password'],
            [
                ['James Mwangi', 'admin@premaxautocare.co.ke', 'super_admin', 'Premax@2024!'],
                ['Grace Akinyi', 'grace@premaxautocare.co.ke', 'receptionist','Staff@2024!'],
            ]
        );
    
    }
}
