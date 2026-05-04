<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAspekPenilaianTable extends Migration
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
            'kategori_id' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'nama_aspek' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'deskripsi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'skala_min' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 0,
            ],
            'skala_max' => [
                'type'       => 'INT',
                'constraint' => 5,
                'default'    => 100,
            ],
            'bobot' => [
                'type'       => 'DECIMAL',
                'constraint' => '5,2',
                'default'    => 1,
            ],
            'jenis_input' => [
                'type'       => 'ENUM',
                'constraint' => ['number', 'range', 'select', 'text'],
                'default'    => 'number',
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
            ],
            'urutan' => [
                'type'       => 'INT',
                'constraint' => 3,
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
        $this->forge->addForeignKey('kategori_id', 'kategori_penilaian', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('aspek_penilaian', true);
    }

    public function down()
    {
        $this->forge->dropTable('aspek_penilaian', true);
    }
}
