<?php require_once('config.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>User Profile - VectorScope</title>
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
          <a href="profile.php">Profile</a>
        </nav>
      </div>
      <div class="user-menu">
        <a href="login.php" style="color:var(--muted);text-decoration:none;font-size:0.9rem">Sign in</a>
      </div>
    </header>

    <main style="margin-top:28px">
      <div style="max-width:1000px;margin:0 auto">
        <?php
        // IDOR vulnerability: unsanitized user ID directly in SQL query (INTENTIONAL - DO NOT FIX)
        $id = isset($_GET['id']) ? $_GET['id'] : 1;
        $idor_flag = get_flag('idor_flag');
        $r = $conn->query("SELECT * FROM users WHERE id=$id");
        $user = $r ? $r->fetch_assoc() : null;
        // Check if accessing admin profile (id=2) - that's the IDOR vulnerability
        $is_idor_exploit = isset($_GET['id']) && $_GET['id'] == 2;
        ?>

        <?php if ($is_idor_exploit && $idor_flag): ?>
          <div style="background:rgba(147,51,234,0.15);border:1px solid rgba(147,51,234,0.4);color:#d8b4fe;padding:14px;border-radius:6px;margin-bottom:20px;font-size:0.9rem;word-break:break-all">
            <strong>🚩 IDOR Vulnerability Detected!</strong><br>
            <span style="font-family:monospace;font-size:0.85rem">Flag: <?php echo htmlspecialchars($idor_flag); ?></span>
          </div>
        <?php endif; ?>

        <?php if ($user): ?>
          <div style="margin-bottom:28px">
            <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
              <div style="width:80px;height:80px;border-radius:8px;background:linear-gradient(135deg,#2563eb,#06b6d4);display:flex;align-items:center;justify-content:center;color:#fff;font-size:2rem;font-weight:bold">
                <?php echo strtoupper(substr($user['username'], 0, 1)); ?>
              </div>
              <div>
                <h2 style="margin:0 0 4px 0"><?php echo htmlspecialchars($user['username']); ?></h2>
                <p class="muted" style="margin:0">User Account</p>
              </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;margin-bottom:20px">
              <!-- Account Information -->
              <div class="card" style="padding:20px">
                <h3 style="margin:0 0 14px 0;font-size:1rem">Account Information</h3>
                <div style="display:flex;flex-direction:column;gap:12px;font-size:0.9rem">
                  <div>
                    <div style="color:var(--muted);margin-bottom:4px">Username</div>
                    <div style="font-family:monospace;background:rgba(255,255,255,0.03);padding:8px 10px;border-radius:4px;border:1px solid rgba(255,255,255,0.05)"><?php echo htmlspecialchars($user['username']); ?></div>
                  </div>
                  <div>
                    <div style="color:var(--muted);margin-bottom:4px">Email</div>
                    <div style="font-family:monospace;background:rgba(255,255,255,0.03);padding:8px 10px;border-radius:4px;border:1px solid rgba(255,255,255,0.05)"><?php echo htmlspecialchars($user['email']); ?></div>
                  </div>
                  <div>
                    <div style="color:var(--muted);margin-bottom:4px">User ID</div>
                    <div style="font-family:monospace;background:rgba(255,255,255,0.03);padding:8px 10px;border-radius:4px;border:1px solid rgba(255,255,255,0.05)"><?php echo htmlspecialchars($user['id']); ?></div>
                  </div>
                </div>
              </div>

              <!-- Activity & Notes -->
              <div class="card" style="padding:20px">
                <h3 style="margin:0 0 14px 0;font-size:1rem">Account Notes</h3>
                <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:6px;padding:12px;font-size:0.9rem;line-height:1.5;min-height:100px;color:var(--muted);white-space:pre-wrap;word-break:break-word">
<?php echo htmlspecialchars($user['notes']); ?>
                </div>
              </div>
            </div>

            <!-- Access Details -->
            <div class="card" style="padding:20px">
              <h3 style="margin:0 0 14px 0;font-size:1rem">Access Status</h3>
              <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;font-size:0.9rem">
                <div>
                  <div style="color:var(--muted);margin-bottom:4px;font-size:0.85rem">Status</div>
                  <div style="color:#86efac">Active</div>
                </div>
                <div>
                  <div style="color:var(--muted);margin-bottom:4px;font-size:0.85rem">Last Login</div>
                  <div style="color:#ccc">2025-01-15 14:32</div>
                </div>
                <div>
                  <div style="color:var(--muted);margin-bottom:4px;font-size:0.85rem">Account Type</div>
                  <div><?php echo $user['id'] == 1 ? 'Standard' : ($user['id'] == 2 ? 'Administrator' : 'Standard'); ?></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Navigation to other profiles -->
          <div style="padding:16px;background:rgba(255,255,255,0.01);border:1px solid rgba(255,255,255,0.05);border-radius:6px;font-size:0.9rem">
            <div style="color:var(--muted);margin-bottom:8px">View other profiles: 
              <a href="profile.php?id=1" style="color:var(--accent);text-decoration:none">Profile 1</a> | 
              <a href="profile.php?id=2" style="color:var(--accent);text-decoration:none">Profile 2</a>
            </div>
          </div>
        <?php else: ?>
          <div class="card" style="padding:40px;text-align:center">
            <p class="muted">User not found. Please check the user ID and try again.</p>
          </div>
        <?php endif; ?>
      </div>
    </main>

    <footer>
      <div class="muted" style="font-size:0.9rem">VectorScope Internal Platform &copy; 2025 | Internal Use Only</div>
    </footer>
  </div>
<a href="portal/dashboard.php" style="position:fixed;bottom:24px;right:24px;z-index:9999;background:linear-gradient(135deg,#3b82f6,#8b5cf6);color:#fff;padding:11px 20px;border-radius:10px;font-family:Inter,sans-serif;font-size:.82rem;font-weight:600;text-decoration:none;box-shadow:0 4px 24px rgba(59,130,246,.45);display:flex;align-items:center;gap:8px;letter-spacing:.02em;transition:all .2s" onmouseover="this.style.transform='translateY(-2px)';this.style.boxShadow='0 8px 32px rgba(59,130,246,.6)'" onmouseout="this.style.transform='';this.style.boxShadow='0 4px 24px rgba(59,130,246,.45)'"></body>#9873; Submit Flags</a>
</body>
</html>
