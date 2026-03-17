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
<title>VectorScope CTF // Access Terminal</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&display=swap');
  *{box-sizing:border-box;margin:0;padding:0}
  :root{--green:#00ff41;--green-dim:#00b32c;--green-dark:#003d0c;--bg:#020d03;--panel:#040f05;--red:#ff2a2a;--yellow:#f0e130;--cyan:#00e5ff}
  body{background:var(--bg);color:var(--green);font-family:'Share Tech Mono',monospace;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;overflow:hidden}
  .scanlines{pointer-events:none;position:fixed;inset:0;background:repeating-linear-gradient(to bottom,transparent 0,transparent 2px,rgba(0,0,0,.18) 2px,rgba(0,0,0,.18) 4px);z-index:999}
  .glow{text-shadow:0 0 8px var(--green),0 0 20px var(--green-dim)}
  .title{font-family:'Orbitron',monospace;font-size:1.6rem;font-weight:900;letter-spacing:.2em;text-align:center;color:var(--green);text-shadow:0 0 12px var(--green),0 0 30px var(--green-dim);margin-bottom:4px}
  .subtitle{font-size:.75rem;color:var(--green-dim);letter-spacing:.25em;text-align:center;margin-bottom:32px}
  .terminal{background:var(--panel);border:1px solid var(--green-dim);border-radius:4px;padding:36px 40px;width:100%;max-width:420px;box-shadow:0 0 40px rgba(0,255,65,.07),inset 0 0 60px rgba(0,0,0,.4);position:relative}
  .terminal::before{content:'';position:absolute;inset:-1px;border-radius:4px;background:linear-gradient(135deg,rgba(0,255,65,.12),transparent 60%);pointer-events:none}
  .tab-bar{display:flex;gap:0;margin-bottom:28px;border-bottom:1px solid var(--green-dark)}
  .tab{flex:1;padding:8px;text-align:center;cursor:pointer;font-family:'Share Tech Mono',monospace;font-size:.85rem;letter-spacing:.1em;color:var(--green-dim);border-bottom:2px solid transparent;margin-bottom:-1px;transition:all .2s;text-decoration:none}
  .tab.active{color:var(--green);border-bottom-color:var(--green);text-shadow:0 0 8px var(--green)}
  .tab:hover{color:var(--green)}
  .prompt{font-size:.8rem;color:var(--green-dim);margin-bottom:20px}
  .prompt span{color:var(--green)}
  label{display:block;font-size:.78rem;color:var(--green-dim);letter-spacing:.12em;margin-bottom:6px;margin-top:16px}
  input{width:100%;background:rgba(0,255,65,.04);border:1px solid var(--green-dark);border-radius:3px;padding:10px 12px;color:var(--green);font-family:'Share Tech Mono',monospace;font-size:.9rem;outline:none;transition:border-color .2s,box-shadow .2s}
  input:focus{border-color:var(--green);box-shadow:0 0 10px rgba(0,255,65,.15)}
  .btn{width:100%;margin-top:24px;padding:12px;background:transparent;border:1px solid var(--green);border-radius:3px;color:var(--green);font-family:'Orbitron',monospace;font-size:.85rem;font-weight:700;letter-spacing:.2em;cursor:pointer;transition:all .3s;text-transform:uppercase}
  .btn:hover{background:rgba(0,255,65,.1);box-shadow:0 0 20px rgba(0,255,65,.2)}
  .error{background:rgba(255,42,42,.08);border:1px solid rgba(255,42,42,.4);color:var(--red);padding:10px 14px;border-radius:3px;font-size:.82rem;margin-bottom:16px;letter-spacing:.05em}
  .leaderboard-link{text-align:center;margin-top:20px;font-size:.78rem;color:var(--green-dim)}
  .leaderboard-link a{color:var(--cyan);text-decoration:none}
  .leaderboard-link a:hover{text-shadow:0 0 8px var(--cyan)}
  .blinking{animation:blink 1s step-end infinite}
  @keyframes blink{50%{opacity:0}}
  .corner{position:absolute;width:10px;height:10px;border-color:var(--green);border-style:solid}
  .corner.tl{top:-1px;left:-1px;border-width:2px 0 0 2px}
  .corner.tr{top:-1px;right:-1px;border-width:2px 2px 0 0}
  .corner.bl{bottom:-1px;left:-1px;border-width:0 0 2px 2px}
  .corner.br{bottom:-1px;right:-1px;border-width:0 2px 2px 0}
  .matrix-bg{position:fixed;inset:0;z-index:-1;opacity:.08;overflow:hidden}
</style>
</head>
<body>
<div class="scanlines"></div>
<canvas class="matrix-bg" id="matrix"></canvas>

<div style="margin-bottom:32px;text-align:center">
  <div class="title">VECTORSCOPE CTF</div>
  <div class="subtitle">// OPERATOR AUTHENTICATION TERMINAL //</div>
</div>

<div class="terminal">
  <div class="corner tl"></div><div class="corner tr"></div>
  <div class="corner bl"></div><div class="corner br"></div>

  <div class="tab-bar">
    <a class="tab <?= $mode==='login'?'active':'' ?>" href="login.php?mode=login">[ LOGIN ]</a>
    <a class="tab <?= $mode==='register'?'active':'' ?>" href="login.php?mode=register">[ REGISTER ]</a>
  </div>

  <?php if ($error): ?>
    <div class="error">&gt; ERROR: <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <?php if ($mode === 'register'): ?>
    <div class="prompt">&gt; CREATE OPERATOR ACCOUNT <span class="blinking">_</span></div>
    <form method="POST">
      <input type="hidden" name="action" value="register">
      <label>// CALLSIGN (USERNAME)</label>
      <input type="text" name="username" autocomplete="off" required placeholder="enter_callsign">
      <label>// ACCESS CODE (PASSWORD)</label>
      <input type="password" name="password" required placeholder="min 6 chars">
      <button type="submit" class="btn">// INITIALIZE ACCOUNT</button>
    </form>
  <?php else: ?>
    <div class="prompt">&gt; AUTHENTICATE TO CONTINUE <span class="blinking">_</span></div>
    <form method="POST">
      <input type="hidden" name="action" value="login">
      <label>// OPERATOR ID</label>
      <input type="text" name="username" autocomplete="off" required placeholder="enter_username">
      <label>// ACCESS CODE</label>
      <input type="password" name="password" required placeholder="••••••••">
      <button type="submit" class="btn">// AUTHENTICATE</button>
    </form>
  <?php endif; ?>

  <div class="leaderboard-link" style="margin-top:20px">
    <a href="leaderboard.php">[ VIEW LEADERBOARD ]</a> &nbsp;|&nbsp;
    <a href="../home.php" style="color:var(--green-dim)">[ ENTER TARGET SYSTEM ]</a>
  </div>
</div>

<script>
const canvas = document.getElementById('matrix');
const ctx = canvas.getContext('2d');
canvas.width = window.innerWidth;
canvas.height = window.innerHeight;
const chars = 'アカサタナハマヤラワ01アイウエオABCDEF{}[]<>/\\'.split('');
const cols = Math.floor(canvas.width / 16);
const drops = Array(cols).fill(1);
function draw() {
  ctx.fillStyle = 'rgba(2,13,3,0.05)';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  ctx.fillStyle = '#00ff41';
  ctx.font = '14px monospace';
  drops.forEach((y, i) => {
    const c = chars[Math.floor(Math.random() * chars.length)];
    ctx.fillText(c, i * 16, y * 16);
    if (y * 16 > canvas.height && Math.random() > 0.975) drops[i] = 0;
    drops[i]++;
  });
}
setInterval(draw, 50);
</script>
</body>
</html>
