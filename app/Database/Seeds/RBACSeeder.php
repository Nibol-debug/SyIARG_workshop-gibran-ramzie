<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class RBACSeeder extends Seeder
{
    public function run()
    {
        $roleModel = new \App\Models\RoleModel();
        $permModel = new \App\Models\PermissionModel();

        // 1. masukin data role
        $roles = [
            ['nama_role' => 'super admin', 'deskripsi' => 'Akes penuh ke semua fitur'],
            ['nama_role' => 'Guru', 'deskripsi' => 'Mengelola nilai siswa'],
        ];
        $this->db->table('roles')->insertBatch($roles);

        // 2. masukin data permission
        $perms = [
            ['kode' => 'penilaian.view', 'nama' => 'Melihat nilai'],
            ['kode' => 'penilaian.create', 'nama' => 'Membuat nilai'],
            ['kode' => 'user.manage', 'nama' => 'mengelola user'],

        ];
        $this->db->table('permissions')->insertBatch($perms);

        // 3. mapping role dengan permission
        // ID role super admin = 1, ID role guru = 2
        $mapping = [
            ['role_id' => '2', 'permission_id' => '1'],
            ['role_id' => '2', 'permission_id' => '2'],
        ];
        $this->db->table('role_permissions')->insertBatch($mapping);


        $userData = [
            'username' => 'admin_rg',
            'password' => password_hash('rahasia123', PASSWORD_BCRYPT),
            'nama'     => 'Admin Gemilang',
        ];
        $this->db->table('users')->insert($userData);

        // kasih role super admin ke user admin_rg
        $this->db->table('user_roles')->insert([
            'user_id' => 1, 
            'role_id' => 1, 
        ]);
    }
}
