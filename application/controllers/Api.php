<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Memuat library form validation dan model
        $this->load->library('form_validation');
        $this->load->model('Leads_model');
        // Baris load library security DIHAPUS di sini juga
    }

    /**
     * Endpoint untuk memproses submission dari form "Send Brief"
     * Method: POST
     */
    public function submit_brief() {
        // 1. Validasi HTTP Request (Hanya menerima POST)
        if ($this->input->server('REQUEST_METHOD') !== 'POST') {
            return $this->output
                ->set_status_header(403)
                ->set_content_type('application/json')
                ->set_output(json_encode([
                    'status' => false,
                    'message' => 'Forbidden: Invalid request method.'
                ]));
        }

        // 2. Set Rules Form Validation bawaan CI3
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim|xss_clean');
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email|xss_clean');
        $this->form_validation->set_rules('organization', 'Organization', 'required|trim|xss_clean');
        $this->form_validation->set_rules('position', 'Position', 'required|trim|xss_clean');
        $this->form_validation->set_rules('messages', 'Messages', 'required|trim|xss_clean');

        // Opsional field (First Name, Country) tetap di-filter jika ada
        $this->form_validation->set_rules('first_name', 'First Name', 'trim|xss_clean');
        $this->form_validation->set_rules('country', 'Country', 'trim|xss_clean');

        // 3. Eksekusi Validasi
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal
            $response = [
                'status' => false,
                'errors' => $this->form_validation->error_array(),
                'csrf_token' => $this->security->get_csrf_hash() // Generate token baru
            ];
        } else {
            // Jika validasi berhasil, tangkap input email
            $email = $this->input->post('email', TRUE);

            // 4. Cek duplikasi email via Model (Mencegah Spam)
            if ($this->Leads_model->check_duplicate_email($email)) {
                $response = [
                    'status' => false,
                    'errors' => ['email' => 'Terima kasih, namun Anda sudah mengirimkan brief menggunakan email ini hari ini.'],
                    'csrf_token' => $this->security->get_csrf_hash()
                ];
            } else {
                // 5. Susun array data sesuai struktur tb_leads
                $data_insert = [
                    'first_name'   => $this->input->post('first_name', TRUE),
                    'last_name'    => $this->input->post('last_name', TRUE),
                    'email'        => $email,
                    'organization' => $this->input->post('organization', TRUE),
                    'position'     => $this->input->post('position', TRUE),
                    'country'      => $this->input->post('country', TRUE),
                    'messages'     => $this->input->post('messages', TRUE),
                    'ip_address'   => $this->input->ip_address(),
                    'created_at'   => date('Y-m-d H:i:s')
                ];

                // 6. Simpan ke database
                $insert_id = $this->Leads_model->insert_lead($data_insert);

                if ($insert_id) {
                    $response = [
                        'status' => true,
                        'message' => 'Thank you, your brief is received.',
                        'csrf_token' => $this->security->get_csrf_hash() // Persiapan jika form tidak di-hide dan ingin submit ulang nanti
                    ];
                } else {
                    $response = [
                        'status' => false,
                        'errors' => ['server' => 'Terjadi kesalahan sistem saat menyimpan data.'],
                        'csrf_token' => $this->security->get_csrf_hash()
                    ];
                }
            }
        }

        // 7. Kembalikan Response JSON murni
        return $this->output
            ->set_content_type('application/json')
            ->set_output(json_encode($response));
    }
}