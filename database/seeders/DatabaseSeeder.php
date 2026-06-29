<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleAndPermissionSeeder::class,
            SystemSettingSeeder::class,
        ]);

        // Seed admin user ตั้งต้น — ต้องมีไว้ login ครั้งแรก (มี role admin → มี permission access admin)
        // หลังจากนี้ admin สร้าง/จัดการ user อื่นผ่านหน้า User Management (ADR-0002)
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);
        $admin->assignRole('admin');
    }
}
