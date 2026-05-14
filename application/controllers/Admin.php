<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Leads_model');
        $this->load->database();

        // Middleware Proteksi Akses CMS
        if (!$this->session->userdata('logged_in')) {
            redirect('Auth/login');
        }
    }

    /**
     * Halaman Utama Dashboard (Menampilkan Tabel Leads)
     */
    public function index() {
        $data['leads'] = $this->Leads_model->get_active_leads();
        $this->load->view('v_admin_leads', $data);
    }

    /**
     * Fungsi AJAX untuk memperbarui status penanganan Lead
     */
    public function update_status() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['status' => false, 'message' => 'Forbidden']));
        }

        $id_lead = $this->input->post('id_lead', TRUE);
        $new_status = $this->input->post('status', TRUE);

        $this->db->select('status');
        $this->db->where('id_lead', $id_lead);
        $old_lead = $this->db->get('tb_leads')->row_array();
        $old_status = $old_lead ? $old_lead['status'] : 'unknown';

        $this->db->where('id_lead', $id_lead);
        $this->db->update('tb_leads', ['status' => $new_status, 'updated_at' => date('Y-m-d H:i:s')]);

        $log_data = [
            'lead_id'    => $id_lead,
            'user_id'    => $this->session->userdata('id_user'),
            'old_status' => $old_status,
            'new_status' => $new_status,
            'notes'      => 'Status updated via AJAX',
            'changed_at' => date('Y-m-d H:i:s')
        ];
        $this->db->insert('tb_lead_status_logs', $log_data);

        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'status' => true,
            'message' => 'Status berhasil diperbarui!',
            'csrf_token' => $this->security->get_csrf_hash()
        ]));
    }

    /**
     * Halaman Pengaturan Konten Dinamis (CMS Editor)
     */
    public function settings() {
        // Ambil semua data dari tb_system_settings
        $query = $this->db->get('tb_system_settings')->result_array();
        
        $settings = [];
        foreach ($query as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        $data['settings'] = $settings;
        $this->load->view('v_admin_settings', $data);
    }

    /**
     * Menyimpan perubahan dari Editor Konten & Upload Gambar
     */
    public function save_settings() {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            // Tangkap semua input POST (teks) yang sudah di-filter XSS
            $post_data = $this->input->post(NULL, TRUE);
            unset($post_data[$this->security->get_csrf_token_name()]); // Singkirkan CSRF

            // Konfigurasi Library Upload CI3
            $config['upload_path']   = './assets/img/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size']      = 5120; // Maksimal 5MB
            $config['encrypt_name']  = TRUE; // Acak nama file agar tidak ada spasi/konflik
            
            $this->load->library('upload', $config);

            // Looping untuk memproses ke-5 input upload file gambar
            for ($i = 1; $i <= 5; $i++) {
                $field_name = 'video_thumb_' . $i;
                
                // Cek apakah ada file yang diunggah di field ini
                if (!empty($_FILES[$field_name]['name'])) {
                    if ($this->upload->do_upload($field_name)) {
                        $upload_data = $this->upload->data();
                        // Simpan lokasi file (relative path) ke dalam array data yang akan di-upsert
                        $post_data[$field_name] = 'assets/img/' . $upload_data['file_name'];
                    } else {
                        // Jika gagal upload (misal format salah/ukuran terlalu besar), set flashdata error
                        $error = $this->upload->display_errors('','');
                        $this->session->set_flashdata('error', 'Gagal upload Thumbnail '.$i.': ' . $error);
                        redirect('Admin/settings');
                        return;
                    }
                }
            }

            // Looping Batch Update (Update jika key sudah ada, Insert jika belum ada)
            foreach ($post_data as $key => $value) {
                // Kita abaikan jika value kosong pada kasus file upload (artinya user tidak upload file baru)
                if ($value === '' && strpos($key, 'video_thumb_') !== false) {
                    continue; 
                }

                $exists = $this->db->where('setting_key', $key)->get('tb_system_settings')->num_rows();
                
                if ($exists > 0) {
                    $this->db->where('setting_key', $key)->update('tb_system_settings', ['setting_value' => $value]);
                } else {
                    $this->db->insert('tb_system_settings', ['setting_key' => $key, 'setting_value' => $value]);
                }
            }

            $this->session->set_flashdata('success', 'Konten website berhasil diperbarui secara instan!');
            redirect('Admin/settings');
        }
    }
}