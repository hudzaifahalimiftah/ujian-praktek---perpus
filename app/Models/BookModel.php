<?php
namespace App\Models;

use CodeIgniter\Model;

class BookModel extends Model
{
    protected $table = 'buku';
    protected $primaryKey = 'id_buku';
    protected $allowedFields = ['nama_buku', 'tahun_terbit', 'penerbit'];
    protected $useTimestamps = false;
}