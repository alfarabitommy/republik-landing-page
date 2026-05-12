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
}
<!-- end file application/models/Leads_model.php -->

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
                        <label for="first_name">First Name*</label>
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
                        <label for="country">Country*</label>
                        <input type="text" id="country" name="country">
                    </div>

                    <div class="form-group">
                        <label for="organization">Organization*</label>
                        <input type="text" id="organization" name="organization" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="position">Position*</label>
                        <input type="text" id="position" name="position" required>
                    </div>
                    
                    <div class="form-group textarea-group">
                        <label for="messages">Messages*</label>
                        <textarea id="messages" name="messages" rows="6" required></textarea>
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
.headline-utama { font-size: clamp(2rem, 4vw, 3.5rem); text-align: center; max-width: 1200px; margin: 60px auto 0 auto; letter-spacing: -0.03em; padding-right: 2%; font-weight: 700; line-height: 1.2; }

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

/* --- CONTACT FORM --- */
.form-container { max-width: 900px; padding-top: 100px; }
.form-header h2 { font-size: 2.8rem; text-align: center; margin-bottom: 20px; font-weight: 700; }
.form-header p { text-align: center; margin-bottom: 50px; color: var(--text-muted); }
.form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px 40px; }
.form-group { display: flex; flex-direction: column; }
.textarea-group { grid-row: span 3; }
label { font-size: 0.9rem; margin-bottom: 10px; color: var(--text-color); font-weight: bold; text-transform: uppercase; }
input, textarea { width: 100%; padding: 15px; background-color: #ffffff; border: none; border-radius: 2px; color: #000000; font-family: var(--font-main); font-size: 1rem; }
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
    .portfolio-item { grid-column: auto !important; }
    .textarea-group { grid-row: auto; }
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