<?php require_once('config.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin Panel - VectorScope</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <header class="site header-bar">
      <div style="display:flex;align-items:center;gap:14px">
        <div class="brand">
          <div class="logo" style="width:48px;height:48px;border-radius:6px;background:#2563eb;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;font-size:18px">VS</div>
          <div class="title">VectorScope</div>
        </div>
        <nav class="nav">
          <a href="home.php">Home</a>
          <a href="search.php">Search</a>
          <a href="orders.php">Records</a>
        </nav>
      </div>
      <div class="user-menu">
        <a href="login.php" style="color:var(--muted);text-decoration:none;font-size:0.9rem">Sign in</a>
      </div>
    </header>

    <main style="margin-top:28px">
      <div style="max-width:1000px;margin:0 auto">
        <h2 style="margin:0 0 8px 0">Administrative Panel</h2>
        <p class="muted" style="margin:0 0 24px 0">Internal management and monitoring. Restricted access.</p>

        <!-- User Management -->
        <div class="card" style="padding:20px;margin-bottom:20px">
          <h3 style="margin:0 0 14px 0;font-size:1rem">User Management</h3>
          <div style="overflow-x:auto">
            <table style="width:100%;border-collapse:collapse;font-size:0.9rem">
              <thead style="border-bottom:1px solid rgba(255,255,255,0.1)">
                <tr>
                  <th style="padding:8px;text-align:left;color:var(--muted);font-weight:500">ID</th>
                  <th style="padding:8px;text-align:left;color:var(--muted);font-weight:500">Username</th>
                  <th style="padding:8px;text-align:left;color:var(--muted);font-weight:500">Email</th>
                  <th style="padding:8px;text-align:left;color:var(--muted);font-weight:500">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $r = $conn->query("SELECT * FROM users");
                while ($u = $r->fetch_assoc()) {
                  echo "<tr style='border-bottom:1px solid rgba(255,255,255,0.05)'>
                    <td style='padding:8px'>" . htmlspecialchars($u['id']) . "</td>
                    <td style='padding:8px'>" . htmlspecialchars($u['username']) . "</td>
                    <td style='padding:8px'>" . htmlspecialchars($u['email']) . "</td>
                    <td style='padding:8px;color:#86efac'>Active</td>
                  </tr>";
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- System Configuration -->
        <div class="card" style="padding:20px;margin-bottom:20px">
          <h3 style="margin:0 0 14px 0;font-size:1rem">System Configuration</h3>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;align-items:start">
            <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);border-radius:6px;padding:12px;font-family:monospace;font-size:0.9rem;color:#ccc;overflow:hidden">
              <div style="color:var(--muted);margin-bottom:6px">Connection</div>
              <div>Host: <strong><?php echo htmlspecialchars(getenv('DB_HOST') ?: 'db'); ?></strong></div>
              <div>Port: <strong><?php echo htmlspecialchars(getenv('DB_PORT') ?: '3306'); ?></strong></div>
              <div>User: <strong><?php echo htmlspecialchars(getenv('DB_USER') ?: 'root'); ?></strong></div>
              <div style="margin-top:8px;color:var(--muted);font-size:0.85rem">Environment: internal</div>
            </div>
            <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.04);border-radius:6px;padding:12px;font-size:0.95rem;color:var(--muted);line-height:1.4">
              <div style="color:var(--muted);margin-bottom:6px">Maintenance Log</div>
              <?php
                // Keep administrative note present but surface it as a maintenance entry so it
                // appears accidental rather than an obvious flag dump. The underlying value
                // remains available in the DB via `get_flag('idor_flag')`.
                $admin_note = get_flag('idor_flag');
                if ($admin_note) {
                  // A realistic-sounding log entry
                  echo "2025-01-15 — Routine review: Updated admin notes and follow-up tasks. Reference: ";
                  echo htmlspecialchars($admin_note);
                } else {
                  echo "No maintenance notes available.";
                }
              ?>
            </div>
          </div>
        </div>

        <!-- System Status -->
        <div class="card" style="padding:20px">
          <h3 style="margin:0 0 14px 0;font-size:1rem">System Status</h3>
          <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;font-size:0.9rem">
            <div>
              <div style="color:var(--muted);margin-bottom:4px">Database Connection</div>
              <div style="color:#86efac">Connected</div>
            </div>
            <div>
              <div style="color:var(--muted);margin-bottom:4px">Platform Status</div>
              <div style="color:#86efac">Operational</div>
            </div>
            <div>
              <div style="color:var(--muted);margin-bottom:4px">Last Backup</div>
              <div>2025-01-15 03:47</div>
            </div>
          </div>
        </div>
      </div>
    </main>

    <footer>
      <div class="muted" style="font-size:0.9rem">VectorScope Internal Platform &copy; 2025 | Internal Use Only</div>
    </footer>
  </div>
<a href="portal/dashboard.php" style="position:fixed;bottom:24px;right:24px;z-index:9999;background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;padding:11px 20px;border-radius:10px;font-family:Inter,sans-serif;font-size:.82rem;font-weight:600;text-decoration:none;box-shadow:0 4px 24px rgba(59,130,246,.45);display:flex;align-items:center;gap:8px;letter-spacing:.02em;transition:all .2s" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 32px rgba(59,130,246,.6)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 24px rgba(59,130,246,.45)'"></body>#9873; Submit Flags</a>
</body>
</html>
