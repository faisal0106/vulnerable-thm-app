<?php
require_once 'config.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$mode = $_GET['mode'] ?? 'login';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($action === 'register') {
        if (strlen($username) < 3 || strlen($username) > 20) {
            $error = 'Username must be 3–20 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $error = 'Username: letters, numbers, underscores only.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif (strtolower($username) === 'admin') {
            $error = 'That username is reserved.';
        } else {
            $exists = $pdb->prepare("SELECT id FROM portal_users WHERE LOWER(username)=LOWER(?)");
            $exists->execute([$username]);
            if ($exists->fetch()) {
                $error = 'Username already taken.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdb->prepare("INSERT INTO portal_users (username, password_hash) VALUES (?, ?)");
                $stmt->execute([$username, $hash]);
                $uid = $pdb->lastInsertId();
                $_SESSION['portal_user_id'] = $uid;
                $_SESSION['portal_username'] = $username;
                $_SESSION['portal_is_admin'] = 0;
                header('Location: dashboard.php');
                exit;
            }
        }
        $mode = 'register';
    } else {
        $stmt = $pdb->prepare("SELECT id, password_hash, is_admin FROM portal_users WHERE LOWER(username)=LOWER(?)");
        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['portal_user_id'] = $user['id'];
            $_SESSION['portal_username'] = $username;
            $_SESSION['portal_is_admin'] = $user['is_admin'];
            header('Location: ' . ($user['is_admin'] ? 'admin.php' : 'dashboard.php'));
            exit;
        } else {
            $error = 'Invalid credentials. Access denied.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>VectorScope CTF — Access Portal</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Orbitron:wght@600;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#06070f;--bg2:#0d0e1a;
  --blue:#3b82f6;--blue-dim:#1e40af;--cyan:#06b6d4;--purple:#8b5cf6;
  --emerald:#10b981;--red:#ef4444;--amber:#f59e0b;
  --text:#e2e8f0;--text-dim:#94a3b8;--text-muted:#475569;
  --border:rgba(255,255,255,0.07);--border-hi:rgba(59,130,246,0.5);
  --panel:rgba(255,255,255,0.03);--panel-hi:rgba(59,130,246,0.06);
}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden}

canvas#matrix{position:fixed;inset:0;z-index:0;opacity:.18}
.scanlines{pointer-events:none;position:fixed;inset:0;background:repeating-linear-gradient(to bottom,transparent 0,transparent 3px,rgba(0,0,0,.06) 3px,rgba(0,0,0,.06) 4px);z-index:1}
.content{position:relative;z-index:2;width:100%;max-width:440px;padding:24px}

.brand-area{text-align:center;margin-bottom:36px}
.brand-icon{width:56px;height:56px;background:linear-gradient(135deg,var(--blue),var(--purple));border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;box-shadow:0 0 30px rgba(59,130,246,.4),0 8px 20px rgba(0,0,0,.5)}
.brand-icon svg{width:28px;height:28px;fill:white}
.brand-title{font-family:'Orbitron',monospace;font-size:1.5rem;font-weight:900;letter-spacing:.15em;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.brand-sub{font-size:.75rem;color:var(--text-muted);letter-spacing:.2em;margin-top:6px;text-transform:uppercase}

.card{background:rgba(13,14,26,0.8);border:1px solid var(--border);border-radius:16px;padding:32px;backdrop-filter:blur(20px);box-shadow:0 20px 60px rgba(0,0,0,.6),inset 0 1px 0 rgba(255,255,255,.05)}
.card-glow{position:absolute;inset:-1px;border-radius:16px;background:linear-gradient(135deg,rgba(59,130,246,.15),transparent 50%,rgba(139,92,246,.1));pointer-events:none;border:1px solid transparent}

.tabs{display:flex;background:rgba(0,0,0,.3);border-radius:10px;padding:4px;margin-bottom:28px;gap:4px}
.tab{flex:1;padding:9px;text-align:center;border-radius:8px;cursor:pointer;font-size:.82rem;font-weight:500;letter-spacing:.05em;color:var(--text-muted);transition:all .2s;text-decoration:none;display:block}
.tab.active{background:linear-gradient(135deg,var(--blue-dim),rgba(139,92,246,.4));color:var(--text);box-shadow:0 2px 8px rgba(59,130,246,.3)}
.tab:hover:not(.active){color:var(--text-dim)}

.field{margin-bottom:16px}
label{display:block;font-size:.75rem;font-weight:500;color:var(--text-dim);letter-spacing:.08em;margin-bottom:8px;text-transform:uppercase}
input{width:100%;background:rgba(0,0,0,.4);border:1px solid var(--border);border-radius:8px;padding:11px 14px;color:var(--text);font-family:'JetBrains Mono',monospace;font-size:.9rem;outline:none;transition:all .2s}
input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(59,130,246,.12);background:rgba(0,0,0,.5)}
input::placeholder{color:var(--text-muted)}

.btn-primary{width:100%;margin-top:8px;padding:13px;background:linear-gradient(135deg,var(--blue),var(--purple));border:none;border-radius:10px;color:#fff;font-family:'Orbitron',monospace;font-size:.82rem;font-weight:800;letter-spacing:.15em;cursor:pointer;transition:all .3s;box-shadow:0 4px 20px rgba(59,130,246,.3);text-transform:uppercase}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 28px rgba(59,130,246,.45)}
.btn-primary:active{transform:translateY(0)}

.error-box{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);border-radius:8px;padding:10px 14px;font-size:.82rem;color:#fca5a5;margin-bottom:18px;display:flex;align-items:center;gap:8px}

.footer-links{text-align:center;margin-top:20px;font-size:.78rem;color:var(--text-muted);display:flex;justify-content:center;gap:20px}
.footer-links a{color:var(--cyan);text-decoration:none;transition:color .2s}
.footer-links a:hover{color:#67e8f9}

.badge{display:inline-block;background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.25);color:var(--emerald);font-size:.68rem;padding:2px 8px;border-radius:20px;letter-spacing:.08em;margin-left:8px;vertical-align:middle}

@keyframes pulse-border{0%,100%{box-shadow:0 20px 60px rgba(0,0,0,.6),0 0 0 0 rgba(59,130,246,.3)}50%{box-shadow:0 20px 60px rgba(0,0,0,.6),0 0 0 4px rgba(59,130,246,.1)}}
.card{animation:pulse-border 4s ease-in-out infinite}
</style>
</head>
<body>
<canvas id="matrix"></canvas>
<div class="scanlines"></div>

<div class="content">
  <div class="brand-area">
    <div class="brand-icon">
      <svg viewBox="0 0 24 24"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
    </div>
    <div class="brand-title">VECTORSCOPE</div>
    <div class="brand-sub">CTF Challenge Platform <span class="badge">LIVE</span></div>
  </div>

  <div class="card" style="position:relative">
    <div class="card-glow"></div>
    <div class="tabs">
      <a class="tab <?= $mode==='login'?'active':'' ?>" href="?mode=login">Sign In</a>
      <a class="tab <?= $mode==='register'?'active':'' ?>" href="?mode=register">Register</a>
    </div>

    <?php if ($error): ?>
      <div class="error-box">
        <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="#ef4444"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
        <?= htmlspecialchars($error) ?>
      </div>
    <?php endif; ?>

    <?php if ($mode === 'register'): ?>
      <form method="POST">
        <input type="hidden" name="action" value="register">
        <div class="field">
          <label>Callsign (Username)</label>
          <input type="text" name="username" autocomplete="off" required placeholder="your_handle">
        </div>
        <div class="field">
          <label>Access Code (Password)</label>
          <input type="password" name="password" required placeholder="minimum 6 characters">
        </div>
        <button type="submit" class="btn-primary">Create Account</button>
      </form>
    <?php else: ?>
      <form method="POST">
        <input type="hidden" name="action" value="login">
        <div class="field">
          <label>Operator ID</label>
          <input type="text" name="username" autocomplete="off" required placeholder="username" autofocus>
        </div>
        <div class="field">
          <label>Access Code</label>
          <input type="password" name="password" required placeholder="••••••••">
        </div>
        <button type="submit" class="btn-primary">Authenticate</button>
      </form>
    <?php endif; ?>
  </div>

  <div class="footer-links">
    <a href="leaderboard.php">Leaderboard</a>
    <a href="../home.php" style="color:var(--text-muted)">Target System</a>
  </div>
</div>

<script>
const canvas = document.getElementById('matrix');
const ctx = canvas.getContext('2d');
function resize() { canvas.width = innerWidth; canvas.height = innerHeight; }
resize();
window.addEventListener('resize', resize);
const chars = 'アイウエオカキクケコ0123456789ABCDEFabcdef{}[]<>/\\|=+-*'.split('');
const colors = ['#3b82f6','#06b6d4','#8b5cf6','#60a5fa','#a78bfa','#22d3ee'];
const cols = Math.floor(canvas.width / 18);
const drops = Array.from({length: cols}, () => Math.random() * -100);
const dropColors = drops.map(() => colors[Math.floor(Math.random() * colors.length)]);
function draw() {
  ctx.fillStyle = 'rgba(6,7,15,0.05)';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  drops.forEach((y, i) => {
    ctx.fillStyle = dropColors[i];
    ctx.font = '13px monospace';
    ctx.fillText(chars[Math.floor(Math.random() * chars.length)], i * 18, y * 18);
    if (y * 18 > canvas.height && Math.random() > 0.975) {
      drops[i] = 0;
      dropColors[i] = colors[Math.floor(Math.random() * colors.length)];
    }
    drops[i]++;
  });
}
setInterval(draw, 45);
</script>
</body>
</html>
