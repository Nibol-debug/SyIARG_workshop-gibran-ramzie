<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class PermissionFilter implements FilterInterface
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
        // Check if user data exists (set by AuthFilter)
        if (!isset($request->userData)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'  => 401,
                    'message' => 'User tidak terautentikasi',
                ]);
        }

        // If no specific permission required, allow access
        if (empty($arguments)) {
            return;
        }

        $userPermissions = $request->userData['permissions'] ?? [];
        
        // Check if user has any of the required permissions
        $hasPermission = false;
        foreach ($arguments as $requiredPermission) {
            if (in_array($requiredPermission, $userPermissions, true)) {
                $hasPermission = true;
                break;
            }
        }

        if (!$hasPermission) {
            return service('response')
                ->setStatusCode(403)
                ->setJSON([
                    'status'  => 403,
                    'message' => 'Akses ditolak: Anda tidak memiliki permission yang diperlukan',
                    'required_permission' => $arguments,
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
