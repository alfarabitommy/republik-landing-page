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
        <div class="container">
            <div class="logo">
                <h1>REPUBLIK</h1>
                <p>Creative Intelligence Agency</p>
            </div>
            <h2 class="headline-utama">
                Your brand doesn't<br>
                need more content. It needs<br>
                <span class="highlight">a sharper creative system.</span>
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