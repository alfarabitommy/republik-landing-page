<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>REPUBLIK | Leads Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/simple-datatables@latest/dist/style.css" rel="stylesheet" type="text/css">
    <style>
        /* CSS Variables & Reset */
        :root {
            --bg-dark: #0B0B0B;
            --bg-panel: #151515;
            --bg-hover: #222222;
            --accent-blue: #4A7AFF;
            --accent-gold: #D4AF37;
            --text-main: #ffffff;
            --text-muted: #888888;
            --border-color: #333333;
            --font-main: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-dark);
            color: var(--text-main);
            font-family: var(--font-main);
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar Layout */
        .sidebar {
            width: 260px;
            background-color: var(--bg-panel);
            border-right: 1px solid var(--border-color);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
        }

        .brand-logo {
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: 2px;
            color: var(--text-main);
            margin-bottom: 40px;
            text-decoration: none;
        }
        
        .brand-logo span { color: var(--accent-blue); }

        .nav-menu { list-style: none; flex-grow: 1; }
        .nav-item { margin-bottom: 10px; }
        .nav-link {
            display: block;
            padding: 12px 15px;
            color: var(--text-muted);
            text-decoration: none;
            font-weight: bold;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        .nav-link:hover, .nav-link.active {
            background-color: var(--accent-blue);
            color: #ffffff;
        }

        .user-panel {
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .logout-btn {
            display: block;
            margin-top: 10px;
            color: #ff4444;
            text-decoration: none;
            font-weight: bold;
        }

        /* Content Area */
        .main-content {
            flex-grow: 1;
            padding: 40px;
            overflow-y: auto;
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .page-header h2 { font-size: 2rem; }

        /* DataTables Custom Styling for Dark Mode */
        .dataTable-wrapper {
            background-color: var(--bg-panel);
            padding: 25px;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        .dataTable-table > thead > tr > th {
            border-bottom: 1px solid var(--border-color);
            color: var(--accent-gold);
            text-transform: uppercase;
            font-size: 0.85rem;
            padding-bottom: 15px;
        }
        .dataTable-table > tbody > tr > td {
            border-bottom: 1px solid var(--border-color);
            padding: 15px 10px;
            vertical-align: middle;
            color: #e0e0e0;
        }
        .dataTable-table > tbody > tr:hover {
            background-color: var(--bg-hover) !important;
        }
        .dataTable-input, .dataTable-selector {
            background-color: var(--bg-dark);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            padding: 8px 12px;
            border-radius: 4px;
        }

        /* Status Dropdown Styling */
        .select-status {
            background-color: var(--bg-dark);
            color: var(--text-main);
            border: 1px solid var(--border-color);
            padding: 6px 10px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.85rem;
            cursor: pointer;
            outline: none;
        }
        .select-status:focus { border-color: var(--accent-blue); }
        
        .msg-preview {
            max-width: 250px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: inline-block;
            font-size: 0.9rem;
        }

        /* Notifikasi Toast Minimalis */
        #toast {
            visibility: hidden;
            min-width: 250px;
            background-color: var(--accent-blue);
            color: #fff;
            text-align: center;
            border-radius: 4px;
            padding: 16px;
            position: fixed;
            z-index: 1000;
            right: 30px;
            bottom: 30px;
            font-weight: bold;
            opacity: 0;
            transition: opacity 0.5s, visibility 0.5s;
        }
        #toast.show {
            visibility: visible;
            opacity: 1;
        }
    </style>
</head>
<body>

    <aside class="sidebar">
        <a href="<?= base_url('Admin') ?>" class="brand-logo">REP<span>.</span></a>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a href="<?= base_url('Admin') ?>" class="nav-link active">Leads Inbox</a>
            </li>
            <li class="nav-item">
                <a href="<?= base_url('Admin/settings') ?>" class="nav-link">Settings</a>
            </li>
        </ul>

        <div class="user-panel">
            Logged in as:<br>
            <strong style="color:var(--text-main)"><?= $this->session->userdata('username'); ?></strong>
            <a href="<?= base_url('Auth/logout') ?>" class="logout-btn">Log Out &rarr;</a>
        </div>
    </aside>

    <main class="main-content">
        <div class="page-header">
            <h2>Leads Intelligence</h2>
        </div>

        <input type="hidden" id="csrf_token" name="<?= $this->security->get_csrf_token_name(); ?>" value="<?= $this->security->get_csrf_hash(); ?>">

        <div class="dataTable-wrapper">
            <table id="leadsTable" class="dataTable-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Client Name</th>
                        <th>Organization</th>
                        <th>Messages</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!empty($leads)): foreach($leads as $row): ?>
                    <tr>
                        <td><?= date('d M Y, H:i', strtotime($row['created_at'])) ?></td>
                        <td>
                            <strong><?= html_escape($row['first_name'] . ' ' . $row['last_name']) ?></strong><br>
                            <small style="color:var(--text-muted)"><?= html_escape($row['email']) ?></small>
                        </td>
                        <td>
                            <?= html_escape($row['organization']) ?><br>
                            <small style="color:var(--text-muted)"><?= html_escape($row['position']) ?></small>
                        </td>
                        <td>
                            <span class="msg-preview" title="<?= html_escape($row['messages']) ?>">
                                <?= html_escape($row['messages']) ?>
                            </span>
                        </td>
                        <td>
                            <select class="select-status" onchange="updateLeadStatus(this, <?= $row['id_lead'] ?>)">
                                <option value="new" <?= ($row['status'] == 'new') ? 'selected' : '' ?>>New</option>
                                <option value="reviewed" <?= ($row['status'] == 'reviewed') ? 'selected' : '' ?>>Reviewed</option>
                                <option value="contacted" <?= ($row['status'] == 'contacted') ? 'selected' : '' ?>>Contacted</option>
                                <option value="closed" <?= ($row['status'] == 'closed') ? 'selected' : '' ?>>Closed</option>
                                <option value="spam" <?= ($row['status'] == 'spam') ? 'selected' : '' ?>>Spam</option>
                            </select>
                        </td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr>
                        <td colspan="5" style="text-align:center;">No leads found.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </main>

    <div id="toast">Status Updated!</div>

    <script src="https://cdn.jsdelivr.net/npm/simple-datatables@latest" type="text/javascript"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const myTable = document.getElementById("leadsTable");
            if (myTable) {
                new simpleDatatables.DataTable(myTable, {
                    searchable: true,
                    fixedHeight: true,
                    perPage: 10
                });
            }
        });

        function updateLeadStatus(selectElement, idLead) {
            const newStatus = selectElement.value;
            const csrfInput = document.getElementById('csrf_token');
            const csrfName = csrfInput.getAttribute('name');
            const csrfValue = csrfInput.value;

            const formData = new FormData();
            formData.append('id_lead', idLead);
            formData.append('status', newStatus);
            formData.append(csrfName, csrfValue);

            selectElement.disabled = true;

            fetch('<?= base_url("Admin/update_status") ?>', {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            })
            .then(response => {
                if(!response.ok) throw new Error('Network response was not ok');
                return response.json();
            })
            .then(data => {
                selectElement.disabled = false;
                if(data.status) {
                    csrfInput.value = data.csrf_token;
                    showToast(data.message);
                } else {
                    alert('Gagal: ' + data.message);
                }
            })
            .catch(error => {
                selectElement.disabled = false;
                alert('Terjadi kesalahan koneksi sistem.');
                console.error('Error:', error);
            });
        }

        function showToast(msg) {
            const toast = document.getElementById("toast");
            toast.innerText = msg;
            toast.className = "show";
            setTimeout(function(){ toast.className = toast.className.replace("show", ""); }, 3000);
        }
    </script>
</body>
</html>