<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'username',
        'email',
        'password_hash',
        'full_name',
        'is_active',
        'last_login_at',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'username'      => 'required|alpha_numeric_space|min_length[3]|max_length[50]|is_unique[users.username,id,{id}]',
        'email'         => 'required|valid_email|is_unique[users.email,id,{id}]',
        'password_hash' => 'required|min_length[8]',
        'full_name'     => 'permit_empty|string|max_length[100]',
    ];

    protected $validationMessages = [
        'username' => [
            'required'    => 'Username wajib diisi',
            'is_unique'   => 'Username sudah digunakan',
        ],
        'email' => [
            'required'    => 'Email wajib diisi',
            'valid_email' => 'Format email tidak valid',
            'is_unique'   => 'Email sudah digunakan',
        ],
    ];

    /**
     * Get user with roles
     */
    public function getUserWithRoles(int $userId): ?array
    {
        $user = $this->find($userId);
        if (!$user) {
            return null;
        }

        $userRoleModel = new UserRoleModel();
        $roles = $userRoleModel->getRolesByUserId($userId);

        $user['roles'] = $roles;

        return $user;
    }

    /**
     * Get user permissions
     */
    public function getUserPermissions(int $userId): array
    {
        $userRoleModel = new UserRoleModel();
        return $userRoleModel->getPermissionsByUserId($userId);
    }

    /**
     * Find user by username or email
     */
    public function findByUsernameOrEmail(string $identifier): ?array
    {
        return $this->where('username', $identifier)
                    ->orWhere('email', $identifier)
                    ->first();
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(int $userId, string $roleSlug): bool
    {
        $userRoleModel = new UserRoleModel();
        return $userRoleModel->userHasRole($userId, $roleSlug);
    }

    /**
     * Check if user has specific permission
     */
    public function hasPermission(int $userId, string $permissionCode): bool
    {
        $permissions = $this->getUserPermissions($userId);
        return in_array($permissionCode, $permissions, true);
    }
}
