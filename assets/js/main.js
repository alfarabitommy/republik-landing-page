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