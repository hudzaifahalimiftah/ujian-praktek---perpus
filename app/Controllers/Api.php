<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;

class Api extends ResourceController
{
    use ResponseTrait;

    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper('jwt');
    }

    // ==================== AUTH API ====================
    
    // Login API
    public function login()
    {
        $username = $this->request->getJSON()->username;
        $password = $this->request->getJSON()->password;

        if (!$username || !$password) {
            return $this->fail('Username dan password harus diisi', 400);
        }

        $builder = $this->db->table('users');
        $user = $builder->where('username', $username)->get()->getRowArray();

        if (!$user) {
            return $this->fail('Username tidak ditemukan', 404);
        }

        if (!password_verify($password, $user['password'])) {
            return $this->fail('Password salah', 401);
        }

        $token = bin2hex(random_bytes(32));
        
        return $this->respond([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'id_user' => $user['id_user'],
                'username' => $user['username'],
                'token' => $token
            ]
        ]);
    }

    // Register New User
    public function register()
    {
        $username = $this->request->getJSON()->username;
        $password = $this->request->getJSON()->password;

        if (!$username || !$password) {
            return $this->fail('Username dan password harus diisi', 400);
        }

        // Validasi panjang
        if (strlen($username) < 3) {
            return $this->fail('Username minimal 3 karakter', 400);
        }

        if (strlen($password) < 6) {
            return $this->fail('Password minimal 6 karakter', 400);
        }

        // Cek apakah username sudah ada
        $builder = $this->db->table('users');
        $existing = $builder->where('username', $username)->get()->getRowArray();

        if ($existing) {
            return $this->fail('Username sudah digunakan', 409);
        }

        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        // Insert ke database
        $insert = $builder->insert([
            'username' => $username,
            'password' => $hashedPassword
        ]);

        if ($insert) {
            return $this->respondCreated([
                'status' => 'success',
                'message' => 'User berhasil didaftarkan',
                'data' => [
                    'id_user' => $this->db->insertID(),
                    'username' => $username
                ]
            ]);
        }

        return $this->fail('Gagal mendaftarkan user', 500);
    }

    // Update User (Ganti Username & Password)
    public function updateUser($id)
    {
        $builder = $this->db->table('users');
        $user = $builder->where('id_user', $id)->get()->getRowArray();

        if (!$user) {
            return $this->fail('User tidak ditemukan', 404);
        }

        $json = $this->request->getJSON(true);
        $updateData = [];

        // Update username jika ada
        if (isset($json['username'])) {
            if (strlen($json['username']) < 3) {
                return $this->fail('Username minimal 3 karakter', 400);
            }
            
            // Cek apakah username baru sudah dipakai user lain
            $existing = $builder->where('username', $json['username'])
                               ->where('id_user !=', $id)
                               ->get()->getRowArray();
            
            if ($existing) {
                return $this->fail('Username sudah digunakan user lain', 409);
            }
            
            $updateData['username'] = $json['username'];
        }

        // Update password jika ada
        if (isset($json['password'])) {
            if (strlen($json['password']) < 6) {
                return $this->fail('Password minimal 6 karakter', 400);
            }
            $updateData['password'] = password_hash($json['password'], PASSWORD_DEFAULT);
        }

        if (empty($updateData)) {
            return $this->fail('Tidak ada data yang diupdate', 400);
        }

        $update = $builder->where('id_user', $id)->update($updateData);

        if ($update) {
            return $this->respond([
                'status' => 'success',
                'message' => 'User berhasil diupdate',
                'data' => [
                    'id_user' => $id,
                    'username' => $updateData['username'] ?? $user['username']
                ]
            ]);
        }

        return $this->fail('Gagal mengupdate user', 500);
    }

    // Get All Users (Test Only)
    public function testLogin()
    {
        $builder = $this->db->table('users');
        $users = $builder->select('id_user, username, created_at')->get()->getResultArray();

        return $this->respond([
            'status' => 'success',
            'message' => 'Data user berhasil diambil',
            'data' => $users
        ]);
    }

    // ==================== BUKU CRUD API ====================

    // GET ALL BOOKS
    public function getBuku()
    {
        $builder = $this->db->table('buku');
        $buku = $builder->orderBy('id_buku', 'DESC')->get()->getResultArray();

        return $this->respond([
            'status' => 'success',
            'message' => 'Data buku berhasil diambil',
            'data' => $buku
        ]);
    }

    // GET BOOK BY ID
    public function getBukuById($id)
    {
        $builder = $this->db->table('buku');
        $buku = $builder->where('id_buku', $id)->get()->getRowArray();

        if (!$buku) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        return $this->respond([
            'status' => 'success',
            'message' => 'Data buku berhasil diambil',
            'data' => $buku
        ]);
    }

    // ADD NEW BOOK
public function addBuku()
{
    $json = $this->request->getJSON(true);

    // Validation
    if (empty($json['nama_buku']) || empty($json['tahun_terbit']) || empty($json['penerbit'])) {
        return $this->respond([
            'status' => 'error',
            'message' => 'Nama buku, tahun terbit, dan penerbit wajib diisi'
        ], 400);
    }

    // Validate tahun terbit
    if ($json['tahun_terbit'] < 1900 || $json['tahun_terbit'] > 2100) {
        return $this->respond([
            'status' => 'error',
            'message' => 'Tahun terbit tidak valid'
        ], 400);
    }

    $builder = $this->db->table('buku');
    $insert = $builder->insert([
        'nama_buku' => $json['nama_buku'],
        'tahun_terbit' => (int)$json['tahun_terbit'],
        'penerbit' => $json['penerbit'],
        'stok' => isset($json['stok']) ? (int)$json['stok'] : 1  // Default stok 1
    ]);

    if ($insert) {
        return $this->respond([
            'status' => 'success',
            'message' => 'Buku berhasil ditambahkan',
            'data' => [
                'id_buku' => $this->db->insertID(),
                'nama_buku' => $json['nama_buku'],
                'tahun_terbit' => (int)$json['tahun_terbit'],
                'penerbit' => $json['penerbit'],
                'stok' => isset($json['stok']) ? (int)$json['stok'] : 1
            ]
        ], 201);
    }

    return $this->respond([
        'status' => 'error',
        'message' => 'Gagal menambahkan buku'
    ], 500);
}
// UPDATE BOOK - FIXED VERSION
public function updateBuku($id)
{
    try {
        $builder = $this->db->table('buku');
        $buku = $builder->where('id_buku', $id)->get()->getRowArray();

        if (!$buku) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        $json = $this->request->getJSON(true);
        
        // Debug log (opsional)
        log_message('info', 'Update buku ID: ' . $id . ' Data: ' . json_encode($json));

        // Validation
        if (empty($json['nama_buku']) || empty($json['tahun_terbit']) || empty($json['penerbit'])) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Nama buku, tahun terbit, dan penerbit wajib diisi'
            ], 400);
        }

        // Validate tahun terbit
        if ($json['tahun_terbit'] < 1900 || $json['tahun_terbit'] > 2100) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Tahun terbit tidak valid (harus antara 1900-2100)'
            ], 400);
        }

        $updateData = [
            'nama_buku' => trim($json['nama_buku']),
            'tahun_terbit' => (int)$json['tahun_terbit'],
            'penerbit' => trim($json['penerbit']),
            'stok' => isset($json['stok']) ? (int)$json['stok'] : $buku['stok']
        ];

        $update = $builder->where('id_buku', $id)->update($updateData);

        if ($update) {
            return $this->respond([
                'status' => 'success',
                'message' => 'Buku berhasil diupdate',
                'data' => [
                    'id_buku' => (int)$id,
                    'nama_buku' => $updateData['nama_buku'],
                    'tahun_terbit' => $updateData['tahun_terbit'],
                    'penerbit' => $updateData['penerbit']
                ]
            ]);
        } else {
            return $this->respond([
                'status' => 'error',
                'message' => 'Gagal mengupdate buku (tidak ada perubahan atau database error)'
            ], 500);
        }
    } catch (\Exception $e) {
        return $this->respond([
            'status' => 'error',
            'message' => 'Server error: ' . $e->getMessage()
        ], 500);
    }
}
    // DELETE BOOK
    public function deleteBuku($id)
    {
        $builder = $this->db->table('buku');
        $buku = $builder->where('id_buku', $id)->get()->getRowArray();

        if (!$buku) {
            return $this->respond([
                'status' => 'error',
                'message' => 'Buku tidak ditemukan'
            ], 404);
        }

        $delete = $builder->where('id_buku', $id)->delete();

        if ($delete) {
            return $this->respond([
                'status' => 'success',
                'message' => 'Buku berhasil dihapus',
                'data' => [
                    'id_buku' => $id,
                    'nama_buku' => $buku['nama_buku']
                ]
            ]);
        }

        return $this->respond([
            'status' => 'error',
            'message' => 'Gagal menghapus buku'
        ], 500);
    }

    // ==================== ENGLISH ALIASES (Optional) ====================
    
    public function books()
    {
        return $this->getBuku();
    }

    public function showBook($id)
    {
        return $this->getBukuById($id);
    }

    public function createBook()
    {
        return $this->addBuku();
    }

    public function updateBook($id)
    {
        return $this->updateBuku($id);
    }

    public function deleteBook($id)
    {
        return $this->deleteBuku($id);
    }


// ==================== PEMINJAMAN API ====================

public function createPeminjaman()
{
    $json = $this->request->getJSON(true);
    
    if (empty($json['id_user']) || empty($json['buku']) || !is_array($json['buku'])) {
        return $this->respond([
            'status' => 'error',
            'message' => 'Data user dan buku harus diisi'
        ], 400);
    }
    
    $this->db->transStart();
    
    try {
        // 1. Validasi stok untuk semua buku sebelum transaksi
        foreach ($json['buku'] as $buku) {
            $stokBuku = $this->db->table('buku')
                                ->select('stok, nama_buku')
                                ->where('id_buku', $buku['id_buku'])
                                ->get()
                                ->getRowArray();
            
            if (!$stokBuku) {
                throw new \Exception('Buku tidak ditemukan');
            }
            
            if ($stokBuku['stok'] < ($buku['jumlah'] ?? 1)) {
                throw new \Exception('Stok buku "' . $stokBuku['nama_buku'] . '" tidak mencukupi. Stok tersedia: ' . $stokBuku['stok']);
            }
        }
        
        // 2. Buat header peminjaman
        $peminjamanData = [
            'id_user' => $json['id_user'],
            'tanggal_pinjam' => date('Y-m-d'),
            'tanggal_deadline' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'dipinjam'
        ];
        
        $this->db->table('peminjaman')->insert($peminjamanData);
        $id_peminjaman = $this->db->insertID();
        
        // 3. Buat detail dan kurangi stok
        $total_buku = 0;
        foreach ($json['buku'] as $buku) {
            $detailData = [
                'id_peminjaman' => $id_peminjaman,
                'id_buku' => $buku['id_buku'],
                'jumlah' => $buku['jumlah'] ?? 1,
                'status' => 'dipinjam'
            ];
            
            $this->db->table('detail_peminjaman')->insert($detailData);
            $total_buku += ($buku['jumlah'] ?? 1);
            
            // KURANGI STOK
            $this->db->table('buku')
                    ->where('id_buku', $buku['id_buku'])
                    ->set('stok', 'stok - ' . ($buku['jumlah'] ?? 1), false)
                    ->update();
        }
        
        // 4. Update total buku di header
        $this->db->table('peminjaman')
                ->where('id_peminjaman', $id_peminjaman)
                ->update(['total_buku' => $total_buku]);
        
        $this->db->transComplete();
        
        return $this->respond([
            'status' => 'success',
            'message' => 'Peminjaman berhasil dibuat',
            'data' => [
                'id_peminjaman' => $id_peminjaman,
                'total_buku' => $total_buku
            ]
        ], 201);
        
    } catch (\Exception $e) {
        $this->db->transRollback();
        return $this->respond([
            'status' => 'error',
            'message' => 'Gagal: ' . $e->getMessage()
        ], 500);
    }
}

// READ: Get semua peminjaman
public function getPeminjaman()
{
    $builder = $this->db->table('peminjaman p');
    $builder->select('p.*, u.username, 
                     COUNT(d.id_detail) as jumlah_buku_dipinjam,
                     SUM(CASE WHEN d.status = "dikembalikan" THEN 1 ELSE 0 END) as jumlah_dikembalikan');
    $builder->join('users u', 'u.id_user = p.id_user');
    $builder->join('detail_peminjaman d', 'd.id_peminjaman = p.id_peminjaman', 'left');
    $builder->groupBy('p.id_peminjaman');
    $builder->orderBy('p.tanggal_pinjam', 'DESC');
    
    $peminjaman = $builder->get()->getResultArray();
    
    return $this->respond([
        'status' => 'success',
        'data' => $peminjaman
    ]);
}

// READ: Get detail satu peminjaman
public function getDetailPeminjaman($id)
{
    // Header
    $header = $this->db->table('peminjaman p')
                      ->select('p.*, u.username')
                      ->join('users u', 'u.id_user = p.id_user')
                      ->where('p.id_peminjaman', $id)
                      ->get()
                      ->getRowArray();
    
    if (!$header) {
        return $this->respond([
            'status' => 'error',
            'message' => 'Peminjaman tidak ditemukan'
        ], 404);
    }
    
    // Detail buku
    $detail = $this->db->table('detail_peminjaman d')
                      ->select('d.*, b.nama_buku, b.penerbit, b.tahun_terbit')
                      ->join('buku b', 'b.id_buku = d.id_buku')
                      ->where('d.id_peminjaman', $id)
                      ->get()
                      ->getResultArray();
    
    return $this->respond([
        'status' => 'success',
        'data' => [
            'header' => $header,
            'detail' => $detail
        ]
    ]);
}

// UPDATE: Pengembalian buku
public function pengembalianBuku($id_peminjaman)
{
    $json = $this->request->getJSON(true);
    $tanggal_kembali = date('Y-m-d');
    
    if (empty($json['buku_dikembalikan'])) {
        return $this->respond([
            'status' => 'error',
            'message' => 'Pilih buku yang dikembalikan'
        ], 400);
    }
    
    $this->db->transStart();
    
    try {
        foreach ($json['buku_dikembalikan'] as $id_buku) {
            // Update status detail
            $this->db->table('detail_peminjaman')
                    ->where('id_peminjaman', $id_peminjaman)
                    ->where('id_buku', $id_buku)
                    ->update([
                        'status' => 'dikembalikan',
                        'tanggal_dikembalikan' => $tanggal_kembali
                    ]);
            
            // AMBIL JUMLAH BUKU YANG DIPINJAM
            $detail = $this->db->table('detail_peminjaman')
                              ->select('jumlah')
                              ->where('id_peminjaman', $id_peminjaman)
                              ->where('id_buku', $id_buku)
                              ->get()
                              ->getRowArray();
            
            if ($detail) {
                // TAMBAH STOK KEMBALI
                $this->db->table('buku')
                        ->where('id_buku', $id_buku)
                        ->set('stok', 'stok + ' . $detail['jumlah'], false)
                        ->update();
            }
        }
        
        // Cek apakah semua sudah dikembalikan
        $belum_kembali = $this->db->table('detail_peminjaman')
                                 ->where('id_peminjaman', $id_peminjaman)
                                 ->where('status', 'dipinjam')
                                 ->countAllResults();
        
        $status_peminjaman = ($belum_kembali == 0) ? 'dikembalikan' : 'dipinjam';
        
        // Update header
        $updateData = ['status' => $status_peminjaman];
        if ($status_peminjaman === 'dikembalikan') {
            $updateData['tanggal_kembali'] = $tanggal_kembali;
        }
        
        $this->db->table('peminjaman')
                ->where('id_peminjaman', $id_peminjaman)
                ->update($updateData);
        
        $this->db->transComplete();
        
        return $this->respond([
            'status' => 'success',
            'message' => 'Pengembalian berhasil'
        ]);
        
    } catch (\Exception $e) {
        $this->db->transRollback();
        return $this->respond([
            'status' => 'error',
            'message' => 'Gagal: ' . $e->getMessage()
        ], 500);
    }
}
}
