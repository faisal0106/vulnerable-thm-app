<?php
// DEBUG PAGE - Shows all available flags for testing purposes
// Remove this in production!
include 'config.php';
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Debug - All Flags</title>
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
          <a href="index.php">Home</a>
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
        <h1 style="font-size:2rem;margin-bottom:8px">🚩 Debug - All Flags</h1>
        <p class="muted" style="margin-bottom:20px">For testing and development purposes only.</p>

        <div style="display:grid;grid-template-columns:1fr;gap:16px">
          <?php
          $flags = [
            'login_flag' => 'SQL Injection - Login Form',
            'xss_flag' => 'XSS - Search Form',
            'order_flag' => 'Blind SQL Injection - Orders',
            'idor_flag' => 'IDOR - User Profiles',
            'debug_flag' => 'Debug Information Disclosure'
          ];

          foreach ($flags as $flag_name => $flag_desc) {
            $flag_value = get_flag($flag_name);
            $flag_value = $flag_value ? $flag_value : 'NOT_FOUND';
            ?>
            <div class="card" style="padding:20px;background:rgba(147,51,234,0.1);border:1px solid rgba(147,51,234,0.3)">
              <h3 style="margin:0 0 8px 0;font-size:1rem;color:#d8b4fe"><?php echo htmlspecialchars($flag_desc); ?></h3>
              <div style="font-family:monospace;font-size:0.9rem;background:rgba(0,0,0,0.3);padding:12px;border-radius:4px;border-left:3px solid #a78bfa;word-break:break-all;color:#e9d5ff">
                <?php echo htmlspecialchars($flag_value); ?>
              </div>
            </div>
            <?php
          }
          ?>
        </div>

        <div class="card" style="padding:20px;margin-top:24px;background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3)">
          <h3 style="margin:0 0 8px 0;color:#fca5a5">⚠️ Warning</h3>
          <p style="margin:0;color:#fecaca;font-size:0.9rem">This debug page should be removed before deploying to production. It exposes all flags and sensitive information.</p>
        </div>
      </div>
    </main>

    <footer>
      <div class="muted" style="font-size:0.9rem">VectorScope Internal Platform &copy; 2025 | Internal Use Only</div>
    </footer>
  </div>
</body>
</html>
