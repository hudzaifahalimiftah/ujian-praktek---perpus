<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');

// API Routes
$routes->group('api', ['namespace' => 'App\Controllers'], function($routes) {
    
    // ==================== AUTH ====================
    $routes->post('login', 'Api::login');
    $routes->post('register', 'Api::register');
    $routes->get('login', 'Api::testLogin'); // Test only
    $routes->options('login', 'Api::login'); // CORS preflight
    $routes->options('register', 'Api::register'); // CORS preflight
    
    // ==================== USERS MANAGEMENT ====================
    $routes->get('users', 'Api::testLogin'); // Get all users (test)
    $routes->put('users/(:num)', 'Api::updateUser/$1'); // Update user
    $routes->options('users/(:num)', 'Api::updateUser/$1'); // CORS preflight
    
    // ==================== BUKU CRUD ====================
    // GET - Ambil semua buku
    $routes->get('buku', 'Api::getBuku');
    
    // GET - Ambil buku by ID
    $routes->get('buku/(:num)', 'Api::getBukuById/$1');
    
    // POST - Tambah buku baru
    $routes->post('buku', 'Api::addBuku');
    
    // PUT - Update buku
    $routes->put('buku/(:num)', 'Api::updateBuku/$1');
    
    // DELETE - Hapus buku
    $routes->delete('buku/(:num)', 'Api::deleteBuku/$1');
    
    // ==================== CORS PREFLIGHT ====================
    $routes->options('buku', 'Api::getBuku');
    $routes->options('buku/(:num)', 'Api::getBukuById/$1');

    // ==================== PEMINJAMAN ROUTES ====================
$routes->get('users', 'Api::testLogin'); // Get semua users

$routes->post('peminjaman', 'Api::createPeminjaman');
$routes->get('peminjaman', 'Api::getPeminjaman');
$routes->get('peminjaman/(:num)', 'Api::getDetailPeminjaman/$1');
$routes->put('peminjaman/(:num)/pengembalian', 'Api::pengembalianBuku/$1');

// CORS preflight
$routes->options('peminjaman', 'Api::createPeminjaman');
$routes->options('peminjaman/(:num)', 'Api::getDetailPeminjaman/$1');
});