/* file: assets/js/main.js */
document.addEventListener('DOMContentLoaded', function() {
    
    const isMobile = window.innerWidth <= 768;
    const sections = document.querySelectorAll('.snap-section');
    let currentSectionIndex = 0;
    let isAnimating = false;
    let lastScrollTime = 0;
    const btnBackToTop = document.getElementById('btnBackToTop');

    // ==========================================
    // 1. LUXURY FLUID SCROLL ENGINE (DESKTOP)
    // ==========================================
    
    // Kurva Matematika Premium (Cubic Out Easing) - Memberikan efek gliding empuk di akhir gerakan
    function easeOutCubic(t) {
        return 1 - Math.pow(1 - t, 3);
    }

    function smoothGlidingTo(targetPosition, duration) {
        const startPosition = window.scrollY || document.documentElement.scrollTop;
        const distance = targetPosition - startPosition;
        let startTime = null;

        function animationStep(currentTime) {
            if (startTime === null) startTime = currentTime;
            const timeElapsed = currentTime - startTime;
            const progress = Math.min(timeElapsed / duration, 1);
            
            // Terapkan kurva perlambatan organik
            const easedProgress = easeOutCubic(progress);
            window.scrollTo(0, startPosition + (distance * easedProgress));
            
            if (progress < 1) {
                requestAnimationFrame(animationStep);
            } else {
                window.scrollTo(0, targetPosition); // Kunci posisi mutlak di akhir agar presisi
                // Berikan buffer 250ms setelah animasi selesai untuk menyerap sisa inersia trackpad
                setTimeout(() => {
                    isAnimating = false;
                }, 250);
            }
        }
        requestAnimationFrame(animationStep);
    }

    if (!isMobile) {
        window.addEventListener('wheel', function(e) {
            // Abaikan jika modal sedang terbuka
            if (document.getElementById('videoModal').style.display === 'flex') return;
            
            e.preventDefault(); // Matikan scroll patah-patah bawaan Windows/Chrome

            const currentTime = new Date().getTime();
            // Cegah double jump akibat sensitivitas trackpad/magic mouse
            if (isAnimating || (currentTime - lastScrollTime < 1300)) return;

            if (e.deltaY > 0) {
                // Jalur Gulir ke Bawah
                if (currentSectionIndex < sections.length - 1) {
                    isAnimating = true;
                    lastScrollTime = currentTime;
                    currentSectionIndex++;
                    smoothGlidingTo(sections[currentSectionIndex].offsetTop, 950); // Durasi meluncur 950ms mewah
                }
            } else {
                // Jalur Gulir ke Atas
                if (currentSectionIndex > 0) {
                    isAnimating = true;
                    lastScrollTime = currentTime;
                    currentSectionIndex--;
                    smoothGlidingTo(sections[currentSectionIndex].offsetTop, 950);
                }
            }
        }, { passive: false });
    }

    // ==========================================
    // 2. SCROLL INTEGRATION & SYNC TRACKER
    // ==========================================
    window.addEventListener('scroll', function() {
        let scrollPosition = window.scrollY || document.documentElement.scrollTop;
        
        // Atur visibilitas tombol Back to Top
        if (btnBackToTop) {
            if (scrollPosition > 400) {
                btnBackToTop.classList.add('show');
            } else {
                btnBackToTop.classList.remove('show');
            }
        }

        // Sinkronisasi index memori jika user menyeret scrollbar fisik di tepi layar
        if (!isAnimating && !isMobile) {
            sections.forEach((sec, index) => {
                if (scrollPosition >= sec.offsetTop - (window.innerHeight / 2)) {
                    currentSectionIndex = index;
                }
            });
        }
    });

    if (btnBackToTop) {
        btnBackToTop.addEventListener('click', function(e) {
            e.preventDefault(); 
            if (isAnimating) return;

            if (!isMobile) {
                isAnimating = true;
                lastScrollTime = new Date().getTime();
                currentSectionIndex = 0; // Kembalikan koordinat index ke Hero
                smoothGlidingTo(0, 1100); // Luncuran kembali ke atas dibuat sedikit lebih lambat & anggun
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
    // 5. FLOATING ACTION BUTTON (FAB) INTERACTION
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