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