/* file: assets/js/main.js */
document.addEventListener('DOMContentLoaded', function() {
    
    const isMobile = window.innerWidth <= 768;
    const sections = document.querySelectorAll('.snap-section');
    let currentSectionIndex = 0;
    let isScrolling = false;
    let lastScrollTime = 0; // Buffer untuk Trackpad / Magic Mouse
    const btnBackToTop = document.getElementById('btnBackToTop');

    // ==========================================
    // 1. ENGINE CUSTOM SMOOTH SCROLL (DESKTOP)
    // ==========================================
    
    // Fungsi animasi saya keluarkan ke global scope agar Back to Top bisa meminjam animasinya
    function smoothScrollTo(targetPosition, duration) {
        const startPosition = window.scrollY || document.documentElement.scrollTop;
        const distance = targetPosition - startPosition;
        let startTime = null;

        function animation(currentTime) {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startPosition;
            const run = easeInOutQuad(currentTime - startTime, startPosition, distance, duration);
            window.scrollTo(0, run);
            
            if (currentTime - startTime < duration) {
                requestAnimationFrame(animation);
            } else {
                window.scrollTo(0, targetPosition); // Pastikan presisi mutlak di akhir animasi
                isScrolling = false; 
            }
        }

        function easeInOutQuad(t, b, c, d) {
            t /= d / 2;
            if (t < 1) return c / 2 * t * t + b;
            t--;
            return -c / 2 * (t * (t - 2) - 1) + b;
        }

        requestAnimationFrame(animation);
    }

    if (!isMobile) {
        window.addEventListener('wheel', function(e) {
            if (document.getElementById('videoModal').style.display === 'flex') return;
            
            e.preventDefault();

            const currentTime = new Date().getTime();
            // REVISI: Cooldown 1200ms menolak sinyal sisa "Inertia" dari sentuhan Trackpad
            if (isScrolling || (currentTime - lastScrollTime < 1200)) {
                return; 
            }

            if (e.deltaY > 0) { // Scroll Bawah
                if (currentSectionIndex < sections.length - 1) {
                    isScrolling = true;
                    lastScrollTime = currentTime;
                    currentSectionIndex++;
                    smoothScrollTo(sections[currentSectionIndex].offsetTop, 800); 
                }
            } else { // Scroll Atas
                if (currentSectionIndex > 0) {
                    isScrolling = true;
                    lastScrollTime = currentTime;
                    currentSectionIndex--;
                    smoothScrollTo(sections[currentSectionIndex].offsetTop, 800);
                }
            }
        }, { passive: false });
    }

    // ==========================================
    // 2. SCROLL TRACKER & BACK TO TOP
    // ==========================================
    // Meletakkan event scroll ke global untuk men-trigger UI Button dan Tracking
    window.addEventListener('scroll', function() {
        let scrollPosition = window.scrollY || document.documentElement.scrollTop;
        
        // Memunculkan tombol Back to Top
        if (btnBackToTop) {
            if (scrollPosition > 300) {
                btnBackToTop.classList.add('show');
            } else {
                btnBackToTop.classList.remove('show');
            }
        }

        // REVISI: Auto-Sync Index jika user iseng menarik scrollbar (batang di pinggir layar) secara manual
        if (!isScrolling && !isMobile) {
            sections.forEach((sec, index) => {
                // Deteksi section mana yang paling banyak terlihat di layar
                if (scrollPosition >= sec.offsetTop - (window.innerHeight / 2)) {
                    currentSectionIndex = index;
                }
            });
        }
    });

    if (btnBackToTop) {
        btnBackToTop.addEventListener('click', function(e) {
            e.preventDefault(); 
            
            if (!isMobile) {
                // REVISI: Menggunakan Custom Scroll Engine kita, bukan bawaan window
                isScrolling = true;
                lastScrollTime = new Date().getTime(); 
                currentSectionIndex = 0; // Reset memori index ke 0 (Hero)
                smoothScrollTo(0, 800);
            } else {
                window.scrollTo({ top: 0, behavior: 'smooth' });
            }
        });
    }

    // ==========================================
    // 3. VIDEO MODAL ENGINE
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
    // 4. AJAX FORM SUBMISSION
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
    // 5. FLOATING ACTION BUTTON (FAB)
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

});