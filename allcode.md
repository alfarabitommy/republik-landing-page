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
                    <img src="<?= base_url('assets/img/hero-1-placeholder.webp') ?>" alt="Collage 1" class="collage-img" loading="lazy">
                </div>
                <div class="collage-cell item-wide-top placeholder-gray">
                    <img src="<?= base_url('assets/img/hero-2-placeholder.webp') ?>" alt="Collage 2" class="collage-img" loading="lazy">
                </div>
                <div class="collage-cell item-small-top placeholder-dark">
                    <img src="<?= base_url('assets/img/hero-3-placeholder.webp') ?>" alt="Collage 3" class="collage-img" loading="lazy">
                </div>
                <div class="collage-cell item-wide-bottom placeholder-gray">
                    <img src="<?= base_url('assets/img/hero-4-placeholder.webp') ?>" alt="Collage 4" class="collage-img" loading="lazy">
                </div>
                <div class="collage-cell item-small-bottom placeholder-dark">
                    <img src="<?= base_url('assets/img/hero-5-placeholder.webp') ?>" alt="Collage 5" class="collage-img" loading="lazy">
                </div>
            </div>
        </div>

        <div class="container">
            <h2 class="headline-utama">
                Your brand doesn't<br>
                need more content. It needs<br>
                <span class="bottom">a sharper creative system.</span>
            </h2>
        </div>
    </header>

    <section class="narrative-section">
        <div class="container">
            <p>REPUBLIK helps brands turn business problems into culture-sharp creative platforms, social campaigns, content systems & performance-ready ideas. We work where attention is crowded, audiences are restless, and brands need more than "posting consistently."</p>
            <p>We help you find the strategic angle, shape the creative idea, build the format system & make every touchpoint do its job.</p>
            <p class="methodology">
                <small>From big campaign thinking to daily content execution, we connect:</small><br>
                <strong>Idea &rarr; Format &rarr; Behavior &rarr; Measurement</strong><br>
                <small>So your brand doesn't just show up. It gets noticed, remembered, and acted on.</small>
            </p>
        </div>
    </section>

    <section id="work" class="portfolio-section">
        <div class="container">
            <div class="section-title">
                <h3>Our Work</h3>
                <p>A selection of work across FMCG, beauty, automotive, lifestyle, and youth culture. Each one built to solve a real brand challenge through sharp strategy, strong creative and formats that move across platforms.</p>
            </div>
            
            <div id="portfolio-grid" class="portfolio-grid">
                <div class="portfolio-item placeholder-brown">
                    <img src="<?= base_url('assets/img/honda.webp') ?>" alt="HONDA AHM" loading="lazy">
                    <div class="overlay-text">HONDA AHM</div>
                </div>
                <div class="portfolio-item placeholder-brown">
                    <img src="<?= base_url('assets/img/honda.webp') ?>" alt="HONDA AHM" loading="lazy">
                    <div class="overlay-text">HONDA AHM</div>
                </div>
                <div class="portfolio-item placeholder-brown">
                    <img src="<?= base_url('assets/img/honda.webp') ?>" alt="HONDA AHM" loading="lazy">
                    <div class="overlay-text">HONDA AHM</div>
                </div>
                <div class="portfolio-item placeholder-brown">
                    <img src="<?= base_url('assets/img/honda.webp') ?>" alt="HONDA AHM" loading="lazy">
                    <div class="overlay-text">HONDA AHM</div>
                </div>
                <div class="portfolio-item placeholder-brown">
                    <img src="<?= base_url('assets/img/jergens.webp') ?>" alt="JERGENS" loading="lazy">
                    <div class="overlay-text">JERGENS</div>
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
                        <label for="email">email*</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="form-group">
                        <label for="organization">Organization*</label>
                        <input type="text" id="organization" name="organization" required>
                    </div>
                    <div class="form-group">
                        <label for="position">Position*</label>
                        <input type="text" id="position" name="position" required>
                    </div>

                    <div class="form-group">
                        <label for="last_name">Last Name*</label>
                        <input type="text" id="last_name" name="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="country">Country*</label>
                        <input type="text" id="country" name="country">
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
/* ==========================================================================
   CSS Reset & Base Styles (DRY Principle)
   ========================================================================== */
:root {
    --bg-color: #0B0B0B; /* Sedikit lebih pekat mendekati desain */
    --text-color: #ffffff;
    --text-muted: #cccccc;
    --accent-blue: #4A7AFF;
    --accent-blue-hover: #335ECC;
    --placeholder-brown: #8C4E3A;
    --placeholder-gray: #333333;
    --placeholder-dark: #222222;
    --font-main: 'Helvetica Neue', Helvetica, Arial, sans-serif;
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    background-color: var(--bg-color);
    color: var(--text-color);
    font-family: var(--font-main);
    line-height: 1.6;
    -webkit-font-smoothing: antialiased;
}

.container {
    width: 90%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 40px 0;
}

/* ==========================================================================
   Hero Section & Collage Grid
   ========================================================================== */
.hero-section {
    width: 100%;
    overflow: hidden;
}

.hero-collage-container {
    position: relative;
    width: 100%;
    max-width: 1600px;
    margin: 0 auto;
}

/* Overlay Logo di Tengah Kolase */
.hero-logo-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    pointer-events: none; /* Agar klik tembus ke gambar jika diperlukan */
}

/* Styling untuk Placeholder Logo sementara */
.logo-placeholder-circle {
    background-color: transparent;
    text-align: center;
    text-shadow: 2px 2px 10px rgba(0,0,0,0.8);
}
.logo-placeholder-circle h1 {
    font-size: clamp(3rem, 6vw, 6rem);
    font-weight: 900;
    letter-spacing: -2px;
    line-height: 1;
}
.logo-placeholder-circle p {
    font-size: clamp(1rem, 2vw, 1.5rem);
    font-weight: bold;
    letter-spacing: 1px;
}

/* CSS Grid untuk Kolase Asimetris */
.hero-collage-grid {
    display: grid;
    /* Rasio kolom: Kiri 30%, Tengah 45%, Kanan 25% */
    grid-template-columns: 1.2fr 2fr 1fr; 
    grid-template-rows: 300px 300px; /* Ketinggian baris */
    gap: 0; /* Merapat seperti pada desain */
}

.collage-cell {
    width: 100%;
    height: 100%;
    overflow: hidden;
}

.collage-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.8; /* Sedikit diredupkan jika tidak ada gambar asli */
}

/* Penempatan Posisi Item Kolase */
.item-tall { grid-column: 1 / 2; grid-row: 1 / 3; } /* Kiri penuh */
.item-wide-top { grid-column: 2 / 3; grid-row: 1 / 2; } /* Tengah Atas */
.item-small-top { grid-column: 3 / 4; grid-row: 1 / 2; } /* Kanan Atas */
.item-wide-bottom { grid-column: 2 / 3; grid-row: 2 / 3; } /* Tengah Bawah */
.item-small-bottom { grid-column: 3 / 4; grid-row: 2 / 3; } /* Kanan Bawah */

/* Warna Placeholder */
.placeholder-gray { background-color: var(--placeholder-gray); }
.placeholder-dark { background-color: var(--placeholder-dark); }

/* ==========================================================================
   Typography & Copywriting
   ========================================================================== */
h1, h2, h3 {
    font-weight: 700;
    line-height: 1.2;
}

/* Penyesuaian Headline agar bergeser secara proporsional sesuai desain */
.headline-utama {
    font-size: clamp(2rem, 4vw, 3.5rem); 
    text-align: center; /* Rata tengah namun diatur lebar box-nya */
    max-width: 850px;
    margin: 40px auto 0 0;
    letter-spacing: -0.03em;
    padding-right: 2%; /* Memberikan ilusi visual sedikit bergeser ke kiri */
}

.headline-utama .bottom {
    font-size: clamp(3rem, 5vw, 4.5rem); 
}

.narrative-section p, .section-title p {
    text-align: center;
    max-width: 800px;
    margin: 0 auto 20px auto;
    font-size: 1.1rem;
    color: var(--text-muted);
}

.methodology {
    margin-top: 40px !important;
}

.methodology strong {
    font-size: 1.5rem;
    display: block;
    margin: 15px 0;
    color: var(--text-color);
}

/* ==========================================================================
   Portfolio Grid Section
   ========================================================================== */
.section-title h3 {
    font-size: 2rem;
    text-align: center;
    margin-bottom: 15px;
}

#portfolio-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 30px;
    margin-top: 40px;
    justify-content: center;
}

.portfolio-item {
    position: relative;
    background-color: var(--placeholder-brown);
    aspect-ratio: 4 / 3;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    transition: transform 0.3s ease;
}

.portfolio-item:hover {
    transform: scale(1.02);
}

.portfolio-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0; 
}

.overlay-text {
    position: absolute;
    font-size: 1.2rem;
    font-weight: bold;
    letter-spacing: 1px;
}

.portfolio-item:nth-child(4) {
    grid-column: 1 / 3;
    justify-self: end;
    width: 66%;
}
.portfolio-item:nth-child(5) {
    grid-column: 2 / 4;
    justify-self: start;
    width: 66%;
}

/* ==========================================================================
   Form Section
   ========================================================================== */
.form-container {
    max-width: 900px;
}

.form-header h2 {
    font-size: 2.5rem;
    text-align: center;
    margin-bottom: 15px;
}

.form-header p {
    text-align: center;
    margin-bottom: 40px;
    color: var(--text-muted);
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px 40px;
}

.form-group {
    display: flex;
    flex-direction: column;
}

.textarea-group {
    grid-row: span 3;
}

label {
    font-size: 0.9rem;
    margin-bottom: 8px;
    color: var(--text-color);
}

input[type="text"],
input[type="email"],
textarea {
    width: 100%;
    padding: 12px;
    background-color: #ffffff;
    border: none;
    border-radius: 2px;
    color: #000000;
    font-family: var(--font-main);
    font-size: 1rem;
}

input:focus, textarea:focus {
    outline: 2px solid var(--accent-blue);
}

.form-submit {
    text-align: center;
    margin-top: 40px;
}

button#btnSubmit {
    background-color: var(--accent-blue);
    color: #ffffff;
    border: none;
    padding: 15px 40px;
    font-size: 1.1rem;
    font-weight: bold;
    border-radius: 30px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button#btnSubmit:hover {
    background-color: var(--accent-blue-hover);
}

button#btnSubmit:disabled {
    background-color: #555555;
    cursor: not-allowed;
}

/* ==========================================================================
   Footer
   ========================================================================== */
footer {
    text-align: center;
    padding: 60px 0;
    border-top: 1px solid #222;
}

footer p {
    font-size: 0.9rem;
    color: var(--text-muted);
    font-weight: bold;
}

.footer-logo {
    margin-top: 30px;
}

.footer-logo h2 {
    font-size: 1.5rem;
    letter-spacing: 2px;
}

.footer-logo p {
    font-size: 0.8rem;
    font-weight: normal;
}

/* ==========================================================================
   Media Queries (Mobile Responsiveness)
   ========================================================================== */
@media (max-width: 768px) {
    /* Merubah kolase menjadi 1 kolom yang ditumpuk di layar HP */
    .hero-collage-grid {
        grid-template-columns: 1fr;
        grid-template-rows: auto;
    }
    .collage-cell {
        grid-column: 1 / 2 !important;
        grid-row: auto !important;
        height: 200px;
    }

    #portfolio-grid {
        grid-template-columns: 1fr; 
    }
    
    .portfolio-item:nth-child(4),
    .portfolio-item:nth-child(5) {
        grid-column: 1 / -1;
        width: 100%;
    }

    .form-grid {
        grid-template-columns: 1fr; 
    }

    .textarea-group {
        grid-row: auto;
    }
}
<!-- end file assets/css/style.css -->

<!-- file assets/js/main.css -->
document.addEventListener('DOMContentLoaded', function() {
    // 1. Lazy Loading untuk Gambar Portofolio (Intersection Observer)
    const lazyImages = document.querySelectorAll('img[loading="lazy"]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    // Menambahkan class untuk animasi fade-in sederhana jika diperlukan
                    img.style.transition = "opacity 0.5s ease-in";
                    img.style.opacity = 1;
                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(function(img) {
            imageObserver.observe(img);
        });
    } else {
        // Fallback jika browser sangat jadul
        lazyImages.forEach(function(img) {
            img.style.opacity = 1;
        });
    }

    // 2. Logika Form Submission & AJAX
    const briefForm = document.getElementById('briefForm');
    const btnSubmit = document.getElementById('btnSubmit');

    if (briefForm) {
        briefForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Mencegah reload halaman

            // Ubah state tombol menjadi loading
            const originalBtnText = btnSubmit.innerHTML;
            btnSubmit.innerHTML = 'Sending...';
            btnSubmit.disabled = true;

            // Hapus pesan error sebelumnya (Prinsip DRY)
            clearErrors();

            // Kumpulkan data form secara otomatis
            const formData = new FormData(briefForm);

            // Eksekusi Fetch API ke endpoint CI3
            fetch('/republik/api/submit_brief', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Update CSRF Token dari server untuk keamanan request selanjutnya
                if (data.csrf_token) {
                    const csrfInput = document.querySelector('input[name="republik_csrf"]');
                    if (csrfInput) {
                        csrfInput.value = data.csrf_token;
                    }
                }

                if (data.status === false) {
                    // Jika validasi gagal atau terdeteksi spam
                    displayErrors(data.errors);
                    btnSubmit.innerHTML = originalBtnText;
                    btnSubmit.disabled = false;
                } else if (data.status === true) {
                    // Jika sukses menyimpan data
                    showSuccessMessage(data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Terjadi kesalahan pada server. Silakan coba beberapa saat lagi.');
                btnSubmit.innerHTML = originalBtnText;
                btnSubmit.disabled = false;
            });
        });
    }

    // --- Fungsi Bantuan (Helper Functions - DRY Principle) ---

    function displayErrors(errors) {
        for (const [field, message] of Object.entries(errors)) {
            // Jika error berasal dari input field
            const inputElement = document.getElementById(field);
            if (inputElement) {
                inputElement.style.outline = "2px solid #ff4444"; // Highlight merah
                
                // Buat elemen teks error
                const errorText = document.createElement('span');
                errorText.className = 'error-message';
                errorText.style.color = '#ff4444';
                errorText.style.fontSize = '0.8rem';
                errorText.style.marginTop = '5px';
                errorText.style.display = 'block';
                errorText.innerHTML = message;

                // Sisipkan di bawah input yang bermasalah
                inputElement.parentNode.appendChild(errorText);
            } else if (field === 'email' || field === 'server') {
                // Khusus untuk error duplikasi email / server logik
                alert(message);
            }
        }
    }

    function clearErrors() {
        const errorMessages = document.querySelectorAll('.error-message');
        errorMessages.forEach(el => el.remove());

        const errorInputs = document.querySelectorAll('input, textarea');
        errorInputs.forEach(el => {
            el.style.outline = "none";
        });
    }

    function showSuccessMessage(message) {
        // Sembunyikan form
        briefForm.style.display = 'none';

        // Buat dan tampilkan elemen sukses yang elegan
        const successDiv = document.createElement('div');
        successDiv.className = 'success-message';
        successDiv.style.textAlign = 'center';
        successDiv.style.padding = '40px 20px';
        successDiv.style.backgroundColor = '#111';
        successDiv.style.border = '1px solid #4A7AFF';
        successDiv.style.borderRadius = '8px';
        successDiv.style.marginTop = '20px';
        
        successDiv.innerHTML = `
            <h3 style="color: #4A7AFF; margin-bottom: 10px; font-size: 1.5rem;">Brief Received</h3>
            <p style="color: #fff;">${message}</p>
        `;

        // Sisipkan di tempat form sebelumnya berada
        const formContainer = document.querySelector('.form-container');
        formContainer.appendChild(successDiv);
    }
});
<!-- end file assets/js/main.css -->

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