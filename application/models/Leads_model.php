<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Leads_model extends CI_Model {

    public function __construct() {
        parent::__construct();
        // Memastikan database library termuat
        $this->load->database();
    }

    /**
     * Memasukkan data prospek baru ke tabel tb_leads
     * Mengembalikan Insert ID
     */
    public function insert_lead($data) {
        $this->db->insert('tb_leads', $data);
        return $this->db->insert_id();
    }

    /**
     * Mengecek apakah email yang sama sudah submit pada hari yang sama
     * Menerapkan prinsip DRY untuk mencegah spam harian dari email yang sama
     */
    public function check_duplicate_email($email) {
        $this->db->where('email', $email);
        $this->db->where('DATE(created_at)', date('Y-m-d'));
        $query = $this->db->get('tb_leads');
        
        return $query->num_rows() > 0;
    }

    /**
     * MENGAMBIL DATA LEADS UNTUK DASHBOARD ADMIN (FUNGSI BARU)
     * Menarik semua data prospek yang belum dihapus (is_deleted = 0)
     * Diurutkan dari yang paling baru masuk (DESC)
     */
    public function get_active_leads() {
        $this->db->select('*');
        $this->db->from('tb_leads');
        $this->db->where('is_deleted', 0); // Hanya ambil yang tidak di-soft-delete
        $this->db->order_by('created_at', 'DESC');
        $query = $this->db->get();
        
        return $query->result_array();
    }
}