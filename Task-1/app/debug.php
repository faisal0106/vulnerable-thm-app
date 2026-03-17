<?php
// Debug page intentionally left verbose for troubleshooting and training.
include 'config.php';
error_reporting(E_ALL);
ini_set('display_errors', '1');
// Expose the debug flag via an environment variable so it appears in phpinfo()
$dbg = get_flag('debug_flag');
if ($dbg) {
	putenv('FLAG_DEBUG=' . $dbg);
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Debug - VectorScope</title>
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

    <main style="margin-top:28px;margin-bottom:40px">
      <div style="max-width:1000px;margin:0 auto">
        <h2 style="font-size:1.5rem;margin-bottom:8px">🚩 Debug Information Exposed</h2>
        <p class="muted" style="margin-bottom:20px">This page exposes sensitive debug information. PHP version, environment variables, and configuration details are visible below.</p>

        <?php if ($dbg): ?>
          <div class="card" style="padding:20px;background:rgba(147,51,234,0.15);border:1px solid rgba(147,51,234,0.4);margin-bottom:24px">
            <h3 style="margin:0 0 12px 0;color:#d8b4fe">Debug Flag Found</h3>
            <div style="font-family:monospace;font-size:0.9rem;background:rgba(0,0,0,0.3);padding:12px;border-radius:4px;border-left:3px solid #a78bfa;word-break:break-all;color:#e9d5ff">
              <?php echo htmlspecialchars($dbg); ?>
            </div>
          </div>
        <?php endif; ?>

        <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;padding:14px;border-radius:6px;margin-bottom:24px;font-size:0.9rem">
          <strong>⚠️ Warning:</strong> The information below is sensitive and should never be exposed in production environments.
        </div>

        <div style="background:rgba(0,0,0,0.2);border-radius:6px;padding:20px;overflow-x:auto">
          <?php phpinfo(); ?>
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
