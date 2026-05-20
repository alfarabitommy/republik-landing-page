<!-- file application/controllers/Admin.php -->
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
<!-- end file application/controllers/Admin.php -->

<!-- file application/controllers/Api.php -->
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {

    public function __construct() {
        parent::__construct();
        // Memuat library form validation dan model
        $this->load->library('form_validation');
        $this->load->model('Leads_model');
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

        // 2. Set Rules Form Validation bawaan CI3 (xss_clean dihapus untuk efisiensi)
        $this->form_validation->set_rules('last_name', 'Last Name', 'required|trim');
        $this->form_validation->set_rules('email', 'Email', 'required|trim|valid_email');
        $this->form_validation->set_rules('organization', 'Organization', 'required|trim');
        $this->form_validation->set_rules('position', 'Position', 'required|trim');
        $this->form_validation->set_rules('messages', 'Messages', 'required|trim');

        // Opsional field
        $this->form_validation->set_rules('first_name', 'First Name', 'trim');
        $this->form_validation->set_rules('country', 'Country', 'trim');

        // 3. Eksekusi Validasi
        if ($this->form_validation->run() == FALSE) {
            // Jika validasi gagal
            $response = [
                'status' => false,
                'errors' => $this->form_validation->error_array(),
                'csrf_token' => $this->security->get_csrf_hash() // Generate token baru
            ];
        } else {
            // Jika validasi berhasil, tangkap input email (parameter TRUE otomatis filter XSS)
            $email = $this->input->post('email', TRUE);

            // 4. Cek duplikasi email via Model (Mencegah Spam)
            if ($this->Leads_model->check_duplicate_email($email)) {
                $response = [
                    'status' => false,
                    'errors' => ['email' => 'Terima kasih, namun Anda sudah mengirimkan brief menggunakan email ini hari ini.'],
                    'csrf_token' => $this->security->get_csrf_hash()
                ];
            } else {
                // 5. Susun array data (Semua input ditangkap dengan parameter TRUE untuk filter XSS)
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
                        'csrf_token' => $this->security->get_csrf_hash() 
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
<!-- end file application/controllers/Api.php -->

<!-- file application/controllers/Auth.php -->
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
<!-- end file application/controllers/Auth.php -->

<!-- file application/controllers/Landing.php -->
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
<!-- end file application/controllers/Landing.php -->

<!-- file application/models/Admin_model.php -->
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
<!-- end file application/models/Admin_model.php -->

<!-- file application/models/Leads_model.php -->
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
<!-- end file application/models/Leads_model.php -->

<!-- file application/views/v_admin_leads.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REPUBLIK | Leads Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
    <style>
        /* CSS Variables & Reset */
        :root {
            --bg-dark: #0B0B0B;
            --bg-panel: #151515;
            --bg-hover: #222222;
            --accent-blue: #4A7AFF;
            --accent-gold: #D4AF37;
            --text-main: #ffffff;
            --text-muted: #888888;
            --border-color: #333333;
            --font-main: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: var(--font-main);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Layout */
        .sidebar {
            width: 260px;
            background-color: var(--bg-panel);
            border-right: 1px solid var(--border-color);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
        }

        .brand-logo {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: 2px;
            color: var(--text-main);
            margin-bottom: 40px;
            text-decoration: none;
        }
        
        .brand-logo span { color: var(--accent-blue); }

        .nav-menu { list-style: none; flex-grow: 1; }
        .nav-item { margin-bottom: 10px; }
        .nav-link {
            display: block;
            padding: 12px 15px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            background-color: var(--accent-blue);
            color: #ffffff;
        }

        .user-panel {
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .logout-btn {
            display: block;
            margin-top: 10px;
            color: #ff4444;
            text-decoration: none;
            font-weight: bold;
        }

        /* Content Area */
        .main-content {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 { font-size: 2rem; }

        /* DataTables Custom Styling for Dark Mode */
        .dataTable-wrapper {
            background-color: var(--bg-panel);
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .dataTable-table > thead > tr > th {
            border-bottom: 1px solid var(--border-color);
            color: var(--accent-gold);
            text-transform: uppercase;
            font-size: 0.85rem;
            padding-bottom: 15px;
        }
        .dataTable-table > tbody > tr > td {
            border-bottom: 1px solid var(--border-color);
            padding: 15px 10px;
            vertical-align: middle;
            color: #e0e0e0;
        }
        .dataTable-table > tbody > tr:hover {
            background-color: var(--bg-hover) !important;
        }
        .dataTable-input, .dataTable-selector {
            background-color: var(--bg-dark);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 4px;
        }

        /* Status Dropdown Styling */
        .select-status {
            background-color: var(--bg-dark);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 6px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
            cursor: pointer;
            outline: none;
        }
        .select-status:focus { border-color: var(--accent-blue); }
        
        .msg-preview {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            font-size: 0.9rem;
        }

        /* Notifikasi Toast Minimalis */
        #toast {
            visibility: hidden;
            min-width: 250px;
            background-color: var(--accent-blue);
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 16px;
            position: fixed;
            z-index: 1000;
            right: 30px;
            bottom: 30px;
            font-weight: bold;
            opacity: 0;
            transition: opacity 0.5s, visibility 0.5s;
        }
        #toast.show {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="<?= base_url('Admin') ?>" class="brand-logo">REP<span>.</span></a>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="<?= base_url('Admin') ?>" class="nav-link active">Leads Inbox</a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('Admin/settings') ?>" class="nav-link">Settings</a>
            </li>
        </ul>

        <div class="user-panel">
            Logged in as:<br>
            <strong style="color:var(--text-main)"><?= $this->session->userdata('username'); ?></strong>
            <a href="<?= base_url('Auth/logout') ?>" class="logout-btn">Log Out &rarr;</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h2>Leads Intelligence</h2>
        </div>

        <input type="hidden" id="csrf_token" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div class="dataTable-wrapper">
            <table id="leadsTable" class="dataTable-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client Name</th>
                        <th>Organization</th>
                        <th>Messages</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($leads)): foreach($leads as $row): ?>
                    <tr>
                        <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <strong><?= html_escape($row['first_name'] . ' ' . $row['last_name']) ?></strong><br>
                            <small style="color:var(--text-muted)"><?= html_escape($row['email']) ?></small>
                        </td>
                        <td>
                            <?= html_escape($row['organization']) ?><br>
                            <small style="color:var(--text-muted)"><?= html_escape($row['position']) ?></small>
                        </td>
                        <td>
                            <span class="msg-preview" title="<?= html_escape($row['messages']) ?>">
                                <?= html_escape($row['messages']) ?>
                            </span>
                        </td>
                        <td>
                            <select class="select-status" onchange="updateLeadStatus(this, <?= $row['id_lead'] ?>)">
                                <option value="new" <?= ($row['status'] == 'new') ? 'selected' : '' ?>>New</option>
                                <option value="reviewed" <?= ($row['status'] == 'reviewed') ? 'selected' : '' ?>>Reviewed</option>
                                <option value="contacted" <?= ($row['status'] == 'contacted') ? 'selected' : '' ?>>Contacted</option>
                                <option value="closed" <?= ($row['status'] == 'closed') ? 'selected' : '' ?>>Closed</option>
                                <option value="spam" <?= ($row['status'] == 'spam') ? 'selected' : '' ?>>Spam</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No leads found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="toast">Status Updated!</div>

    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const myTable = document.getElementById("leadsTable");
            if (myTable) {
                new simpleDatatables.DataTable(myTable, {
                    searchable: true,
                    fixedHeight: true,
                    perPage: 10
                });
            }
        });

        function updateLeadStatus(selectElement, idLead) {
            const newStatus = selectElement.value;
            const csrfInput = document.getElementById('csrf_token');
            const csrfName = csrfInput.getAttribute('name');
            const csrfValue = csrfInput.value;

            const formData = new FormData();
            formData.append('id_lead', idLead);
            formData.append('status', newStatus);
            formData.append(csrfName, csrfValue);

            selectElement.disabled = true;

            fetch('<?= base_url("Admin/update_status") ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if(!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                selectElement.disabled = false;
                if(data.status) {
                    csrfInput.value = data.csrf_token;
                    showToast(data.message);
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(error => {
                selectElement.disabled = false;
                alert('Terjadi kesalahan koneksi sistem.');
                console.error('Error:', error);
            });
        }

        function showToast(msg) {
            const toast = document.getElementById("toast");
            toast.innerText = msg;
            toast.className = "show";
            setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 3000);
        }
    </script>
</body>
</html>
<!-- end file application/views/v_admin_leads.php -->

<!-- file application/views/v_admin_settings.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REPUBLIK | Content Editor</title>
    <style>
        :root { --bg-dark: #0B0B0B; --bg-panel: #151515; --accent-blue: #4A7AFF; --text-main: #ffffff; --border-color: #333333; --accent-gold: #D4AF37; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-dark); color: var(--text-main); font-family: 'Helvetica Neue', Arial, sans-serif; display: flex; min-height: 100vh; }
        .sidebar { width: 260px; background-color: var(--bg-panel); border-right: 1px solid var(--border-color); padding: 30px 20px; }
        .brand-logo { font-size: 1.5rem; font-weight: 900; color: #fff; text-decoration: none; margin-bottom: 40px; display: block; }
        .nav-link { display: block; padding: 12px 15px; color: #888; text-decoration: none; font-weight: bold; border-radius: 4px; margin-bottom: 5px; }
        .nav-link.active { background-color: var(--accent-blue); color: #fff; }
        .main-content { flex-grow: 1; padding: 40px; overflow-y: auto; }
        .editor-wrapper { background-color: var(--bg-panel); padding: 30px; border-radius: 8px; border: 1px solid var(--border-color); max-width: 900px; }
        .form-group-bundle { background-color: #1a1a1a; padding: 20px; border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 25px; }
        label { display: block; margin-bottom: 10px; font-weight: bold; color: var(--accent-gold); text-transform: uppercase; font-size: 0.8rem; }
        input[type="text"], textarea { width: 100%; padding: 12px; background-color: #000; border: 1px solid #444; border-radius: 4px; color: #fff; margin-bottom: 10px; }
        .img-preview { max-height: 60px; margin-bottom: 10px; display: block; border: 1px solid #444; }
        .btn-save { background-color: var(--accent-blue); color: #fff; border: none; padding: 15px 30px; font-weight: bold; border-radius: 4px; cursor: pointer; transition: 0.3s; }
        .btn-save:hover { background-color: #335ECC; }
    </style>
</head>
<body>
    <aside class="sidebar">
        <a href="#" class="brand-logo">REP.</a>
        <a href="<?= base_url('Admin') ?>" class="nav-link">Leads Inbox</a>
        <a href="<?= base_url('Admin/settings') ?>" class="nav-link active">Settings</a>
    </aside>
    <main class="main-content">
        <h2>Content Editor</h2>
        <div class="editor-wrapper">
            <?php if($this->session->flashdata('success')): ?> 
                <p style="color:lime; margin-bottom:20px; padding: 15px; background: rgba(0,255,0,0.1); border: 1px solid lime; border-radius: 4px;"><?= $this->session->flashdata('success') ?></p> 
            <?php endif; ?>
            
            <form action="<?= base_url('Admin/save_settings') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                
                <h3 style="margin: 20px 0;">Portfolio Videos (Slot 1-6)</h3>
                
                <?php for($i=1; $i<=6; $i++): ?>
                <div class="form-group-bundle">
                    <label>Portfolio <?= $i ?></label>
                    <input type="text" name="video_title_<?= $i ?>" value="<?= $settings['video_title_'.$i] ?? '' ?>" placeholder="Judul Display (Contoh: HONDA AHM)">
                    <input type="text" name="video_<?= $i ?>" value="<?= $settings['video_'.$i] ?? '' ?>" placeholder="URL Video (YouTube / Link MP4)">
                    
                    <?php if(!empty($settings['video_thumb_'.$i])): ?>
                        <img src="<?= base_url($settings['video_thumb_'.$i]) ?>" class="img-preview" alt="Thumbnail Preview">
                    <?php endif; ?>
                    
                    <input type="file" name="video_thumb_<?= $i ?>" accept="image/*">
                </div>
                <?php endfor; ?>

                <button type="submit" class="btn-save">Save Changes</button>
            </form>
        </div>
    </main>
</body>
</html>
<!-- end file application/views/v_admin_settings.php -->

<!-- file application/views/v_landing.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0B0B0B">
    
    <title>REPUBLIK | Creative Intelligence Agency</title>
    <meta name="title" content="REPUBLIK | Creative Intelligence Agency">
    <meta name="description" content="Your brand doesn't need more content. It needs a sharper creative system. We help brands turn business problems into culture-sharp creative platforms.">
    <meta name="keywords" content="Creative Agency, Intelligence Agency, Brand Strategy, B2B Marketing, Content System, Indonesia Agency, REPUBLIK">
    <meta name="author" content="REPUBLIK">
    
    <link rel="canonical" href="<?= base_url() ?>">

    <meta property="og:type" content="website">
    <meta property="og:url" content="<?= base_url() ?>">
    <meta property="og:title" content="REPUBLIK | Creative Intelligence Agency">
    <meta property="og:description" content="Your brand doesn't need more content. It needs a sharper creative system. We help brands turn business problems into culture-sharp creative platforms.">
    <meta property="og:image" content="<?= base_url('assets/img/college1.png') ?>">

    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="<?= base_url() ?>">
    <meta property="twitter:title" content="REPUBLIK | Creative Intelligence Agency">
    <meta property="twitter:description" content="Your brand doesn't need more content. It needs a sharper creative system.">
    <meta property="twitter:image" content="<?= base_url('assets/img/college1.png') ?>">

    <link rel="preload" href="<?= base_url('assets/css/style.css') ?>" as="style">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>

    <?php 
    // Headline statik dikunci dalam satu variabel murni
    $shared_headline = '<span class="headline-top">Your brand doesn’t need more content.</span> <span class="headline-bottom">It needs a sharper creative system.</span>';
    ?>

    <main>
        <header class="hero-section">
            <div class="hero-collage-container">
                <div class="hero-logo-overlay">
                    <img src="<?= base_url('assets/img/re-logo-2026.png') ?>" alt="REPUBLIK Creative Intelligence Agency Logo" class="hero-logo-img">
                </div>

                <div class="hero-collage-grid">
                    <div class="collage-cell item-tall placeholder-dark"><img src="<?= base_url('assets/img/hero/1.png') ?>" alt="REPUBLIK Creative Work 1" class="collage-img" fetchpriority="high"></div>
                    
                    <div class="collage-cell item-wide-top placeholder-gray"><img src="<?= base_url('assets/img/hero/3.png') ?>" alt="REPUBLIK Creative Work 2" class="collage-img" fetchpriority="high"></div>
                    <div class="collage-cell item-small-top placeholder-dark"><img src="<?= base_url('assets/img/hero/4.png') ?>" alt="REPUBLIK Creative Work 3" class="collage-img"></div>
                    <div class="collage-cell item-new-top-right placeholder-gray"><img src="<?= base_url('assets/img/hero/2.png') ?>" alt="REPUBLIK Creative Work Extra 1" class="collage-img"></div>
                    
                    <div class="collage-cell item-wide-bottom placeholder-gray"><img src="<?= base_url('assets/img/hero/5.png') ?>" alt="REPUBLIK Creative Work 4" class="collage-img"></div>
                    <div class="collage-cell item-small-bottom placeholder-dark"><img src="<?= base_url('assets/img/hero/6.png') ?>" alt="REPUBLIK Creative Work 5" class="collage-img"></div>
                    <div class="collage-cell item-new-bottom-right placeholder-dark"><img src="<?= base_url('assets/img/hero/7.png') ?>" alt="REPUBLIK Creative Work Extra 2" class="collage-img"></div>
                </div>
            </div>

            <div class="hero-text-wrapper">
                <div class="headline-container">
                    <?= $shared_headline ?> 
                </div>
            </div>
        </header>

        <section class="narrative-section">
            <div class="container">
                <div class="narrative-content">
                    <p>REPUBLIK helps brands turn business problems into culture-sharp creative platforms, social campaigns, content systems & performance-ready ideas. We work where attention is crowded, audiences are restless, and brands need more than "posting consistently."</p>
                    <p>We help you find the strategic angle, shape the creative idea, build the format system & make every touchpoint do its job.</p>
                    <div class="methodology">
                        <small>From big campaign thinking to daily content execution, we connect:</small>
                        <strong>Idea &rarr; Format &rarr; Behavior &rarr; Measurement</strong>
                        <small>So your brand doesn't just show up. It gets noticed, remembered, and acted on.</small>
                    </div>
                </div>
            </div>
        </section>

        <section id="work" class="portfolio-section">
            <div class="container">
                <div class="section-title">
                    <h3>Our Work</h3>
                    <p>A selection of work across FMCG, beauty, automotive, lifestyle, and youth culture. Each one built to solve a real brand challenge through sharp strategy, strong creative and formats that move across platforms.</p>
                </div>
                
                <div id="portfolio-grid" class="portfolio-grid">
                    <?php for($i=1; $i<=6; $i++): ?>
                    <div class="portfolio-item video-trigger" aria-label="Play <?= html_escape($settings['video_title_'.$i] ?? 'Video '.$i) ?>" data-video-src="<?= $settings['video_'.$i] ?? '[https://www.youtube.com/watch?v=dQw4w9WgXcQ](https://www.youtube.com/watch?v=dQw4w9WgXcQ)' ?>" style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.6) 100%), url('<?= !empty($settings['video_thumb_'.$i]) ? base_url($settings['video_thumb_'.$i]) : '' ?>') center/cover no-repeat #222;">
                        <div class="overlay-text"><?= html_escape($settings['video_title_'.$i] ?? 'PORTFOLIO '.$i) ?></div>
                        <div class="play-icon" aria-hidden="true">▶</div>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </section>

        <section id="contact" class="form-section">
            <div class="container form-container">
                <div class="form-header">
                    <h2>Let's build something that moves.</h2>
                    <p>Tell us what you're trying to change, launch, grow, fix, or make impossible to ignore. Bring the business challenge. We'll help build the creative system.</p>
                </div>

                <form id="briefForm" action="#" method="POST">
                    <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">
                    
                    <div class="form-grid">
                        <div class="form-group"><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" autocomplete="given-name"></div>
                        <div class="form-group"><label for="last_name">Last Name*</label><input type="text" id="last_name" name="last_name" required autocomplete="family-name"></div>
                        <div class="form-group"><label for="email">email*</label><input type="email" id="email" name="email" required autocomplete="email"></div>
                        <div class="form-group"><label for="country">Country</label><input type="text" id="country" name="country" autocomplete="country-name"></div>
                        <div class="form-group"><label for="organization">Organization*</label><input type="text" id="organization" name="organization" required autocomplete="organization"></div>
                        <div class="form-group textarea-group"><label for="messages">Messages*</label><textarea id="messages" name="messages" rows="6" required></textarea></div>
                        <div class="form-group"><label for="position">Position*</label><input type="text" id="position" name="position" required autocomplete="organization-title"></div>
                    </div>

                    <div class="form-submit">
                        <button type="submit" id="btnSubmit" aria-label="Send your business brief">Send Brief</button>
                    </div>
                </form>
            </div>
        </section>
    </main>

    <div id="videoModal" class="video-modal" role="dialog" aria-modal="true" aria-label="Video Player">
        <div class="modal-overlay" aria-hidden="true"></div>
        <div class="modal-content">
            <button class="close-modal" aria-label="Close Video">&times;</button>
            <div id="videoContainer"></div>
        </div>
    </div>

    <button id="btnBackToTop" class="btn-back-to-top" aria-label="Back to Top">
        <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="19" x2="12" y2="5"></line><polyline points="5 12 12 5 19 12"></polyline></svg>
    </button>

    <div class="fab-container">
        <div class="fab-menu" id="fabMenu">
            <a href="[https://wa.me/6285714734610](https://wa.me/6285714734610)" target="_blank" class="fab-item" aria-label="WhatsApp">
                <span class="fab-tooltip">WhatsApp</span>
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
            </a>
            <a href="[https://www.instagram.com/republik.asia/](https://www.instagram.com/republik.asia/)" target="_blank" class="fab-item" aria-label="Instagram">
                <span class="fab-tooltip">Instagram</span>
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line></svg>
            </a>
            <a href="[https://id.linkedin.com/company/republikasia](https://id.linkedin.com/company/republikasia)" target="_blank" class="fab-item" aria-label="LinkedIn">
                <span class="fab-tooltip">LinkedIn</span>
                <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path><rect x="2" y="9" width="4" height="12"></rect><circle cx="4" cy="4" r="2"></circle></svg>
            </a>
        </div>
        <button class="fab-trigger" id="fabTrigger" aria-label="Contact Us">
            <svg class="fab-icon-chat" viewBox="0 0 24 24" width="28" height="28" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
            <svg class="fab-icon-close" viewBox="0 0 24 24" width="28" height="28" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="display: none;"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
        </button>
    </div>

    <footer>
        <div class="container">
            <p>Idea-first. System-led. Indonesia-native. Performance-aware.</p>
            <div class="footer-logo">
                <h2>REPUBLIK</h2>
                <p>Creative Intelligence Agency</p>
            </div>
        </div>
    </footer>

    <script src="<?= base_url('assets/js/main.js') ?>" defer></script>
</body>
</html>
<!-- end file application/views/v_landing.php -->

<!-- file application/views/v_login.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REPUBLIK | CMS Authentication</title>
    <style>
        /* Standalone Minimalist Dark Mode CSS untuk Halaman Login */
        :root {
            --bg-dark: #0B0B0B;
            --box-dark: #111111;
            --accent-blue: #4A7AFF;
            --accent-hover: #335ECC;
            --text-main: #ffffff;
            --text-muted: #888888;
            --border-color: #333333;
            --error-red: #ff4444;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        .login-wrapper {
            background-color: var(--box-dark);
            width: 100%;
            max-width: 400px;
            padding: 50px 40px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
            box-shadow: 0 10px 30px rgba(0,0,0,0.8);
        }

        .login-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .login-header h1 {
            font-size: 2rem;
            letter-spacing: 2px;
            margin-bottom: 5px;
            font-weight: 900;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.85rem;
            font-weight: bold;
            text-transform: uppercase;
            color: var(--text-muted);
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 15px;
            background-color: #222;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            color: var(--text-main);
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: var(--accent-blue);
        }

        .btn-submit {
            width: 100%;
            padding: 15px;
            background-color: var(--accent-blue);
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background-color 0.3s;
        }

        .btn-submit:hover {
            background-color: var(--accent-hover);
        }

        .alert-error {
            background-color: rgba(255, 68, 68, 0.1);
            border-left: 4px solid var(--error-red);
            color: var(--error-red);
            padding: 15px;
            margin-bottom: 25px;
            font-size: 0.9rem;
            border-radius: 2px;
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="login-header">
            <h1>REPUBLIK</h1>
            <p>Control Center</p>
        </div>

        <?php if($this->session->flashdata('error')): ?>
            <div class="alert-error">
                <?= $this->session->flashdata('error') ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('Auth/login') ?>" method="POST">
            <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required autocomplete="off" autofocus>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button type="submit" class="btn-submit">Sign In</button>
        </form>
    </div>

</body>
</html>
<!-- end file application/views/v_login.php -->

<!-- file assets/css/style.css -->
/* file: assets/css/style.css */
:root {
    --bg-color: #0B0B0B;
    --text-color: #ffffff;
    --text-muted: #cccccc;
    --accent-blue: #4A7AFF;
    --accent-blue-hover: #335ECC;
    --placeholder-gray: #333333;
    --placeholder-dark: #222222;
    --font-main: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

/* REVISI GLOBAL: Mengembalikan pergerakan halaman ke Standard Native Smooth Scroll */
html {
    scroll-behavior: smooth !important;
}

body { 
    background-color: var(--bg-color); 
    color: var(--text-color); 
    font-family: var(--font-main); 
    line-height: 1.6; 
    -webkit-font-smoothing: antialiased; 
    overflow-x: hidden;
}

.container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 40px 0; }

/* --- HERO & COLLAGE LAYOUT --- */
.hero-section { 
    width: 100%; 
    height: auto; 
    overflow: hidden; 
    display: flex;
    flex-direction: column;
}

.hero-collage-container { 
    position: relative; 
    width: 100%; 
    margin: 0 auto; 
    display: flex; 
    align-items: center; 
    justify-content: center;
}

.hero-logo-overlay { 
    position: absolute; 
    top: 50%; 
    left: 50%; 
    transform: translate(-50%, -50%); 
    z-index: 10; 
    pointer-events: none; 
}

.hero-logo-img {
    display: block;
    max-width: 700px; 
    height: auto;
    filter: drop-shadow(0 0 25px rgba(255, 255, 255, 0.2)); 
}

.hero-collage-grid { 
    display: grid; 
    grid-template-columns: 1.2fr 2fr 1fr 1.5fr; 
    height: 66vh; 
    grid-template-rows: repeat(10, 1fr); 
    gap: 0; 
    position: relative;
    width: 100%;
}

.hero-collage-grid::after {
    content: '';
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0, 0, 0, 0.45);
    z-index: 2; 
    pointer-events: none; 
}

.collage-cell { width: 100%; height: 100%; overflow: hidden; }
.collage-img { width: 100%; height: 100%; object-fit: cover; opacity: 0.8; }

.item-tall { grid-column: 1 / 2; grid-row: 1 / 11; }
.item-wide-top { grid-column: 2 / 3; grid-row: 1 / 7; }
.item-wide-bottom { grid-column: 2 / 3; grid-row: 7 / 11; }
.item-small-top { grid-column: 3 / 4; grid-row: 1 / 6; }
.item-small-bottom { grid-column: 3 / 4; grid-row: 6 / 11; }
.item-new-top-right { grid-column: 4 / 5; grid-row: 1 / 7; }
.item-new-bottom-right { grid-column: 4 / 5; grid-row: 7 / 11; }

/* --- TWO-LINE HEADLINE TIPOGRAFI --- */
.hero-text-wrapper {
    width: 100%;
    padding-top: 8vh; 
    padding-bottom: 2vh; 
    background-color: var(--bg-color);
}

.headline-container {
    text-align: center;
    margin: 0 auto;
    max-width: 1300px;
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.headline-top {
    font-size: clamp(1.6rem, 2.5vw, 2.2rem);
    font-weight: 500;
    letter-spacing: -0.01em;
    color: var(--text-color);
    opacity: 0.9;
}

.headline-bottom {
    font-size: clamp(2.2rem, 4.2vw, 4rem);
    font-weight: 800;
    letter-spacing: -0.03em;
    line-height: 1.1;
    color: var(--text-color);
}

.placeholder-gray { background-color: var(--placeholder-gray); }
.placeholder-dark { background-color: var(--placeholder-dark); }

/* --- NARRATIVE SECTION --- */
.narrative-section {
    padding: 0px 0 0px 0;
}
.narrative-content p { text-align: center; max-width: 850px; margin: 0 auto 30px auto; font-size: 1.2rem; color: var(--text-muted); line-height: 1.7; }
.methodology { text-align: center; margin-top: 60px; }
.methodology small { display: block; color: var(--text-muted); margin-bottom: 10px; font-size: 0.95rem; }
.methodology strong { font-size: 1.8rem; display: block; margin: 25px 0; color: var(--text-color); letter-spacing: 2px; }

/* --- PORTFOLIO GRID --- */
.portfolio-section { padding: 0px 0 80px 0; }
.section-title h3 { font-size: 2.5rem; text-align: center; margin-bottom: 20px; font-weight: 700; letter-spacing: -0.02em; }
.section-title p { text-align: center; max-width: 800px; margin: 0 auto 50px auto; color: var(--text-muted); font-size: 1.05rem; }

.portfolio-grid { 
    display: grid; 
    grid-template-columns: repeat(3, 1fr); 
    gap: 30px; 
    margin-top: 40px; 
}

.portfolio-item { 
    position: relative; 
    background: #222; 
    aspect-ratio: 16/9; 
    display: flex; 
    align-items: center; 
    justify-content: center; 
    cursor: pointer; 
    transition: 0.4s cubic-bezier(0.165, 0.84, 0.44, 1); 
    border: 1px solid #333; 
    overflow: hidden; 
}

.portfolio-item:hover { transform: translateY(-5px); border-color: var(--accent-blue); }
.play-icon { font-size: 3rem; opacity: 0.3; transition: 0.3s; z-index: 2; color: #fff; }
.portfolio-item:hover .play-icon { opacity: 1; color: var(--accent-blue); }
.overlay-text { position: absolute; bottom: 20px; left: 20px; font-weight: bold; font-size: 1.1rem; z-index: 3; color: #fff; text-transform: uppercase; letter-spacing: 1px; }

/* --- VIDEO MODAL --- */
.video-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; align-items: center; justify-content: center; }
.modal-overlay { position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.95); }
.modal-content { 
    position: relative; 
    width: 90%; 
    max-width: 1000px; 
    aspect-ratio: 16/9; 
    background: #000; 
    z-index: 10; 
    border: 1px solid #333; 
    border-radius: 8px;
    box-shadow: 0 20px 50px rgba(0,0,0,0.8);
}

.close-modal { 
    position: absolute; 
    top: -20px; 
    right: -50px; 
    color: #fff; 
    font-size: 2rem; 
    cursor: pointer; 
    line-height: 1; 
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.1);
    transition: all 0.3s ease;
    z-index: 11;
}
.close-modal:hover { background-color: var(--accent-blue); transform: scale(1.1); }

#videoContainer { width: 100%; height: 100%; border-radius: 8px; overflow: hidden; }
#videoContainer iframe, #videoContainer video { width: 100%; height: 100%; border: none; display: block; }

/* --- FORM GRID --- */
.form-section { padding: 0px 0; }
.form-container { max-width: 900px; padding-top: 0; }
.form-header h2 { font-size: 2.8rem; text-align: center; margin-bottom: 20px; font-weight: 700; letter-spacing: -0.02em; }
.form-header p { text-align: center; margin-bottom: 50px; color: var(--text-muted); font-size: 1.05rem; }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px 40px; }
.textarea-group { grid-column: 2; grid-row: 3 / span 2; }
label { font-size: 0.9rem; margin-bottom: 10px; color: var(--text-color); font-weight: bold; text-transform: uppercase; }
input, textarea { width: 100%; padding: 15px; background-color: #ffffff; border: none; border-radius: 2px; color: #000000; font-family: var(--font-main); font-size: 1rem; }
textarea { height: 80%; min-height: 160px; resize: none; }

.form-submit { text-align: center; margin-top: 50px; }
button#btnSubmit { background-color: var(--accent-blue); color: #ffffff; border: none; padding: 18px 50px; font-size: 1.1rem; font-weight: bold; border-radius: 40px; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; }
button#btnSubmit:hover { background-color: var(--accent-blue-hover); transform: translateY(-2px); }

/* --- FLOATING ACTION BUTTON (FAB) --- */
.fab-container { position: fixed; bottom: 40px; right: 40px; z-index: 1000; display: flex; flex-direction: column; align-items: center; }
.fab-menu { display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px; opacity: 0; visibility: hidden; transform: translateY(20px); transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
.fab-menu.active { opacity: 1; visibility: visible; transform: translateY(0); }
.fab-item { position: relative; width: 50px; height: 50px; border-radius: 50%; background-color: #1a1a1a; border: 1px solid #333; color: var(--text-color); display: flex; justify-content: center; align-items: center; text-decoration: none; box-shadow: 0 4px 15px rgba(0,0,0,0.5); transition: all 0.3s ease; }
.fab-item:hover { background-color: var(--accent-blue); transform: scale(1.1); border-color: var(--accent-blue); }
.fab-tooltip { position: absolute; right: 65px; background-color: #111; color: #fff; padding: 6px 12px; border-radius: 4px; font-size: 0.85rem; font-weight: bold; white-space: nowrap; opacity: 0; visibility: hidden; transform: translateX(10px); transition: all 0.3s ease; border: 1px solid #333; pointer-events: none; }
.fab-item:hover .fab-tooltip { opacity: 1; visibility: visible; transform: translateX(0); }
.fab-trigger { width: 65px; height: 65px; border-radius: 50%; background-color: #111; color: var(--text-color); border: 1px solid #444; display: flex; justify-content: center; align-items: center; cursor: pointer; box-shadow: 0 10px 25px rgba(0,0,0,0.8); transition: all 0.3s ease; }
.fab-trigger:hover { background-color: #222; transform: scale(1.05); }
.fab-trigger.active { background-color: var(--accent-blue); border-color: var(--accent-blue); transform: rotate(90deg); }

/* --- BACK TO TOP BUTTON --- */
.btn-back-to-top { position: fixed; bottom: 50px; right: 120px; width: 45px; height: 45px; border-radius: 50%; background-color: #111; color: var(--text-color); border: 1px solid #444; display: flex; justify-content: center; align-items: center; cursor: pointer; box-shadow: 0 10px 25px rgba(0,0,0,0.8); transition: all 0.3s ease; opacity: 0; visibility: hidden; z-index: 1001; }
.btn-back-to-top.show { opacity: 1; visibility: visible; }
.btn-back-to-top:hover { background-color: var(--accent-blue); border-color: var(--accent-blue); transform: translateY(-3px); }

/* --- FOOTER --- */
footer { text-align: center; padding: 40px 0; border-top: 1px solid #222; }
footer p { font-size: 1rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
.footer-logo { margin-top: 40px; }
.footer-logo h2 { font-size: 1.8rem; letter-spacing: 3px; font-weight: 900; }
.footer-logo p { font-size: 0.85rem; font-weight: 400; letter-spacing: 1px; }

/* ==========================================================================
   RESPONSIVE DESIGN (MEDIA QUERIES)
   ========================================================================= */

@media (max-width: 1024px) {
    .hero-collage-grid { 
        grid-template-columns: 1fr 1fr; 
        height: 65vh;
        grid-template-rows: repeat(4, 1fr); 
    }
    .item-tall { grid-column: 1 / 2; grid-row: 1 / 3; }
    .item-wide-top { grid-column: 2 / 3; grid-row: 1 / 2; }
    .item-small-top { grid-column: 2 / 3; grid-row: 2 / 3; }
    .item-new-top-right { grid-column: 1 / 2; grid-row: 3 / 4; }
    .item-wide-bottom { grid-column: 2 / 3; grid-row: 3 / 4; }
    .item-small-bottom { grid-column: 1 / 2; grid-row: 4 / 5; }
    .item-new-bottom-right { grid-column: 2 / 3; grid-row: 4 / 5; }

    .portfolio-grid { grid-template-columns: repeat(2, 1fr); gap: 20px; }
    .form-grid { gap: 20px; }
}

@media (max-width: 768px) {
    /* REVISI SCATTERED MOODBOARD MOBILE */
    .hero-collage-container {
        height: 65vh;
        background-color: var(--bg-color);
        position: relative;
    }

    .hero-collage-grid {
        display: block !important; 
        position: absolute;
        top: 0; left: 0;
        width: 100%; height: 100%;
        z-index: 1; 
    }
    
    /* REVISI UTAMA: Overlay diperpanjang ke bawah dan samping untuk menutup lubang */
    .hero-collage-grid::after {
        background: rgba(0, 0, 0, 0.75); 
        bottom: -15% !important; /* Memanjang ke bawah melewati batas wadah */
        left: -15% !important;   /* Memlebar ke kiri untuk rotasi gambar */
        right: -15% !important;  /* Memlebar ke kanan untuk rotasi gambar */
        top: -5% !important;    /* Sedikit ke atas untuk rotasi gambar */
    }

    .narrative-section {
        padding: 0px 0 0px 0;
    }

    .collage-cell { position: absolute !important; height: auto !important; z-index: 1; border-radius: 6px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.8); }
    .collage-img { border-radius: 6px; }

    .item-tall            { top: 5%; left: -5%; width: 45%; height: 35% !important; transform: rotate(-8deg); }
    .item-wide-top        { top: 12%; right: -10%; width: 60%; height: 25% !important; transform: rotate(5deg); }
    .item-small-top       { top: 40%; left: -15%; width: 50%; height: 22% !important; transform: rotate(-12deg); }
    .item-new-top-right   { top: 42%; right: 2%; width: 45%; height: 28% !important; transform: rotate(7deg); }
    .item-wide-bottom     { bottom: 15%; left: 10%; width: 55%; height: 25% !important; transform: rotate(-5deg); }
    .item-small-bottom    { bottom: 2%; right: -5%; width: 50%; height: 24% !important; transform: rotate(10deg); }
    
    /* Gambar ini paling bawah dan sering bocor */
    .item-new-bottom-right{ bottom: -10%; left: 35%; width: 40%; height: 25% !important; transform: rotate(-4deg); }

    .hero-logo-overlay { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; width: 85%; }
    .hero-logo-img { max-width: 260px; width: 100%; margin: 0 auto; filter: drop-shadow(0 0 25px rgba(255,255,255,0.15)); }

    .hero-text-wrapper {
        padding-top: 15vh;
        padding-bottom: 2vh;
    }
    .headline-container { gap: 6px; padding: 0 15px; }
    .headline-top { font-size: clamp(1.4rem, 2.2vw, 1.8rem); }

    .portfolio-grid { grid-template-columns: 1fr; }
    
    .form-section { padding: 60px 0; }
    .form-grid { grid-template-columns: 1fr; }
    .textarea-group { grid-column: 1 / -1; grid-row: auto; }
    input, textarea { padding: 14px 15px; }
    .close-modal { top: -50px; right: 0; width: 40px; height: 40px; font-size: 1.5rem; }
    
    .fab-container { bottom: 20px; right: 20px; }
    .fab-trigger { width: 55px; height: 55px; }
    .fab-item { width: 45px; height: 45px; }
    .fab-tooltip { display: none; }
    .btn-back-to-top { bottom: 25px; right: 90px; width: 45px; height: 45px; }
}
<!-- end file assets/css/style.css -->

<!-- file assets/js/main.js -->
/* file: assets/js/main.js */
document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // 1. VIDEO MODAL ENGINE
    // ==========================================
    const modal = document.getElementById('videoModal');
    const container = document.getElementById('videoContainer');
    const triggers = document.querySelectorAll('.video-trigger');
    const closeBtn = document.querySelector('.close-modal');
    const overlay = document.querySelector('.modal-overlay');

    triggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const videoSrc = this.getAttribute('data-video-src');
            let content = '';

            if (videoSrc.includes('youtube.com') || videoSrc.includes('youtu.be')) {
                const videoId = videoSrc.split('v=')[1] || videoSrc.split('/').pop();
                content = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
            } else {
                content = `<video controls autoplay><source src="${videoSrc}" type="video/mp4"></video>`;
            }

            container.innerHTML = content;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; 
        });
    });

    const closeModal = () => {
        modal.style.display = 'none';
        container.innerHTML = ''; 
        document.body.style.overflow = 'auto';
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', closeModal);

    // ==========================================
    // 2. AJAX FORM SUBMISSION
    // ==========================================
    const briefForm = document.getElementById('briefForm');
    const btnSubmit = document.getElementById('btnSubmit');

    if (briefForm) {
        briefForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const originalText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = 'Sending...';
            btnSubmit.disabled = true;

            fetch('api/submit_brief', {
                method: 'POST',
                body: new FormData(briefForm),
                credentials: 'same-origin'
            })
            .then(res => {
                if (!res.ok) throw new Error('Server Error');
                return res.json();
            })
            .then(data => {
                if (data.status) {
                    briefForm.style.display = 'none';
                    const success = document.createElement('div');
                    success.innerHTML = `<div style="text-align:center; padding:60px; border:1px solid var(--accent-blue); background:#111; border-radius:8px;">
                        <h3 style="color:var(--accent-blue); font-size:1.8rem; margin-bottom:10px;">Brief Received</h3>
                        <p>${data.message}</p>
                    </div>`;
                    briefForm.parentNode.appendChild(success);
                } else {
                    alert('Please check your input fields.');
                    btnSubmit.innerHTML = originalText;
                    btnSubmit.disabled = false;
                    if (data.csrf_token) document.querySelector('input[type="hidden"]').value = data.csrf_token;
                }
            })
            .catch(() => {
                alert('Connection error. Please try again.');
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
            });
        });
    }

    // ==========================================
    // 3. FLOATING ACTION BUTTON (FAB) INTERACTION
    // ==========================================
    const fabTrigger = document.getElementById('fabTrigger');
    const fabMenu = document.getElementById('fabMenu');
    const iconChat = document.querySelector('.fab-icon-chat');
    const iconClose = document.querySelector('.fab-icon-close');

    if (fabTrigger && fabMenu) {
        fabTrigger.addEventListener('click', function() {
            fabTrigger.classList.toggle('active');
            fabMenu.classList.toggle('active');

            if (fabTrigger.classList.contains('active')) {
                iconChat.style.display = 'none';
                iconClose.style.display = 'block';
            } else {
                iconChat.style.display = 'block';
                iconClose.style.display = 'none';
            }
        });

        document.addEventListener('click', function(event) {
            const isClickInside = fabTrigger.contains(event.target) || fabMenu.contains(event.target);
            if (!isClickInside && fabMenu.classList.contains('active')) {
                fabTrigger.classList.remove('active');
                fabMenu.classList.remove('active');
                iconChat.style.display = 'block';
                iconClose.style.display = 'none';
            }
        });
    }

    // ==========================================
    // 4. BACK TO TOP BUTTON LOGIC
    // ==========================================
    const btnBackToTop = document.getElementById('btnBackToTop');
    if (btnBackToTop) {
        window.addEventListener('scroll', function() {
            let scrollPosition = window.scrollY || document.documentElement.scrollTop;
            if (scrollPosition > 400) {
                btnBackToTop.classList.add('show');
            } else {
                btnBackToTop.classList.remove('show');
            }
        });

        btnBackToTop.addEventListener('click', function(e) {
            e.preventDefault(); 
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    }

});
<!-- end file assets/js/main.js -->

<!-- file database/db_republik_landing.php -->
CREATE TABLE `tb_leads` (
    `id_lead` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    `first_name` VARCHAR(100) NULL DEFAULT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL,
    `organization` VARCHAR(150) NOT NULL,
    `position` VARCHAR(100) NOT NULL,
    `country` VARCHAR(100) NULL DEFAULT NULL,
    `messages` TEXT NOT NULL,
    `ip_address` VARCHAR(45) NULL DEFAULT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_lead`),
    INDEX `idx_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
<!-- end file database/db_republik_landing.php -->