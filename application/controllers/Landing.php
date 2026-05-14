<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Landing extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->helper(['url', 'form']);
        $this->load->database(); // Memastikan database dimuat agar query dinamis berfungsi
    }

    public function index() {
        // Tarik semua konfigurasi dari database
        $query = $this->db->get('tb_system_settings')->result_array();
        
        // Ubah menjadi array asosiatif untuk mempermudah pemanggilan di View
        $settings = [];
        foreach ($query as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        // Passing data dinamis ke Front-End
        $data['settings'] = $settings;

        $this->load->view('v_landing', $data);
    }
}