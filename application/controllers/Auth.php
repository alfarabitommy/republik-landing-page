<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Auth extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->library('session');
        $this->load->helper(['url', 'form']);
        $this->load->model('Admin_model');
    }

    /**
     * Menangani halaman dan proses Login
     */
    public function login() {
        // Jika user sudah login, langsung tendang ke Dashboard Admin
        if ($this->session->userdata('logged_in')) {
            redirect('Admin');
        }

        // Jika ada request POST dari form login
        if ($this->input->server('REQUEST_METHOD') === 'POST') {
            // Tangkap input dengan XSS Clean (TRUE)
            $username = $this->input->post('username', TRUE);
            $password_input = $this->input->post('password', TRUE);

            // Verifikasi username ke database
            $user = $this->Admin_model->verify_user($username);

            // Jika user ditemukan & password valid (menggunakan algoritma Bcrypt)
            if ($user && password_verify($password_input, $user['password'])) {
                
                // Susun data sesi
                $session_data = [
                    'id_user'   => $user['id_user'],
                    'username'  => $user['username'],
                    'role_id'   => $user['role_id'],
                    'logged_in' => TRUE
                ];

                // Set userdata lalu alihkan ke Controller Admin
                $this->session->set_userdata($session_data);
                redirect('Admin');
                
            } else {
                // Jika gagal, set flashdata dan kembalikan ke halaman login
                $this->session->set_flashdata('error', 'Username atau Password salah.');
                redirect('Auth/login');
            }
        }

        // Jika bukan POST (akses pertama kali), muat view login
        $this->load->view('v_login');
    }

    /**
     * Menangani proses Logout
     */
    public function logout() {
        // Hancurkan semua sesi dan kembalikan ke halaman login
        $this->session->sess_destroy();
        redirect('Auth/login');
    }
}