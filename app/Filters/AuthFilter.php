<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class AuthFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        
        if (empty($authHeader)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'   => 401,
                    'message'  => 'Token tidak ditemukan',
                ]);
        }

        // Extract token from "Bearer <token>"
        if (!preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'   => 401,
                    'message'  => 'Format token tidak valid',
                ]);
        }

        $token = $matches[1];
        $secretKey = env('JWT_SECRET', 'your-secret-key-change-in-production');

        try {
            $decoded = JWT::decode($token, new Key($secretKey, 'HS256'));
            
            // Add user info to request for later use
            $request->userData = [
                'id'         => $decoded->data->id,
                'username'   => $decoded->data->username,
                'email'      => $decoded->data->email,
                'roles'      => $decoded->data->roles ?? [],
                'permissions'=> $decoded->data->permissions ?? [],
            ];
        } catch (\Exception $e) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'   => 401,
                    'message'  => 'Token tidak valid atau kadaluarsa',
                    'error'    => $e->getMessage(),
                ]);
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to leave execution of the after() filter unless an
     * exception is thrown.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do here
    }
}
