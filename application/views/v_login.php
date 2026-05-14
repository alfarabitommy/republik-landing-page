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