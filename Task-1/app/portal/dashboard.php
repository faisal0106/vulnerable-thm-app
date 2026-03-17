<?php
require_once 'config.php';
require_login();

$uid = $_SESSION['portal_user_id'];
$username = $_SESSION['portal_username'];
$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['flag'])) {
    $submitted = strtoupper(trim($_POST['flag']));
    if (isset(KNOWN_FLAGS[$submitted])) {
        $meta = KNOWN_FLAGS[$submitted];
        $already = $pdb->prepare("SELECT id FROM flag_captures WHERE user_id=? AND flag_name=?");
        $already->execute([$uid, $meta['name']]);
        if ($already->fetch()) {
            $msg = 'Already captured: ' . $meta['label'];
            $msg_type = 'warn';
        } else {
            $pdb->prepare("INSERT INTO flag_captures (user_id, flag_name, flag_value) VALUES (?,?,?)")
                ->execute([$uid, $meta['name'], $submitted]);
            $msg = '+' . $meta['points'] . ' pts — ' . $meta['label'];
            $msg_type = 'success';
        }
    } else {
        $msg = 'Invalid flag — not recognized in the database.';
        $msg_type = 'error';
    }
}

$stats = get_user_score($pdb, $uid);
$captures_map = [];
foreach ($stats['captures'] as $c) {
    $captures_map[$c['flag_name']] = $c['captured_at'];
}

$rank_stmt = $pdb->query("
    SELECT u.id, SUM(CASE WHEN fc.flag_name IS NOT NULL THEN (
        SELECT points FROM (
            SELECT 'login_flag' AS fn, 100 AS points UNION ALL SELECT 'xss_flag',100 UNION ALL
            SELECT 'order_flag',150 UNION ALL SELECT 'idor_flag',100 UNION ALL SELECT 'debug_flag',50
        ) p WHERE p.fn=fc.flag_name) ELSE 0 END) as score
    FROM portal_users u LEFT JOIN flag_captures fc ON fc.user_id=u.id
    WHERE u.is_admin=0 GROUP BY u.id
    ORDER BY score DESC, (SELECT MIN(captured_at) FROM flag_captures WHERE user_id=u.id) ASC
");
$rank = 1;
while ($r = $rank_stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($r['id'] == $uid) break;
    $rank++;
}
$pct = $stats['count'] > 0 ? round($stats['count'] / count(KNOWN_FLAGS) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — VectorScope CTF</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Orbitron:wght@600;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#06070f;--bg2:#0d0e1a;
  --blue:#3b82f6;--blue-dim:#1e40af;--cyan:#06b6d4;--purple:#8b5cf6;
  --emerald:#10b981;--red:#ef4444;--amber:#f59e0b;
  --text:#e2e8f0;--text-dim:#94a3b8;--text-muted:#475569;
  --border:rgba(255,255,255,0.07);--panel:rgba(255,255,255,0.03);
}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh}
canvas#matrix{position:fixed;inset:0;z-index:0;opacity:.08}
.scanlines{pointer-events:none;position:fixed;inset:0;background:repeating-linear-gradient(to bottom,transparent 0,transparent 3px,rgba(0,0,0,.05) 3px,rgba(0,0,0,.05) 4px);z-index:1}
.topbar{position:sticky;top:0;z-index:100;background:rgba(6,7,15,.92);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:60px;display:flex;align-items:center;justify-content:space-between}
.logo{font-family:'Orbitron',monospace;font-size:.9rem;font-weight:900;letter-spacing:.2em;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.nav-links{display:flex;gap:6px;align-items:center}
.nav-btn{padding:6px 14px;border-radius:8px;font-size:.78rem;font-weight:500;text-decoration:none;transition:all .2s;color:var(--text-dim)}
.nav-btn:hover{background:rgba(255,255,255,.05);color:var(--text)}
.nav-btn.primary{background:linear-gradient(135deg,rgba(59,130,246,.2),rgba(139,92,246,.2));border:1px solid rgba(59,130,246,.3);color:var(--blue)}
.nav-btn.danger{color:rgba(239,68,68,.7)}
.nav-btn.danger:hover{background:rgba(239,68,68,.08);color:var(--red)}
.main{max-width:1080px;margin:0 auto;padding:32px 24px;position:relative;z-index:2}
.page-header{margin-bottom:28px}
.page-title{font-family:'Orbitron',monospace;font-size:1.2rem;font-weight:900;color:var(--text)}
.page-sub{font-size:.8rem;color:var(--text-muted);margin-top:4px}
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:24px}
.stat{background:var(--panel);border:1px solid var(--border);border-radius:12px;padding:20px;text-align:center;transition:border-color .2s}
.stat:hover{border-color:rgba(59,130,246,.3)}
.stat-val{font-family:'Orbitron',monospace;font-size:1.8rem;font-weight:900;line-height:1;margin-bottom:6px}
.stat-val.blue{color:var(--blue);text-shadow:0 0 20px rgba(59,130,246,.4)}
.stat-val.cyan{color:var(--cyan);text-shadow:0 0 20px rgba(6,182,212,.4)}
.stat-val.purple{color:var(--purple);text-shadow:0 0 20px rgba(139,92,246,.4)}
.stat-val.emerald{color:var(--emerald);text-shadow:0 0 20px rgba(16,185,129,.4)}
.stat-label{font-size:.68rem;color:var(--text-muted);letter-spacing:.1em;text-transform:uppercase}
.card{background:rgba(13,14,26,.7);border:1px solid var(--border);border-radius:14px;padding:24px;margin-bottom:20px;backdrop-filter:blur(8px)}
.card-title{font-size:.72rem;font-weight:600;color:var(--text-dim);letter-spacing:.12em;text-transform:uppercase;margin-bottom:18px;display:flex;align-items:center;gap:8px}
.card-title::before{content:'';width:3px;height:14px;background:linear-gradient(to bottom,var(--blue),var(--purple));border-radius:2px;flex-shrink:0}
.submit-row{display:flex;gap:10px}
.flag-input{flex:1;background:rgba(0,0,0,.5);border:1px solid var(--border);border-radius:10px;padding:12px 16px;color:var(--text);font-family:'JetBrains Mono',monospace;font-size:.95rem;outline:none;transition:all .2s}
.flag-input:focus{border-color:var(--blue);box-shadow:0 0 0 3px rgba(59,130,246,.1)}
.flag-input::placeholder{color:var(--text-muted)}
.btn-submit{padding:12px 22px;background:linear-gradient(135deg,var(--blue),var(--purple));border:none;border-radius:10px;color:#fff;font-family:'Orbitron',monospace;font-size:.75rem;font-weight:800;letter-spacing:.1em;cursor:pointer;transition:all .2s;white-space:nowrap;box-shadow:0 4px 16px rgba(59,130,246,.3)}
.btn-submit:hover{transform:translateY(-1px);box-shadow:0 6px 22px rgba(59,130,246,.45)}
.alert{padding:12px 16px;border-radius:8px;font-size:.83rem;margin-bottom:16px;display:flex;align-items:center;gap:10px}
.alert.success{background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.alert.error{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#fca5a5}
.alert.warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);color:#fcd34d}
.progress-track{height:6px;background:rgba(255,255,255,.05);border-radius:4px;margin-bottom:20px;overflow:hidden}
.progress-fill{height:100%;background:linear-gradient(90deg,var(--blue),var(--cyan),var(--purple));border-radius:4px;transition:width .6s ease;box-shadow:0 0 10px rgba(59,130,246,.5)}
.flag-list{display:flex;flex-direction:column;gap:8px}
.flag-row{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border-radius:10px;border:1px solid var(--border);background:rgba(0,0,0,.2);transition:all .2s}
.flag-row:hover{border-color:rgba(255,255,255,.12)}
.flag-row.done{border-color:rgba(16,185,129,.25);background:rgba(16,185,129,.04)}
.flag-row.done:hover{border-color:rgba(16,185,129,.4)}
.flag-name{font-size:.87rem;font-weight:500;color:var(--text-dim)}
.flag-row.done .flag-name{color:var(--text)}
.flag-ts{font-size:.73rem;color:var(--text-muted);margin-top:3px;font-family:'JetBrains Mono',monospace}
.flag-right{display:flex;align-items:center;gap:10px;flex-shrink:0}
.flag-pts{font-family:'JetBrains Mono',monospace;font-size:.78rem;color:var(--amber)}
.badge{font-size:.68rem;padding:3px 10px;border-radius:20px;font-weight:500;letter-spacing:.05em}
.badge.done{background:rgba(16,185,129,.12);border:1px solid rgba(16,185,129,.3);color:var(--emerald)}
.badge.pending{background:rgba(255,255,255,.04);border:1px solid var(--border);color:var(--text-muted)}
.check{width:18px;height:18px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0}
.check.done{background:rgba(16,185,129,.2);color:var(--emerald)}
.check.pending{background:rgba(255,255,255,.04);color:var(--text-muted)}
@media(max-width:700px){.stat-grid{grid-template-columns:repeat(2,1fr)}.submit-row{flex-direction:column}.topbar{padding:0 16px}}
</style>
</head>
<body>
<canvas id="matrix"></canvas>
<div class="scanlines"></div>

<div class="topbar">
  <div class="logo">VECTORSCOPE CTF</div>
  <div class="nav-links">
    <span style="font-size:.78rem;color:var(--text-muted);margin-right:4px"><?= htmlspecialchars($username) ?></span>
    <a href="leaderboard.php" class="nav-btn">Leaderboard</a>
    <a href="../home.php" class="nav-btn primary">Target System →</a>
    <a href="logout.php" class="nav-btn danger">Logout</a>
  </div>
</div>

<div class="main">
  <div class="page-header">
    <div class="page-title">Operator Dashboard</div>
    <div class="page-sub">Current rank: <strong style="color:var(--blue)">#<?= $rank ?></strong> &nbsp;·&nbsp; Session active</div>
  </div>

  <div class="stat-grid">
    <div class="stat">
      <div class="stat-val blue"><?= $stats['score'] ?></div>
      <div class="stat-label">Total Score</div>
    </div>
    <div class="stat">
      <div class="stat-val cyan"><?= $stats['count'] ?><span style="font-size:1rem;opacity:.5">/<?= count(KNOWN_FLAGS) ?></span></div>
      <div class="stat-label">Flags Found</div>
    </div>
    <div class="stat">
      <div class="stat-val purple">#<?= $rank ?></div>
      <div class="stat-label">Global Rank</div>
    </div>
    <div class="stat">
      <div class="stat-val emerald" style="font-size:1.1rem"><?= format_time($stats['elapsed']) ?></div>
      <div class="stat-label">Time Elapsed</div>
    </div>
  </div>

  <div class="card">
    <div class="card-title">Submit Captured Flag</div>
    <?php if ($msg): ?>
      <div class="alert <?= $msg_type ?>">
        <?= $msg_type === 'success' ? '🚩' : ($msg_type === 'error' ? '✗' : '⚠') ?>
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>
    <form method="POST">
      <div class="submit-row">
        <input class="flag-input" type="text" name="flag" placeholder="THM{...}" autocomplete="off" autofocus>
        <button type="submit" class="btn-submit">Submit Flag</button>
      </div>
    </form>
    <div style="font-size:.75rem;color:var(--text-muted);margin-top:10px">Explore the target system, discover vulnerabilities, and submit the flags you find here.</div>
  </div>

  <div class="card">
    <div class="card-title">Mission Progress — <?= $pct ?>% complete</div>
    <div class="progress-track">
      <div class="progress-fill" style="width:<?= $pct ?>%"></div>
    </div>
    <div class="flag-list">
      <?php foreach (KNOWN_FLAGS as $fval => $meta): ?>
        <?php $cap = isset($captures_map[$meta['name']]); ?>
        <div class="flag-row <?= $cap ? 'done' : '' ?>">
          <div style="display:flex;align-items:center;gap:12px">
            <div class="check <?= $cap ? 'done' : 'pending' ?>">
              <?= $cap ? '✓' : '○' ?>
            </div>
            <div>
              <div class="flag-name"><?= htmlspecialchars($meta['label']) ?></div>
              <?php if ($cap): ?>
                <div class="flag-ts">captured <?= date('Y-m-d H:i:s', $captures_map[$meta['name']]) ?></div>
              <?php endif; ?>
            </div>
          </div>
          <div class="flag-right">
            <span class="flag-pts">+<?= $meta['points'] ?>pts</span>
            <span class="badge <?= $cap ? 'done' : 'pending' ?>"><?= $cap ? 'CAPTURED' : 'PENDING' ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
const canvas = document.getElementById('matrix');
const ctx = canvas.getContext('2d');
function resize() { canvas.width = innerWidth; canvas.height = innerHeight; }
resize(); window.addEventListener('resize', resize);
const chars = '01アイウエオABCDEF{}[]<>'.split('');
const colors = ['#3b82f6','#06b6d4','#8b5cf6','#60a5fa'];
const cols = Math.floor(canvas.width / 20);
const drops = Array.from({length: cols}, () => Math.random() * -80);
const dc = drops.map(() => colors[Math.floor(Math.random() * colors.length)]);
function draw() {
  ctx.fillStyle = 'rgba(6,7,15,.06)';
  ctx.fillRect(0, 0, canvas.width, canvas.height);
  drops.forEach((y, i) => {
    ctx.fillStyle = dc[i];
    ctx.font = '13px monospace';
    ctx.fillText(chars[Math.floor(Math.random() * chars.length)], i * 20, y * 20);
    if (y * 20 > canvas.height && Math.random() > 0.975) { drops[i] = 0; dc[i] = colors[Math.floor(Math.random() * colors.length)]; }
    drops[i]++;
  });
}
setInterval(draw, 50);
</script>
</body>
</html>
