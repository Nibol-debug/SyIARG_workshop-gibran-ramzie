<?php

namespace App\Models;

use CodeIgniter\Model;

class RolePermissionModel extends Model
{
    protected $table            = 'role_permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'role_id',
        'permission_id',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';

    /**
     * Get permissions by role ID
     */
    public function getPermissionsByRoleId(int $roleId): array
    {
        return $this->select('permissions.*')
                    ->join('permissions', 'permissions.id = role_permissions.permission_id')
                    ->where('role_permissions.role_id', $roleId)
                    ->where('permissions.is_active', 1)
                    ->orderBy('permissions.modul', 'ASC')
                    ->orderBy('permissions.aksi', 'ASC')
                    ->findAll();
    }

    /**
     * Get roles by permission ID
     */
    public function getRolesByPermissionId(int $permissionId): array
    {
        return $this->select('roles.*')
                    ->join('roles', 'roles.id = role_permissions.role_id')
                    ->where('role_permissions.permission_id', $permissionId)
                    ->where('roles.is_active', 1)
                    ->findAll();
    }

    /**
     * Check if role has permission
     */
    public function roleHasPermission(int $roleId, string $permissionCode): bool
    {
        $result = $this->select('role_permissions.id')
                       ->join('permissions', 'permissions.id = role_permissions.permission_id')
                       ->where('role_permissions.role_id', $roleId)
                       ->where('permissions.kode', $permissionCode)
                       ->where('permissions.is_active', 1)
                       ->first();

        return !empty($result);
    }

    /**
     * Get permission IDs by role ID
     */
    public function getPermissionIdsByRoleId(int $roleId): array
    {
        $result = $this->select('permission_id')
                       ->where('role_id', $roleId)
                       ->findAll();

        return array_column($result, 'permission_id');
    }
}
