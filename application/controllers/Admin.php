<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper('url');
        $this->load->model('Leads_model');
        $this->load->database();

        if (!$this->session->userdata('logged_in')) {
            redirect('Auth/login');
        }
    }

    public function index() {
        $data['leads'] = $this->Leads_model->get_active_leads();
        $this->load->view('v_admin_leads', $data);
    }

    public function update_status() {
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            return $this->output->set_status_header(403)->set_content_type('application/json')->set_output(json_encode(['status' => false, 'message' => 'Forbidden']));
        }
        $id_lead = $this->input->post('id_lead', TRUE);
        $new_status = $this->input->post('status', TRUE);
        
        $this->db->where('id_lead', $id_lead);
        $this->db->update('tb_leads', ['status' => $new_status, 'updated_at' => date('Y-m-d H:i:s')]);
        
        return $this->output->set_content_type('application/json')->set_output(json_encode([
            'status' => true,
            'message' => 'Status berhasil diperbarui!',
            'csrf_token' => $this->security->get_csrf_hash()
        ]));
    }

    public function settings() {
        $query = $this->db->get('tb_system_settings')->result_array();
        $settings = [];
        foreach ($query as $row) { 
            $settings[$row['setting_key']] = $row['setting_value']; 
        }
        $data['settings'] = $settings;
        $this->load->view('v_admin_settings', $data);
    }

    public function save_settings() {
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            $post_data = $this->input->post(NULL, TRUE);
            unset($post_data[$this->security->get_csrf_token_name()]);

            $config['upload_path']   = './assets/img/';
            $config['allowed_types'] = 'gif|jpg|png|jpeg|webp';
            $config['max_size']      = 5120;
            $config['encrypt_name']  = TRUE;
            $this->load->library('upload', $config);

            // Looping 6 slot video
            for ($i = 1; $i <= 6; $i++) {
                $field_name = 'video_thumb_' . $i;
                if (!empty($_FILES[$field_name]['name'])) {
                    if ($this->upload->do_upload($field_name)) {
                        $upload_data = $this->upload->data();
                        $post_data[$field_name] = 'assets/img/' . $upload_data['file_name'];
                    }
                }
            }

            foreach ($post_data as $key => $value) {
                if ($value === '' && strpos($key, 'video_thumb_') !== false) continue;
                $exists = $this->db->where('setting_key', $key)->get('tb_system_settings')->num_rows();
                if ($exists > 0) {
                    $this->db->where('setting_key', $key)->update('tb_system_settings', ['setting_value' => $value]);
                } else {
                    $this->db->insert('tb_system_settings', ['setting_key' => $key, 'setting_value' => $value]);
                }
            }
            $this->session->set_flashdata('success', 'Konten website berhasil diperbarui!');
            redirect('Admin/settings');
        }
    }
}