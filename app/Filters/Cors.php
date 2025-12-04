<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class Cors implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        // Get the method
        $method = strtoupper($request->getMethod());
        
        // Set CORS headers IMMEDIATELY
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS, PATCH');
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With, X-API-KEY, Origin, Accept, Access-Control-Request-Method');
        header('Access-Control-Max-Age: 86400'); // Cache for 24 hours
        header('Access-Control-Allow-Credentials: true');
        
        // Handle OPTIONS preflight request
        if ($method === 'OPTIONS') {
            // Return 200 immediately for OPTIONS
            http_response_code(200);
            exit(0);
        }
        
        return $request;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Add CORS headers to response as well
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-API-KEY, Origin, Accept');
        $response->setHeader('Access-Control-Allow-Credentials', 'true');
        
        return $response;
    }
}