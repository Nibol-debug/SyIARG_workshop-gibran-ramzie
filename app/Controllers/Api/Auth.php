<?php

namespace App\Controllers\Api; // 'A' pada Api harus kapital kalau folder lo namanya 'Api'

use App\Controllers\BaseController;
use App\Models\UserModel;
use Firebase\JWT\JWT;

class Auth extends BaseController
{
    protected $userModel;
    
    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    /**
     * Handle user login
     */
    public function login()
    {
        $rules = [
            'username' => 'required',
            'password' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setStatusCode(400)->setJSON([
                'status'   => 400,
                'message'  => 'Username dan password wajib diisi',
                'errors'   => $this->validator->getErrors(),
            ]);
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');

        // Find user by username or email
        $user = $this->userModel->findByUsernameOrEmail($username);

        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 401,
                'message' => 'Username atau password salah',
            ]);
        }

        // Check if user is active
        if (!$user['is_active']) {
            return $this->response->setStatusCode(403)->setJSON([
                'status'  => 403,
                'message' => 'Akun Anda tidak aktif. Hubungi administrator.',
            ]);
        }

        // Verify password
        if (!password_verify($password, $user['password_hash'])) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 401,
                'message' => 'Username atau password salah',
            ]);
        }

        // Get user roles and permissions
        $userRoleModel = new \App\Models\UserRoleModel();
        $roles = $userRoleModel->getRolesByUserId($user['id']);
        $permissions = $userRoleModel->getPermissionsByUserId($user['id']);

        // Generate JWT token
        $secretKey = env('JWT_SECRET', 'your-secret-key-change-in-production');
        $issuedAt = time();
        $expirationTime = $issuedAt + (2 * 3600); // 2 hours

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expirationTime,
            'data' => [
                'id'          => $user['id'],
                'username'    => $user['username'],
                'email'       => $user['email'],
                'full_name'   => $user['full_name'],
                'roles'       => array_column($roles, 'slug'),
                'permissions' => $permissions,
            ],
        ];

        $token = JWT::encode($payload, $secretKey, 'HS256');

        // Update last login
        $this->userModel->update($user['id'], [
            'last_login_at' => date('Y-m-d H:i:s'),
        ]);

        // Log activity
        $this->logActivity($user['id'], 'login', 'User login berhasil');

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'Login berhasil',
            'data'    => [
                'token'       => $token,
                'expires_in'  => 7200, // seconds
                'user'        => [
                    'id'        => $user['id'],
                    'username'  => $user['username'],
                    'email'     => $user['email'],
                    'full_name' => $user['full_name'],
                    'roles'     => array_column($roles, 'name'),
                    'roleSlugs' => array_column($roles, 'slug'),
                ],
                'permissions' => $permissions,
            ],
        ]);
    }

    /**
     * Get current user info
     */
    public function me()
    {
        $userId = $this->request->userData['id'] ?? null;
        
        if (!$userId) {
            return $this->response->setStatusCode(401)->setJSON([
                'status'  => 401,
                'message' => 'User tidak terautentikasi',
            ]);
        }

        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return $this->response->setStatusCode(404)->setJSON([
                'status'  => 404,
                'message' => 'User tidak ditemukan',
            ]);
        }

        unset($user['password_hash']);

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'Data user ditemukan',
            'data'    => $user,
        ]);
    }

    /**
     * Logout (client should remove token)
     */
    public function logout()
    {
        $userId = $this->request->userData['id'] ?? null;
        
        if ($userId) {
            $this->logActivity($userId, 'logout', 'User logout');
        }

        return $this->response->setJSON([
            'status'  => 200,
            'message' => 'Logout berhasil',
        ]);
    }

    /**
     * Log user activity
     */
    private function logActivity(?int $userId, string $action, string $description)
    {
        $db = \Config\Database::connect();
        $db->table('log_aktivitas')->insert([
            'user_id'     => $userId,
            'aksi'        => $action,
            'deskripsi'   => $description,
            'ip_address'  => $this->request->getIPAddress(),
            'user_agent'  => $this->request->getUserAgent(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    }
}