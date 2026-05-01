<?php

namespace App\Controllers\Api;

use App\Models\UserModel;
use CodeIgniter\RESTful\ResourceController;
use Firebase\JWT\JWT;

class Auth extends ResourceController
{
    public function login()
    {
        // 1. Ambil input dari Postman/Express
        $username = $this->request->getVar('username');
        $password = $this->request->getVar('password');

        $userModel = new UserModel();
        
        // 2. Cari user berdasarkan username
        $user = $userModel->where('username', $username)->first();

        if (!$user) {
            return $this->failNotFound('Username tidak ditemukan');
        }

        // 3. Cek Password (menggunakan password_hash)
        if (!password_verify($password, $user['password'])) {
            return $this->fail('Password salah');
        }

        // 4. Ambil Permissions User (Ini bagian krusial buat RBAC)
        $db = \Config\Database::connect();
        $permissions = $db->table('role_permissions')
            ->select('permissions.kode')
            ->join('permissions', 'permissions.id = role_permissions.permission_id')
            ->join('user_roles', 'user_roles.role_id = role_permissions.role_id') // Pastikan lo ada tabel user_roles
            ->where('user_roles.user_id', $user['id'])
            ->get()->getResultArray();

        $permArray = array_column($permissions, 'kode');

        // 5. Generate JWT[cite: 1]
        $key = getenv('JWT_SECRET');
        $payload = [
            'iat'  => time(),           // Waktu dibuat[cite: 1]
            'exp'  => time() + 7200,    // Kadaluarsa dalam 2 jam[cite: 1]
            'uid'  => $user['id'],      // User ID[cite: 1]
            'name' => $user['nama'],
            'perms'=> $permArray        // List hak akses[cite: 1]
        ];

        $token = JWT::encode($payload, $key, 'HS256');

        // 6. Kirim respon ke Frontend[cite: 1]
        return $this->respond([
            'status'  => 200,
            'message' => 'Login Berhasil',
            'token'   => $token,
            'user'    => [
                'nama'  => $user['nama'],
                'perms' => $permArray
            ]
        ]);
    }
}