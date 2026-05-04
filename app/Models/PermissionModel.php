<?php

namespace App\Models;

use CodeIgniter\Model;

class PermissionModel extends Model
{
    protected $table            = 'permissions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'kode',
        'modul',
        'aksi',
        'deskripsi',
        'is_active',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'kode'        => 'required|alpha_numeric_punct|min_length[3]|max_length[100]|is_unique[permissions.kode,id,{id}]',
        'modul'       => 'required|min_length[3]|max_length[50]',
        'aksi'        => 'required|min_length[3]|max_length[50]',
        'deskripsi'   => 'permit_empty|string',
    ];

    protected $validationMessages = [
        'kode' => [
            'required'  => 'Kode permission wajib diisi',
            'is_unique' => 'Kode permission sudah digunakan',
        ],
        'modul' => [
            'required' => 'Modul wajib diisi',
        ],
        'aksi' => [
            'required' => 'Aksi wajib diisi',
        ],
    ];

    /**
     * Get all active permissions grouped by module
     */
    public function getGroupedByModule(): array
    {
        $permissions = $this->where('is_active', 1)
                            ->orderBy('modul', 'ASC')
                            ->orderBy('aksi', 'ASC')
                            ->findAll();

        $grouped = [];
        foreach ($permissions as $permission) {
            $grouped[$permission['modul']][] = $permission;
        }

        return $grouped;
    }

    /**
     * Get all active permissions
     */
    public function getActivePermissions(): array
    {
        return $this->where('is_active', 1)
                    ->orderBy('modul', 'ASC')
                    ->orderBy('aksi', 'ASC')
                    ->findAll();
    }

    /**
     * Find permission by code
     */
    public function findByCode(string $code): ?array
    {
        return $this->where('kode', $code)->first();
    }

    /**
     * Generate permission code from module and action
     */
    public static function generateCode(string $module, string $action): string
    {
        return strtolower($module) . '.' . strtolower($action);
    }

    /**
     * Check if permission exists
     */
    public function permissionExists(string $code): bool
    {
        return $this->where('kode', $code)->countAllResults() > 0;
    }
}
