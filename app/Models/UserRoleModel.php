<?php

namespace App\Models;

use CodeIgniter\Model;

class UserRoleModel extends Model
{
    protected $table            = 'user_roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'user_id',
        'role_id',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';

    /**
     * Get roles by user ID
     */
    public function getRolesByUserId(int $userId): array
    {
        return $this->select('roles.*')
                    ->join('roles', 'roles.id = user_roles.role_id')
                    ->where('user_roles.user_id', $userId)
                    ->where('roles.is_active', 1)
                    ->findAll();
    }

    /**
     * Get permissions by user ID (union of all role permissions)
     */
    public function getPermissionsByUserId(int $userId): array
    {
        $permissions = $this->select('permissions.kode')
                            ->distinct()
                            ->join('roles', 'roles.id = user_roles.role_id')
                            ->join('role_permissions', 'role_permissions.role_id = roles.id')
                            ->join('permissions', 'permissions.id = role_permissions.permission_id')
                            ->where('user_roles.user_id', $userId)
                            ->where('roles.is_active', 1)
                            ->where('permissions.is_active', 1)
                            ->findAll();

        return array_column($permissions, 'kode');
    }

    /**
     * Check if user has specific role
     */
    public function userHasRole(int $userId, string $roleSlug): bool
    {
        $result = $this->select('user_roles.id')
                       ->join('roles', 'roles.id = user_roles.role_id')
                       ->where('user_roles.user_id', $userId)
                       ->where('roles.slug', $roleSlug)
                       ->where('roles.is_active', 1)
                       ->first();

        return !empty($result);
    }

    /**
     * Assign roles to user
     */
    public function assignRoles(int $userId, array $roleIds): bool
    {
        // Remove existing roles
        $this->where('user_id', $userId)->delete();

        // Insert new roles
        foreach ($roleIds as $roleId) {
            $this->insert([
                'user_id' => $userId,
                'role_id' => $roleId,
            ]);
        }

        return true;
    }

    /**
     * Get users by role ID
     */
    public function getUsersByRoleId(int $roleId): array
    {
        return $this->select('users.id, users.username, users.email, users.full_name, users.is_active')
                    ->join('users', 'users.id = user_roles.user_id')
                    ->where('user_roles.role_id', $roleId)
                    ->where('users.deleted_at', null)
                    ->findAll();
    }
}
