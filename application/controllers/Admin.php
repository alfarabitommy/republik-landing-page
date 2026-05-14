<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Leads_model');

        // Middleware Proteksi Akses CMS
        // Jika tidak ada sesi logged_in, tendang ke halaman login
        if (!$this->session->userdata('logged_in')) {
            redirect('Auth/login');
        }
    }

    /**
     * Halaman Utama Dashboard (Menampilkan Tabel Leads)
     */
    public function index() {
        // Mengambil data leads yang aktif (is_deleted = 0)
        $data['leads'] = $this->Leads_model->get_active_leads();
        
        $this->load->view('v_admin_leads', $data);
    }

    /**
     * Fungsi AJAX untuk memperbarui status penanganan Lead
     * Method: POST
     */
    public function update_status() {
        // Validasi metode HTTP
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            return $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode(['status' => false, 'message' => 'Forbidden']));
        }

        // Tangkap input dengan filter XSS (TRUE)
        $id_lead = $this->input->post('id_lead', TRUE);
        $new_status = $this->input->post('status', TRUE);

        // Ambil status lama terlebih dahulu untuk dicatat di log
        $this->db->select('status');
        $this->db->where('id_lead', $id_lead);
        $old_lead = $this->db->get('tb_leads')->row_array();
        $old_status = $old_lead ? $old_lead['status'] : 'unknown';

        // Update status di tabel utama (tb_leads)
        $this->db->where('id_lead', $id_lead);
        $this->db->update('tb_leads', [
            'status' => $new_status,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // Catat jejak audit ke tabel log (tb_lead_status_logs)
        $log_data = [
            'lead_id'    => $id_lead,
            'user_id'    => $this->session->userdata('id_user'),
            'old_status' => $old_status,
            'new_status' => $new_status,
            'notes'      => 'Status updated via AJAX Dashboard',
            'changed_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tb_lead_status_logs', $log_data);

        // Kembalikan response JSON beserta token CSRF baru untuk keamanan beruntun
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode([
                'status' => true,
                'message' => 'Status berhasil diperbarui!',
                'csrf_token' => $this->security->get_csrf_hash()
            ]));
    }
}