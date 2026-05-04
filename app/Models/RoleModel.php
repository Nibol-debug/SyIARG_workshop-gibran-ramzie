<?php

namespace App\Models;

use CodeIgniter\Model;

class RoleModel extends Model
{
    protected $table            = 'roles';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'name'        => 'required|min_length[3]|max_length[100]',
        'slug'        => 'required|alpha_dash|min_length[3]|max_length[100]|is_unique[roles.slug,id,{id}]',
        'description' => 'permit_empty|string',
    ];

    protected $validationMessages = [
        'name' => [
            'required'  => 'Nama role wajib diisi',
            'min_length' => 'Nama role minimal 3 karakter',
        ],
        'slug' => [
            'required'  => 'Slug wajib diisi',
            'is_unique' => 'Slug sudah digunakan',
        ],
    ];

    /**
     * Get all active roles
     */
    public function getActiveRoles(): array
    {
        return $this->where('is_active', 1)
                    ->orderBy('name', 'ASC')
                    ->findAll();
    }

    /**
     * Get role with permissions
     */
    public function getRoleWithPermissions(int $roleId): ?array
    {
        $role = $this->find($roleId);
        if (!$role) {
            return null;
        }

        $rolePermissionModel = new RolePermissionModel();
        $permissions = $rolePermissionModel->getPermissionsByRoleId($roleId);

        $role['permissions'] = $permissions;

        return $role;
    }

    /**
     * Assign permissions to role
     */
    public function assignPermissions(int $roleId, array $permissionIds): bool
    {
        $db = \Config\Database::connect();
        $rolePermissionModel = new RolePermissionModel();

        // Remove existing permissions
        $rolePermissionModel->where('role_id', $roleId)->delete();

        // Insert new permissions
        foreach ($permissionIds as $permissionId) {
            $rolePermissionModel->insert([
                'role_id'       => $roleId,
                'permission_id' => $permissionId,
            ]);
        }

        return true;
    }

    /**
     * Get users count for a role
     */
    public function getUsersCount(int $roleId): int
    {
        $userRoleModel = new UserRoleModel();
        return $userRoleModel->where('role_id', $roleId)->countAllResults();
    }
}
