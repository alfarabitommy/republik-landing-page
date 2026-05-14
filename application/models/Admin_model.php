<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        $this->load->database();
    }

    /**
     * Memverifikasi keberadaan user berdasarkan username
     * @param string $username
     * @return array|null Mengembalikan satu baris data (row_array) jika ditemukan
     */
    public function verify_user($username) {
        $this->db->select('*');
        $this->db->from('tb_users');
        $this->db->where('username', $username);
        $query = $this->db->get();
        
        return $query->row_array();
    }
}