<?php
session_start();
require_once('config.php');

$error = '';
$info = '';
$flag_found = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $u = $_POST['username'] ?? '';
  $p = $_POST['password'] ?? '';
  
  // Check if SQLi payload detected (for debugging)
  if (strpos($u, "OR") !== false || strpos($u, "or") !== false || strpos($u, "'") !== false) {
    $flag_found = get_flag('login_flag');
  }
  
  // SQL injection vulnerability - raw user input in WHERE clause (INTENTIONAL - DO NOT FIX)
  $query = "SELECT * FROM users WHERE username='$u' AND password='$p'";
  $res = $conn->query($query);
  
  if ($res && $res->num_rows > 0) {
    $_SESSION['user'] = $u;
    $info = 'Authentication successful. Redirecting...';
    header('Location: profile.php?id=1');
    exit;
  } else {
    $error = 'Invalid username or password';
  }
}
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>VectorScope - Sign In</title>
  <link rel="stylesheet" href="style.css">
</head>
<body style="background:#0f0f1e">
  <div style="min-height:100vh;display:flex;flex-direction:column">
    <header class="site header-bar">
      <div style="display:flex;align-items:center;gap:14px">
        <div class="brand">
          <div class="logo" style="width:48px;height:48px;border-radius:6px;background:#2563eb;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;font-size:18px">VS</div>
          <div class="title">VectorScope</div>
        </div>
      </div>
      <div class="user-menu">
        <a href="index.php" style="color:var(--muted);text-decoration:none;font-size:0.9rem">Back to Home</a>
      </div>
    </header>

    <div style="flex:1;display:flex;align-items:center;justify-content:center;padding:40px 20px">
      <div style="max-width:420px;width:100%;background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.08);border-radius:8px;padding:40px">
        <h2 style="margin:0 0 8px 0;font-size:1.5rem">Sign In</h2>
        <p class="muted" style="margin:0 0 24px 0;font-size:0.95rem">Enter your credentials to access VectorScope</p>
        
        <?php if ($error): ?>
          <div style="background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;padding:12px;border-radius:6px;margin-bottom:18px;font-size:0.9rem"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($info): ?>
          <div style="background:rgba(34,197,94,0.1);border:1px solid rgba(34,197,94,0.3);color:#86efac;padding:12px;border-radius:6px;margin-bottom:18px;font-size:0.9rem"><?php echo htmlspecialchars($info); ?></div>
        <?php endif; ?>

        <?php if ($flag_found): ?>
          <div style="background:rgba(147,51,234,0.15);border:1px solid rgba(147,51,234,0.4);color:#d8b4fe;padding:14px;border-radius:6px;margin-bottom:18px;font-size:0.9rem;word-break:break-all">
            <strong>🚩 SQL Injection Payload Detected!</strong><br>
            <span style="font-family:monospace;font-size:0.85rem">Flag: <?php echo htmlspecialchars($flag_found); ?></span>
          </div>
        <?php endif; ?>

        <form method="POST" style="display:flex;flex-direction:column;gap:14px">
          <div>
            <label style="display:block;margin-bottom:6px;font-size:0.9rem;color:var(--muted)">Username</label>
            <input type="text" name="username" required style="width:100%;padding:10px 12px;border:1px solid rgba(255,255,255,0.1);border-radius:6px;background:rgba(255,255,255,0.03);color:#fff;font-size:0.95rem;box-sizing:border-box" placeholder="Enter username">
          </div>
          <div>
            <label style="display:block;margin-bottom:6px;font-size:0.9rem;color:var(--muted)">Password</label>
            <input type="password" name="password" required style="width:100%;padding:10px 12px;border:1px solid rgba(255,255,255,0.1);border-radius:6px;background:rgba(255,255,255,0.03);color:#fff;font-size:0.95rem;box-sizing:border-box" placeholder="Enter password">
          </div>
          <button type="submit" style="background:#2563eb;color:#fff;padding:10px;border:none;border-radius:6px;font-weight:500;cursor:pointer;margin-top:8px;font-size:0.95rem">Sign In</button>
        </form>

        <p class="muted" style="margin:20px 0 0 0;font-size:0.85rem">Contact your system administrator for password reset or access issues.</p>
      </div>
    </div>

    <footer style="border-top:1px solid rgba(255,255,255,0.05);padding:20px;text-align:center">
      <div class="muted" style="font-size:0.9rem">VectorScope Internal Platform &copy; 2025 | Internal Use Only</div>
    </footer>
  </div>
</body>
</html>
