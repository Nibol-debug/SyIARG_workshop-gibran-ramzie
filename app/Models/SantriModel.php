<?php

namespace App\Models;

use CodeIgniter\Model;

class SantriModel extends Model
{
    protected $table            = 'santri';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'nis',
        'nisn',
        'nama_lengkap',
        'jenis_kelamin',
        'tempat_lahir',
        'tanggal_lahir',
        'kelas_id',
        'alamat',
        'nama_wali',
        'telepon_wali',
        'status',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules = [
        'nis'           => 'required|alpha_numeric|min_length[3]|max_length[20]|is_unique[santri.nis,id,{id}]',
        'nama_lengkap'  => 'required|min_length[3]|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
        'status'        => 'required|in_list[aktif,lulus,pindah,keluar]',
    ];

    protected $validationMessages = [
        'nis' => [
            'required'  => 'NIS wajib diisi',
            'is_unique' => 'NIS sudah digunakan',
        ],
        'nama_lengkap' => [
            'required' => 'Nama lengkap wajib diisi',
        ],
        'jenis_kelamin' => [
            'required' => 'Jenis kelamin wajib diisi',
            'in_list'  => 'Pilih Laki-laki atau Perempuan',
        ],
    ];

    /**
     * Get all active santri
     */
    public function getActiveSantri(): array
    {
        return $this->where('status', 'aktif')
                    ->orderBy('nama_lengkap', 'ASC')
                    ->findAll();
    }

    /**
     * Search santri by NIS or name
     */
    public function searchSantri(string $query): array
    {
        return $this->groupStart()
                    ->like('nis', $query)
                    ->orLike('nama_lengkap', $query)
                    ->groupEnd()
                    ->where('status', 'aktif')
                    ->orderBy('nama_lengkap', 'ASC')
                    ->findAll();
    }

    /**
     * Get santri by class
     */
    public function getByClass(int $kelasId): array
    {
        return $this->where('kelas_id', $kelasId)
                    ->where('status', 'aktif')
                    ->orderBy('nama_lengkap', 'ASC')
                    ->findAll();
    }

    /**
     * Get santri with statistics
     */
    public function getWithStatistics(int $santriId): ?array
    {
        $santri = $this->find($santriId);
        if (!$santri) {
            return null;
        }

        $db = \Config\Database::connect();
        
        // Get penilaian count and average
        $stats = $db->table('penilaian')
                    ->select('COUNT(*) as total_nilai, AVG(nilai) as rata_rata')
                    ->where('santri_id', $santriId)
                    ->get()
                    ->getRowArray();

        $santri['statistics'] = $stats;

        return $santri;
    }
}
