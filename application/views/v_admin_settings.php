<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REPUBLIK | Content Editor</title>
    <style>
        :root {
            --bg-dark: #0B0B0B;
            --bg-panel: #151515;
            --bg-hover: #222222;
            --accent-blue: #4A7AFF;
            --accent-blue-hover: #335ECC;
            --accent-gold: #D4AF37;
            --text-main: #ffffff;
            --text-muted: #888888;
            --border-color: #333333;
            --success-green: #00C851;
            --error-red: #ff4444;
            --font-main: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background-color: var(--bg-dark); color: var(--text-main); font-family: var(--font-main); display: flex; min-height: 100vh; }

        .sidebar { width: 260px; background-color: var(--bg-panel); border-right: 1px solid var(--border-color); padding: 30px 20px; display: flex; flex-direction: column; }
        .brand-logo { font-size: 1.5rem; font-weight: 900; letter-spacing: 2px; color: var(--text-main); margin-bottom: 40px; text-decoration: none; }
        .brand-logo span { color: var(--accent-blue); }
        .nav-menu { list-style: none; flex-grow: 1; }
        .nav-item { margin-bottom: 10px; }
        .nav-link { display: block; padding: 12px 15px; color: var(--text-muted); text-decoration: none; font-weight: bold; border-radius: 4px; transition: 0.3s; }
        .nav-link:hover, .nav-link.active { background-color: var(--accent-blue); color: #ffffff; }
        .user-panel { padding-top: 20px; border-top: 1px solid var(--border-color); font-size: 0.9rem; color: var(--text-muted); }
        .logout-btn { display: block; margin-top: 10px; color: #ff4444; text-decoration: none; font-weight: bold; }

        .main-content { flex-grow: 1; padding: 40px; overflow-y: auto; }
        .page-header { margin-bottom: 30px; }
        .page-header h2 { font-size: 2rem; }

        .editor-wrapper { background-color: var(--bg-panel); padding: 30px; border-radius: 8px; border: 1px solid var(--border-color); max-width: 850px; }
        
        .form-group { margin-bottom: 25px; }
        .form-group-bundle { background-color: #1a1a1a; padding: 20px; border: 1px solid var(--border-color); border-radius: 6px; margin-bottom: 25px; }
        
        label { display: block; margin-bottom: 10px; font-weight: bold; color: var(--accent-gold); text-transform: uppercase; font-size: 0.85rem; letter-spacing: 1px; }
        .sub-label { color: var(--text-muted); font-size: 0.8rem; margin-bottom: 5px; display: block; text-transform: none; font-weight: normal; }
        
        input[type="text"], textarea { width: 100%; padding: 12px 15px; background-color: var(--bg-dark); border: 1px solid var(--border-color); border-radius: 4px; color: var(--text-main); font-family: var(--font-main); font-size: 0.95rem; margin-bottom: 10px; }
        input[type="file"] { width: 100%; padding: 10px; background-color: var(--bg-dark); border: 1px dashed var(--border-color); border-radius: 4px; color: var(--text-main); margin-bottom: 10px; cursor: pointer; }
        input[type="text"]:focus, textarea:focus { outline: none; border-color: var(--accent-blue); }
        textarea { height: 120px; resize: vertical; }

        .btn-save { background-color: var(--accent-blue); color: #fff; border: none; padding: 15px 30px; font-size: 1rem; font-weight: bold; border-radius: 4px; cursor: pointer; text-transform: uppercase; transition: 0.3s; margin-top: 20px;}
        .btn-save:hover { background-color: var(--accent-blue-hover); }

        .alert-success { background-color: rgba(0, 200, 81, 0.1); border-left: 4px solid var(--success-green); color: var(--success-green); padding: 15px; margin-bottom: 25px; font-size: 0.95rem; font-weight: bold; border-radius: 2px; }
        .alert-error { background-color: rgba(255, 68, 68, 0.1); border-left: 4px solid var(--error-red); color: var(--error-red); padding: 15px; margin-bottom: 25px; font-size: 0.95rem; font-weight: bold; border-radius: 2px; }
        
        .img-preview { max-height: 80px; border-radius: 4px; border: 1px solid #444; margin-bottom: 10px; display: block; }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="<?= base_url('Admin') ?>" class="brand-logo">REP<span>.</span></a>
        <ul class="nav-menu">
            <li class="nav-item"><a href="<?= base_url('Admin') ?>" class="nav-link">Leads Inbox</a></li>
            <li class="nav-item"><a href="<?= base_url('Admin/settings') ?>" class="nav-link active">Settings</a></li>
        </ul>
        <div class="user-panel">
            Logged in as:<br><strong style="color:var(--text-main)"><?= $this->session->userdata('username'); ?></strong>
            <a href="<?= base_url('Auth/logout') ?>" class="logout-btn">Log Out &rarr;</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h2>Dynamic Content Editor</h2>
        </div>

        <div class="editor-wrapper">
            <?php if($this->session->flashdata('success')): ?>
                <div class="alert-success"><?= $this->session->flashdata('success') ?></div>
            <?php endif; ?>
            
            <?php if($this->session->flashdata('error')): ?>
                <div class="alert-error"><?= $this->session->flashdata('error') ?></div>
            <?php endif; ?>

            <form action="<?= base_url('Admin/save_settings') ?>" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

                <div class="form-group">
                    <label for="headline_main">Hero Main Headline</label>
                    <textarea id="headline_main" name="headline_main" required><?= $settings['headline_main'] ?? '' ?></textarea>
                </div>

                <hr style="border: 0; border-top: 1px dashed #444; margin: 40px 0 30px 0;">
                <h3 style="margin-bottom: 20px; font-size: 1.2rem; color: #fff;">Portfolio Section (Our Work)</h3>

                <div class="form-group-bundle">
                    <label>Portfolio 1 (Top Left)</label>
                    <span class="sub-label">Title Displayed:</span>
                    <input type="text" name="video_title_1" value="<?= $settings['video_title_1'] ?? 'HONDA AHM' ?>" placeholder="e.g., HONDA AHM">
                    
                    <span class="sub-label">Video URL (YouTube/MP4):</span>
                    <input type="text" name="video_1" value="<?= $settings['video_1'] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ?>">
                    
                    <span class="sub-label">Upload New Thumbnail (Leave blank to keep current):</span>
                    <?php if(!empty($settings['video_thumb_1'])): ?>
                        <img src="<?= base_url($settings['video_thumb_1']) ?>" alt="Current Thumb" class="img-preview">
                    <?php endif; ?>
                    <input type="file" name="video_thumb_1" accept="image/*">
                </div>
                
                <div class="form-group-bundle">
                    <label>Portfolio 2 (Top Middle)</label>
                    <span class="sub-label">Title Displayed:</span>
                    <input type="text" name="video_title_2" value="<?= $settings['video_title_2'] ?? 'JERGENS' ?>" placeholder="e.g., JERGENS">
                    
                    <span class="sub-label">Video URL (YouTube/MP4):</span>
                    <input type="text" name="video_2" value="<?= $settings['video_2'] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ?>">
                    
                    <span class="sub-label">Upload New Thumbnail:</span>
                    <?php if(!empty($settings['video_thumb_2'])): ?>
                        <img src="<?= base_url($settings['video_thumb_2']) ?>" alt="Current Thumb" class="img-preview">
                    <?php endif; ?>
                    <input type="file" name="video_thumb_2" accept="image/*">
                </div>

                <div class="form-group-bundle">
                    <label>Portfolio 3 (Top Right)</label>
                    <span class="sub-label">Title Displayed:</span>
                    <input type="text" name="video_title_3" value="<?= $settings['video_title_3'] ?? 'HONDA AHM' ?>" placeholder="e.g., HONDA AHM">
                    
                    <span class="sub-label">Video URL (YouTube/MP4):</span>
                    <input type="text" name="video_3" value="<?= $settings['video_3'] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ?>">
                    
                    <span class="sub-label">Upload New Thumbnail:</span>
                    <?php if(!empty($settings['video_thumb_3'])): ?>
                        <img src="<?= base_url($settings['video_thumb_3']) ?>" alt="Current Thumb" class="img-preview">
                    <?php endif; ?>
                    <input type="file" name="video_thumb_3" accept="image/*">
                </div>

                <div class="form-group-bundle">
                    <label>Portfolio 4 (Bottom Left-Center)</label>
                    <span class="sub-label">Title Displayed:</span>
                    <input type="text" name="video_title_4" value="<?= $settings['video_title_4'] ?? 'HONDA AHM' ?>" placeholder="e.g., HONDA AHM">
                    
                    <span class="sub-label">Video URL (YouTube/MP4):</span>
                    <input type="text" name="video_4" value="<?= $settings['video_4'] ?? base_url('assets/video/honda.mp4') ?>">
                    
                    <span class="sub-label">Upload New Thumbnail:</span>
                    <?php if(!empty($settings['video_thumb_4'])): ?>
                        <img src="<?= base_url($settings['video_thumb_4']) ?>" alt="Current Thumb" class="img-preview">
                    <?php endif; ?>
                    <input type="file" name="video_thumb_4" accept="image/*">
                </div>

                <div class="form-group-bundle">
                    <label>Portfolio 5 (Bottom Right-Center)</label>
                    <span class="sub-label">Title Displayed:</span>
                    <input type="text" name="video_title_5" value="<?= $settings['video_title_5'] ?? 'JERGENS' ?>" placeholder="e.g., JERGENS">
                    
                    <span class="sub-label">Video URL (YouTube/MP4):</span>
                    <input type="text" name="video_5" value="<?= $settings['video_5'] ?? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' ?>">
                    
                    <span class="sub-label">Upload New Thumbnail:</span>
                    <?php if(!empty($settings['video_thumb_5'])): ?>
                        <img src="<?= base_url($settings['video_thumb_5']) ?>" alt="Current Thumb" class="img-preview">
                    <?php endif; ?>
                    <input type="file" name="video_thumb_5" accept="image/*">
                </div>

                <button type="submit" class="btn-save">Save All Content</button>
            </form>
        </div>
    </main>

</body>
</html>