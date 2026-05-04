<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePenilaianTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'santri_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'aspek_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'pengajar_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nilai' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
            ],
            'keterangan' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'tanggal_penilaian' => [
                'type' => 'DATE',
            ],
            'periode' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'comment'    => 'Format: YYYY-S (contoh: 2024-1)',
            ],
            'is_draft' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('santri_id', 'santri', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('aspek_id', 'aspek_penilaian', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('pengajar_id', 'users', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addKey(['santri_id', 'periode']);
        $this->forge->addKey('tanggal_penilaian');
        $this->forge->createTable('penilaian', true);
    }

    public function down()
    {
        $this->forge->dropTable('penilaian', true);
    }
}
