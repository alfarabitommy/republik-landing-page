<!-- file application/controllers/Admin.php -->
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
                <a href="#" class="nav-link">Settings</a>
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
            // Inisialisasi Vanilla JS DataTables
            const myTable = document.getElementById("leadsTable");
            if (myTable) {
                new simpleDatatables.DataTable(myTable, {
                    searchable: true,
                    fixedHeight: true,
                    perPage: 10
                });
            }
        });

        // Fungsi AJAX murni Vanilla JS (Sesuai Aturan Blueprint)
        function updateLeadStatus(selectElement, idLead) {
            const newStatus = selectElement.value;
            const csrfInput = document.getElementById('csrf_token');
            const csrfName = csrfInput.getAttribute('name');
            const csrfValue = csrfInput.value;

            // Membangun FormData
            const formData = new FormData();
            formData.append('id_lead', idLead);
            formData.append('status', newStatus);
            formData.append(csrfName, csrfValue);

            // Menonaktifkan select sementara saat proses
            selectElement.disabled = true;

            // Eksekusi Fetch API
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
                selectElement.disabled = false; // Aktifkan kembali
                if(data.status) {
                    // Update CSRF token di DOM dengan yang baru dari server
                    csrfInput.value = data.csrf_token;
                    
                    // Tampilkan Toast Sukses
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

        // Fungsi kontrol animasi Toast Minimalis
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

<!-- file application/views/v_landing.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REPUBLIK | Creative Intelligence Agency</title>
    <meta name="description" content="Your brand doesn't need more content. It needs a sharper creative system.">
    <link rel="stylesheet" href="<?= base_url('assets/css/style.css') ?>">
</head>
<body>

    <header class="hero-section">
        <div class="hero-collage-container">
            <div class="hero-logo-overlay">
                <div class="logo-placeholder-circle">
                    <h1>REPUBLIK</h1>
                    <p>Creative Intelligence Agency</p>
                </div>
            </div>

            <div class="hero-collage-grid">
                <div class="collage-cell item-tall placeholder-dark">
                    <img src="<?= base_url('assets/img/college1.png') ?>" alt="Collage 1" class="collage-img" loading="lazy">
                </div>
                <div class="collage-cell item-wide-top placeholder-gray">
                    <img src="<?= base_url('assets/img/college2.png') ?>" alt="Collage 2" class="collage-img" loading="lazy">
                </div>
                <div class="collage-cell item-small-top placeholder-dark">
                    <img src="<?= base_url('assets/img/college3.png') ?>" alt="Collage 3" class="collage-img" loading="lazy">
                </div>
                <div class="collage-cell item-wide-bottom placeholder-gray">
                    <img src="<?= base_url('assets/img/college4.png') ?>" alt="Collage 4" class="collage-img" loading="lazy">
                </div>
                <div class="collage-cell item-small-bottom placeholder-dark">
                    <img src="<?= base_url('assets/img/college5.png') ?>" alt="Collage 5" class="collage-img" loading="lazy">
                </div>
            </div>
        </div>

        <div class="container">
            <h2 class="headline-utama">
                Your brand doesn't
                need more content. It needs<br>
                a sharper creative system.
            </h2>
        </div>
    </header>

    <section class="narrative-section">
        <div class="container">
            <p>REPUBLIK helps brands turn business problems into culture-sharp creative platforms, social campaigns, content systems & performance-ready ideas. We work where attention is crowded, audiences are restless, and brands need more than "posting consistently."</p>
            <p>We help you find the strategic angle, shape the creative idea, build the format system & make every touchpoint do its job.</p>
            <div class="methodology">
                <small>From big campaign thinking to daily content execution, we connect:</small>
                <strong>Idea &rarr; Format &rarr; Behavior &rarr; Measurement</strong>
                <small>So your brand doesn't just show up. It gets noticed, remembered, and acted on.</small>
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
                <div class="portfolio-item video-trigger" data-video-src="https://www.youtube.com/watch?v=dQw4w9WgXcQ">
                    <div class="overlay-text">HONDA AHM</div>
                    <div class="play-icon">▶</div>
                </div>
                <div class="portfolio-item video-trigger" data-video-src="https://www.youtube.com/watch?v=dQw4w9WgXcQ">
                    <div class="overlay-text">JERGENS</div>
                    <div class="play-icon">▶</div>
                </div>
                <div class="portfolio-item video-trigger" data-video-src="https://www.youtube.com/watch?v=dQw4w9WgXcQ">
                    <div class="overlay-text">HONDA AHM</div>
                    <div class="play-icon">▶</div>
                </div>
                <div class="portfolio-item video-trigger" data-video-src="<?= base_url('assets/video/honda.mp4') ?>">
                    <div class="overlay-text">HONDA AHM</div>
                    <div class="play-icon">▶</div>
                </div>
                <div class="portfolio-item video-trigger" data-video-src="https://www.youtube.com/watch?v=dQw4w9WgXcQ">
                    <div class="overlay-text">JERGENS</div>
                    <div class="play-icon">▶</div>
                </div>
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
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name*</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>

                    <div class="form-group">
                        <label for="email">email*</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="country">Country</label>
                        <input type="text" id="country" name="country">
                    </div>

                    <div class="form-group">
                        <label for="organization">Organization*</label>
                        <input type="text" id="organization" name="organization" required>
                    </div>
                    
                    <div class="form-group textarea-group">
                        <label for="messages">Messages*</label>
                        <textarea id="messages" name="messages" rows="6" required></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position*</label>
                        <input type="text" id="position" name="position" required>
                    </div>
                </div>

                <div class="form-submit">
                    <button type="submit" id="btnSubmit">Send Brief</button>
                </div>
            </form>
        </div>
    </section>

    <div id="videoModal" class="video-modal">
        <div class="modal-overlay"></div>
        <div class="modal-content">
            <span class="close-modal">&times;</span>
            <div id="videoContainer"></div>
        </div>
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

    <script src="<?= base_url('assets/js/main.js') ?>"></script>
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
body { background-color: var(--bg-color); color: var(--text-color); font-family: var(--font-main); line-height: 1.6; -webkit-font-smoothing: antialiased; }
.container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 40px 0; }

/* --- HERO & COLLAGE --- */
.hero-section { width: 100%; overflow: hidden; }
.hero-collage-container { position: relative; width: 100%; max-width: 1600px; margin: 0 auto; }
.hero-logo-overlay { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); z-index: 10; pointer-events: none; }
.logo-placeholder-circle h1 { font-size: clamp(3rem, 6vw, 6rem); font-weight: 900; letter-spacing: -2px; line-height: 1; }
.logo-placeholder-circle p { font-size: clamp(0.8rem, 1.5vw, 1.2rem); font-weight: bold; letter-spacing: 1px; }
.hero-collage-grid { display: grid; grid-template-columns: 1.2fr 2fr 1fr; grid-template-rows: 300px 300px; gap: 0; }
.collage-cell { width: 100%; height: 100%; overflow: hidden; }
.collage-img { width: 100%; height: 100%; object-fit: cover; opacity: 0.8; }
.item-tall { grid-column: 1 / 2; grid-row: 1 / 3; }
.item-wide-top { grid-column: 2 / 3; grid-row: 1 / 2; }
.item-small-top { grid-column: 3 / 4; grid-row: 1 / 2; }
.item-wide-bottom { grid-column: 2 / 3; grid-row: 2 / 3; }
.item-small-bottom { grid-column: 3 / 4; grid-row: 2 / 3; }
.placeholder-gray { background-color: var(--placeholder-gray); }
.placeholder-dark { background-color: var(--placeholder-dark); }
.headline-utama { font-size: clamp(2rem, 4vw, 3.5rem); text-align: center; max-width: 1200px; margin: 60px auto 0 auto; letter-spacing: -0.03em; font-weight: 700; line-height: 1.2; }

/* --- NARRATIVE --- */
.narrative-section p { text-align: center; max-width: 800px; margin: 0 auto 30px auto; font-size: 1.15rem; color: var(--text-muted); }
.methodology { text-align: center; margin-top: 50px; }
.methodology small { display: block; color: var(--text-muted); margin-bottom: 10px; }
.methodology strong { font-size: 1.8rem; display: block; margin: 20px 0; color: var(--text-color); letter-spacing: 2px; }

/* --- PORTFOLIO 3-2 GRID --- */
.portfolio-section { padding-top: 80px; }
.section-title h3 { font-size: 2.2rem; text-align: center; margin-bottom: 20px; font-weight: 700; }
.section-title p { text-align: center; max-width: 800px; margin: 0 auto 50px auto; color: var(--text-muted); }
#portfolio-grid { display: grid; grid-template-columns: repeat(6, 1fr); gap: 30px; margin-top: 40px; }
.portfolio-item { grid-column: span 2; position: relative; background: #222; aspect-ratio: 16/9; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: 0.3s; border: 1px solid #333; overflow: hidden; }
.portfolio-item:nth-child(4) { grid-column: 2 / 4; }
.portfolio-item:nth-child(5) { grid-column: 4 / 6; }
.portfolio-item:hover { transform: scale(1.02); border-color: var(--accent-blue); }
.play-icon { font-size: 3rem; opacity: 0.4; transition: 0.3s; z-index: 2; color: #fff; }
.portfolio-item:hover .play-icon { opacity: 1; color: var(--accent-blue); }
.overlay-text { position: absolute; bottom: 20px; left: 20px; font-weight: bold; font-size: 1rem; z-index: 3; color: #fff; text-transform: uppercase; }

/* --- VIDEO MODAL --- */
.video-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; z-index: 9999; align-items: center; justify-content: center; }
.modal-overlay { position: absolute; width: 100%; height: 100%; background: rgba(0,0,0,0.95); }
.modal-content { position: relative; width: 85%; max-width: 1050px; aspect-ratio: 16/9; background: #000; z-index: 10; border: 1px solid #333; }
.close-modal { position: absolute; top: -50px; right: 0; color: #fff; font-size: 3rem; cursor: pointer; }
#videoContainer iframe, #videoContainer video { width: 100%; height: 100%; border: none; }

/* --- UPDATED FORM GRID --- */
.form-container { max-width: 900px; padding-top: 100px; }
.form-header h2 { font-size: 2.8rem; text-align: center; margin-bottom: 20px; font-weight: 700; }
.form-header p { text-align: center; margin-bottom: 50px; color: var(--text-muted); }

.form-grid { 
    display: grid; 
    grid-template-columns: 1fr 1fr; 
    gap: 25px 40px; 
}

/* Kolom Kanan (Messages) memanjang dari baris 3 ke baris 4 */
.textarea-group { 
    grid-column: 2;
    grid-row: 3 / span 2; 
}

label { font-size: 0.9rem; margin-bottom: 10px; color: var(--text-color); font-weight: bold; text-transform: uppercase; }
input, textarea { width: 100%; padding: 15px; background-color: #ffffff; border: none; border-radius: 2px; color: #000000; font-family: var(--font-main); font-size: 1rem; }
textarea { height: 80%; min-height: 160px; resize: none; }

.form-submit { text-align: center; margin-top: 50px; }
button#btnSubmit { background-color: var(--accent-blue); color: #ffffff; border: none; padding: 18px 50px; font-size: 1.1rem; font-weight: bold; border-radius: 40px; cursor: pointer; transition: 0.3s; text-transform: uppercase; letter-spacing: 1px; }
button#btnSubmit:hover { background-color: var(--accent-blue-hover); transform: translateY(-2px); }

/* --- FOOTER --- */
footer { text-align: center; padding: 80px 0; border-top: 1px solid #222; margin-top: 100px; }
footer p { font-size: 1rem; color: var(--text-muted); font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
.footer-logo { margin-top: 40px; }
.footer-logo h2 { font-size: 1.8rem; letter-spacing: 3px; font-weight: 900; }
.footer-logo p { font-size: 0.85rem; font-weight: 400; letter-spacing: 1px; }

@media (max-width: 768px) {
    .hero-collage-grid { grid-template-columns: 1fr; grid-template-rows: auto; }
    .collage-cell { height: 200px; grid-column: auto !important; grid-row: auto !important; }
    .form-grid, #portfolio-grid { grid-template-columns: 1fr; }
    .textarea-group { grid-column: auto; grid-row: auto; }
    .portfolio-item { grid-column: auto !important; }
}
<!-- end file assets/css/style.css -->

<!-- file assets/js/main.js -->
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. VIDEO MODAL ENGINE
    const modal = document.getElementById('videoModal');
    const container = document.getElementById('videoContainer');
    const triggers = document.querySelectorAll('.video-trigger');
    const closeBtn = document.querySelector('.close-modal');
    const overlay = document.querySelector('.modal-overlay');

    triggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const videoSrc = this.getAttribute('data-video-src');
            let content = '';

            // Detect YouTube vs Local Video
            if (videoSrc.includes('youtube.com') || videoSrc.includes('youtu.be')) {
                const videoId = videoSrc.split('v=')[1] || videoSrc.split('/').pop();
                content = `<iframe src="https://www.youtube.com/embed/${videoId}?autoplay=1" allow="autoplay; encrypted-media" allowfullscreen></iframe>`;
            } else {
                content = `<video controls autoplay><source src="${videoSrc}" type="video/mp4"></video>`;
            }

            container.innerHTML = content;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Disable background scroll
        });
    });

    const closeModal = () => {
        modal.style.display = 'none';
        container.innerHTML = ''; // Kill video process
        document.body.style.overflow = 'auto';
    };

    if (closeBtn) closeBtn.addEventListener('click', closeModal);
    if (overlay) overlay.addEventListener('click', closeModal);

    // 2. AJAX FORM SUBMISSION
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
                    // Success State
                    briefForm.style.display = 'none';
                    const success = document.createElement('div');
                    success.innerHTML = `<div style="text-align:center; padding:60px; border:1px solid var(--accent-blue); background:#111; border-radius:8px;">
                        <h3 style="color:var(--accent-blue); font-size:1.8rem; margin-bottom:10px;">Brief Received</h3>
                        <p>${data.message}</p>
                    </div>`;
                    briefForm.parentNode.appendChild(success);
                } else {
                    // Error State
                    alert('Please check your input fields.');
                    btnSubmit.innerHTML = originalText;
                    btnSubmit.disabled = false;
                    // Update CSRF token if provided
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