<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePermissionsTable extends Migration
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
            'kode' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'modul' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'aksi' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'deskripsi' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'is_active' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 1,
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
        $this->forge->addKey('kode');
        $this->forge->addKey('modul');
        $this->forge->createTable('permissions', true);
    }

    public function down()
    {
        $this->forge->dropTable('permissions', true);
    }
}
