<?php

namespace App\Controllers\Api; // 'A' pada Api harus kapital kalau folder lo namanya 'Api'

use CodeIgniter\RESTful\ResourceController;

class Auth extends ResourceController
{
    public function login() // Pastikan nama fungsinya 'login' bukan 'index'
    {
        return $this->respond([
            'status'  => 200,
            'message' => 'Backend SyIAR Gemilang Ready!',
        ]);
    }
}