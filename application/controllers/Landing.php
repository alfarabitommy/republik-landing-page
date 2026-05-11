<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Landing extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Memuat helper URL dan form
        $this->load->helper('url');
        $this->load->helper('form');
        // Baris load library security DIHAPUS karena class Security sudah otomatis dimuat oleh Core CI3
    }

    public function index() {
        // Memuat file view application/views/v_landing.php
        $this->load->view('v_landing');
    }
}