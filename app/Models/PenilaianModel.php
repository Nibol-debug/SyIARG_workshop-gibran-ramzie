<?php

namespace App\Models;

use CodeIgniter\Model;

class PenilaianModel extends Model
{
    protected $table            = 'penilaian';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $protectFields    = true;
    protected $allowedFields    = [
        'santri_id',
        'aspek_id',
        'pengajar_id',
        'nilai',
        'keterangan',
        'tanggal_penilaian',
        'periode',
        'is_draft',
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'santri_id'         => 'required|integer',
        'aspek_id'          => 'required|integer',
        'pengajar_id'       => 'required|integer',
        'nilai'             => 'required|numeric',
        'tanggal_penilaian' => 'required|valid_date',
        'periode'           => 'required|regex_match[/^\d{4}-\d$/]',
    ];

    /**
     * Get penilaian by santri with aspect details
     */
    public function getBySantri(int $santriId, string $periode = null): array
    {
        $builder = $this->select('penilaian.*, aspek_penilaian.nama_aspek, aspek_penilaian.bobot, kategori_penilaian.nama_kategori')
                        ->join('aspek_penilaian', 'aspek_penilaian.id = penilaian.aspek_id')
                        ->join('kategori_penilaian', 'kategori_penilaian.id = aspek_penilaian.kategori_id')
                        ->where('penilaian.santri_id', $santriId)
                        ->where('penilaian.is_draft', 0);

        if ($periode) {
            $builder->where('penilaian.periode', $periode);
        }

        return $builder->orderBy('penilaian.tanggal_penilaian', 'DESC')
                       ->findAll();
    }

    /**
     * Get rekap nilai by santri and period
     */
    public function getRekapBySantri(int $santriId, string $periode): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('penilaian')
                    ->select('
                        kategori_penilaian.id as kategori_id,
                        kategori_penilaian.nama_kategori,
                        kategori_penilaian.bobot_persen,
                        AVG(penilaian.nilai) as rata_rata,
                        COUNT(penilaian.id) as jumlah_nilai
                    ')
                    ->join('aspek_penilaian', 'aspek_penilaian.id = penilaian.aspek_id')
                    ->join('kategori_penilaian', 'kategori_penilaian.id = aspek_penilaian.kategori_id')
                    ->where('penilaian.santri_id', $santriId)
                    ->where('penilaian.periode', $periode)
                    ->where('penilaian.is_draft', 0)
                    ->groupBy('kategori_penilaian.id')
                    ->orderBy('kategori_penilaian.urutan', 'ASC')
                    ->get();

        return $query->getResultArray();
    }

    /**
     * Save batch penilaian
     */
    public function saveBatch(array $data): bool
    {
        return $this->insertBatch($data);
    }

    /**
     * Get penilaian by period and class
     */
    public function getByPeriodAndClass(string $periode, int $kelasId): array
    {
        $db = \Config\Database::connect();
        
        $query = $db->table('penilaian')
                    ->select('
                        santri.id as santri_id,
                        santri.nis,
                        santri.nama_lengkap,
                        kategori_penilaian.nama_kategori,
                        AVG(penilaian.nilai) as rata_rata
                    ')
                    ->join('santri', 'santri.id = penilaian.santri_id')
                    ->join('aspek_penilaian', 'aspek_penilaian.id = penilaian.aspek_id')
                    ->join('kategori_penilaian', 'kategori_penilaian.id = aspek_penilaian.kategori_id')
                    ->where('penilaian.periode', $periode)
                    ->where('penilaian.is_draft', 0)
                    ->where('santri.kelas_id', $kelasId)
                    ->groupBy('santri.id, kategori_penilaian.id')
                    ->orderBy('santri.nama_lengkap', 'ASC')
                    ->get();

        return $query->getResultArray();
    }

    /**
     * Delete draft penilaian by pengajar
     */
    public function deleteDraftsByPengajar(int $pengajarId): bool
    {
        return $this->where('pengajar_id', $pengajarId)
                    ->where('is_draft', 1)
                    ->delete();
    }
}
