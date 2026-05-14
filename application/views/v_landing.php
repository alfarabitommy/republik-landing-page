<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="theme-color" content="#0B0B0B">
    
    <title>REPUBLIK | Creative Intelligence Agency</title>
    <meta name="title" content="REPUBLIK | Creative Intelligence Agency">
    <meta name="description" content="Your brand doesn't need more content. It needs a sharper creative system. We help brands turn business problems into culture-sharp creative platforms.">
    <meta name="keywords" content="Creative Agency, Intelligence Agency, Brand Strategy, B2B Marketing, Content System, Indonesia Agency">
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

    <main>
        <header class="hero-section">
            <div class="hero-collage-container">
                <div class="hero-logo-overlay">
                    <div class="logo-placeholder-circle">
                        <h1>REPUBLIK</h1>
                        <p>Creative Intelligence Agency</p>
                    </div>
                </div>

                <div class="hero-collage-grid">
                    <div class="collage-cell item-tall placeholder-dark"><img src="<?= base_url('assets/img/college1.png') ?>" alt="REPUBLIK Creative Work 1" class="collage-img" fetchpriority="high"></div>
                    <div class="collage-cell item-wide-top placeholder-gray"><img src="<?= base_url('assets/img/college2.png') ?>" alt="REPUBLIK Creative Work 2" class="collage-img" fetchpriority="high"></div>
                    <div class="collage-cell item-small-top placeholder-dark"><img src="<?= base_url('assets/img/college3.png') ?>" alt="REPUBLIK Creative Work 3" class="collage-img"></div>
                    <div class="collage-cell item-wide-bottom placeholder-gray"><img src="<?= base_url('assets/img/college4.png') ?>" alt="REPUBLIK Creative Work 4" class="collage-img"></div>
                    <div class="collage-cell item-small-bottom placeholder-dark"><img src="<?= base_url('assets/img/college5.png') ?>" alt="REPUBLIK Creative Work 5" class="collage-img"></div>
                </div>
            </div>

            <div class="container">
                <h2 class="headline-utama">
                    <?= nl2br(html_escape($settings['headline_main'] ?? 'Your brand doesn\'t need more content. It needs a sharper creative system.')) ?>
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
                    
                    <div class="portfolio-item video-trigger" aria-label="Play <?= html_escape($settings['video_title_1'] ?? 'Video 1') ?>" data-video-src="<?= $settings['video_1'] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ?>" style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.6) 100%), url('<?= !empty($settings['video_thumb_1']) ? base_url($settings['video_thumb_1']) : '' ?>') center/cover no-repeat #222;">
                        <div class="overlay-text"><?= html_escape($settings['video_title_1'] ?? 'HONDA AHM') ?></div>
                        <div class="play-icon" aria-hidden="true">▶</div>
                    </div>
                    
                    <div class="portfolio-item video-trigger" aria-label="Play <?= html_escape($settings['video_title_2'] ?? 'Video 2') ?>" data-video-src="<?= $settings['video_2'] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ?>" style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.6) 100%), url('<?= !empty($settings['video_thumb_2']) ? base_url($settings['video_thumb_2']) : '' ?>') center/cover no-repeat #222;">
                        <div class="overlay-text"><?= html_escape($settings['video_title_2'] ?? 'JERGENS') ?></div>
                        <div class="play-icon" aria-hidden="true">▶</div>
                    </div>
                    
                    <div class="portfolio-item video-trigger" aria-label="Play <?= html_escape($settings['video_title_3'] ?? 'Video 3') ?>" data-video-src="<?= $settings['video_3'] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ?>" style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.6) 100%), url('<?= !empty($settings['video_thumb_3']) ? base_url($settings['video_thumb_3']) : '' ?>') center/cover no-repeat #222;">
                        <div class="overlay-text"><?= html_escape($settings['video_title_3'] ?? 'HONDA AHM') ?></div>
                        <div class="play-icon" aria-hidden="true">▶</div>
                    </div>
                    
                    <div class="portfolio-item video-trigger" aria-label="Play <?= html_escape($settings['video_title_4'] ?? 'Video 4') ?>" data-video-src="<?= $settings['video_4'] ?? base_url('assets/video/honda.mp4') ?>" style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.6) 100%), url('<?= !empty($settings['video_thumb_4']) ? base_url($settings['video_thumb_4']) : '' ?>') center/cover no-repeat #222;">
                        <div class="overlay-text"><?= html_escape($settings['video_title_4'] ?? 'HONDA AHM') ?></div>
                        <div class="play-icon" aria-hidden="true">▶</div>
                    </div>
                    
                    <div class="portfolio-item video-trigger" aria-label="Play <?= html_escape($settings['video_title_5'] ?? 'Video 5') ?>" data-video-src="<?= $settings['video_5'] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ?>" style="background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0.2) 50%, rgba(0,0,0,0.6) 100%), url('<?= !empty($settings['video_thumb_5']) ? base_url($settings['video_thumb_5']) : '' ?>') center/cover no-repeat #222;">
                        <div class="overlay-text"><?= html_escape($settings['video_title_5'] ?? 'JERGENS') ?></div>
                        <div class="play-icon" aria-hidden="true">▶</div>
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