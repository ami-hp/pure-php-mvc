<?php

namespace App\Core;

use App\Core\Database;
use PDO;

class Model
{
    protected $table;
    protected $primaryKey = 'id';
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function find($value)
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :{$this->primaryKey}");
        $stmt->execute([($this->primaryKey ?? "id") => $value]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function all()
    {
        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}