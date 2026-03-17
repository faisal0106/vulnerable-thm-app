<?php
require_once 'config.php';

$stmt = $pdb->query("
    SELECT u.id, u.username, u.created_at, COUNT(fc.id) as flag_count,
           MIN(fc.captured_at) as first_cap, MAX(fc.captured_at) as last_cap
    FROM portal_users u LEFT JOIN flag_captures fc ON fc.user_id=u.id
    WHERE u.is_admin=0 GROUP BY u.id
    ORDER BY flag_count DESC, last_cap ASC, u.created_at ASC
");
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
$leaderboard = [];
foreach ($players as $p) {
    $sd = get_user_score($pdb, $p['id']);
    $leaderboard[] = array_merge($p, ['score'=>$sd['score'],'elapsed'=>$sd['elapsed'],'captures'=>$sd['captures']]);
}
usort($leaderboard, function($a,$b){ if($b['score']!==$a['score']) return $b['score']-$a['score']; if($a['elapsed']!==$b['elapsed']) return $a['elapsed']-$b['elapsed']; return $a['created_at']-$b['created_at']; });
$total_players = count($leaderboard);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Leaderboard — VectorScope CTF</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Orbitron:wght@600;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#06070f;--bg2:#0d0e1a;
  --blue:#3b82f6;--cyan:#06b6d4;--purple:#8b5cf6;--emerald:#10b981;
  --amber:#f59e0b;--red:#ef4444;--gold:#fbbf24;--silver:#94a3b8;--bronze:#d97706;
  --text:#e2e8f0;--text-dim:#94a3b8;--text-muted:#475569;
  --border:rgba(255,255,255,0.07);--panel:rgba(255,255,255,0.03);
}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh}
canvas#matrix{position:fixed;inset:0;z-index:0;opacity:.07}
.scanlines{pointer-events:none;position:fixed;inset:0;background:repeating-linear-gradient(to bottom,transparent 0,transparent 3px,rgba(0,0,0,.05) 3px,rgba(0,0,0,.05) 4px);z-index:1}
.topbar{position:sticky;top:0;z-index:100;background:rgba(6,7,15,.92);backdrop-filter:blur(20px);border-bottom:1px solid var(--border);padding:0 32px;height:60px;display:flex;align-items:center;justify-content:space-between}
.logo{font-family:'Orbitron',monospace;font-size:.9rem;font-weight:900;letter-spacing:.2em;background:linear-gradient(135deg,#60a5fa,#a78bfa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.nav-links{display:flex;gap:6px}
.nav-btn{padding:6px 14px;border-radius:8px;font-size:.78rem;font-weight:500;text-decoration:none;transition:all .2s;color:var(--text-dim)}
.nav-btn:hover{background:rgba(255,255,255,.05);color:var(--text)}
.nav-btn.primary{background:linear-gradient(135deg,rgba(59,130,246,.2),rgba(139,92,246,.2));border:1px solid rgba(59,130,246,.3);color:var(--blue)}
.main{max-width:1000px;margin:0 auto;padding:40px 24px;position:relative;z-index:2}

.hero{text-align:center;margin-bottom:40px}
.hero-title{font-family:'Orbitron',monospace;font-size:2rem;font-weight:900;letter-spacing:.2em;background:linear-gradient(135deg,#60a5fa,#a78bfa,#22d3ee);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;margin-bottom:8px}
.hero-sub{font-size:.8rem;color:var(--text-muted);letter-spacing:.15em;text-transform:uppercase}
.live-dot{display:inline-block;width:7px;height:7px;background:var(--emerald);border-radius:50%;margin-right:6px;animation:pulse 2s ease-in-out infinite;box-shadow:0 0 6px var(--emerald)}
@keyframes pulse{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.6;transform:scale(.85)}}

.stats-row{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:32px}
.stat-card{background:rgba(13,14,26,.7);border:1px solid var(--border);border-radius:14px;padding:22px;text-align:center;backdrop-filter:blur(8px)}
.stat-val{font-family:'Orbitron',monospace;font-size:2rem;font-weight:900;margin-bottom:4px}
.stat-val.blue{color:var(--blue);text-shadow:0 0 20px rgba(59,130,246,.4)}
.stat-val.purple{color:var(--purple);text-shadow:0 0 20px rgba(139,92,246,.4)}
.stat-val.amber{color:var(--amber);text-shadow:0 0 20px rgba(245,158,11,.4)}
.stat-label{font-size:.7rem;color:var(--text-muted);letter-spacing:.1em;text-transform:uppercase}

.table-card{background:rgba(13,14,26,.7);border:1px solid var(--border);border-radius:16px;overflow:hidden;backdrop-filter:blur(8px)}
.table-head{display:grid;grid-template-columns:64px 1fr 110px 100px 120px 130px;padding:14px 24px;border-bottom:1px solid var(--border);font-size:.7rem;color:var(--text-muted);letter-spacing:.12em;text-transform:uppercase}
.table-row{display:grid;grid-template-columns:64px 1fr 110px 100px 120px 130px;padding:16px 24px;border-bottom:1px solid rgba(255,255,255,.03);align-items:center;transition:background .15s}
.table-row:hover{background:rgba(255,255,255,.02)}
.table-row:last-child{border-bottom:none}
.rank-badge{font-family:'Orbitron',monospace;font-size:.85rem;font-weight:900;width:36px;height:36px;border-radius:10px;display:flex;align-items:center;justify-content:center}
.rank-1{background:linear-gradient(135deg,rgba(251,191,36,.2),rgba(251,191,36,.05));color:var(--gold);border:1px solid rgba(251,191,36,.3);box-shadow:0 0 12px rgba(251,191,36,.2)}
.rank-2{background:linear-gradient(135deg,rgba(148,163,184,.15),rgba(148,163,184,.03));color:var(--silver);border:1px solid rgba(148,163,184,.25)}
.rank-3{background:linear-gradient(135deg,rgba(217,119,6,.15),rgba(217,119,6,.03));color:var(--bronze);border:1px solid rgba(217,119,6,.25)}
.rank-n{background:rgba(255,255,255,.03);color:var(--text-muted);border:1px solid var(--border)}
.username{font-size:.9rem;font-weight:500;display:flex;align-items:center;gap:8px}
.score-cell{font-family:'Orbitron',monospace;font-size:.95rem;font-weight:700;color:var(--amber)}
.flags-cell{font-size:.85rem;color:var(--cyan)}
.time-cell{font-family:'JetBrains Mono',monospace;font-size:.78rem;color:var(--text-muted)}
.dots{display:flex;gap:5px;align-items:center}
.dot{width:11px;height:11px;border-radius:3px;transition:all .2s}
.dot.on{background:linear-gradient(135deg,var(--blue),var(--cyan));box-shadow:0 0 6px rgba(59,130,246,.5)}
.dot.off{background:rgba(255,255,255,.06);border:1px solid var(--border)}
.empty-state{text-align:center;padding:64px 20px;color:var(--text-muted)}
.empty-state p{font-size:.9rem;margin-bottom:16px}
@media(max-width:700px){.table-head,.table-row{grid-template-columns:48px 1fr 80px 80px}.time-cell{display:none}.dots{display:none}}
</style>
</head>
<body>
<canvas id="matrix"></canvas>
<div class="scanlines"></div>

<div class="topbar">
  <div class="logo">VECTORSCOPE CTF</div>
  <div class="nav-links">
    <?php if (is_logged_in()): ?>
      <a href="dashboard.php" class="nav-btn primary">Dashboard</a>
    <?php else: ?>
      <a href="login.php" class="nav-btn primary">Login / Register</a>
    <?php endif; ?>
    <a href="../home.php" class="nav-btn">Target System</a>
  </div>
</div>

<div class="main">
  <div class="hero">
    <div class="hero-title">LEADERBOARD</div>
    <div class="hero-sub"><span class="live-dot"></span>Live Rankings · <?= $total_players ?> Operators</div>
  </div>

  <div class="stats-row">
    <div class="stat-card">
      <div class="stat-val blue"><?= $total_players ?></div>
      <div class="stat-label">Operators</div>
    </div>
    <div class="stat-card">
      <div class="stat-val purple"><?= count(KNOWN_FLAGS) ?></div>
      <div class="stat-label">Total Flags</div>
    </div>
    <div class="stat-card">
      <div class="stat-val amber"><?= MAX_SCORE ?></div>
      <div class="stat-label">Max Score</div>
    </div>
  </div>

  <div class="table-card">
    <div class="table-head">
      <div>Rank</div>
      <div>Operator</div>
      <div>Score</div>
      <div>Flags</div>
      <div>Time</div>
      <div>Progress</div>
    </div>

    <?php if (empty($leaderboard)): ?>
      <div class="empty-state">
        <p>No operators registered yet.</p>
        <a href="login.php?mode=register" style="color:var(--blue);text-decoration:none;font-size:.82rem">Be the first to join →</a>
      </div>
    <?php endif; ?>

    <?php foreach ($leaderboard as $i => $p): ?>
      <?php $r = $i + 1; $cap_names = array_column($p['captures'], 'flag_name'); ?>
      <div class="table-row">
        <div>
          <div class="rank-badge <?= $r===1?'rank-1':($r===2?'rank-2':($r===3?'rank-3':'rank-n')) ?>">
            <?= $r <= 9 ? "#0$r" : "#$r" ?>
          </div>
        </div>
        <div class="username">
          <?php if ($r===1): ?><span style="color:var(--gold)">★</span><?php elseif($r===2): ?><span style="color:var(--silver)">✦</span><?php elseif($r===3): ?><span style="color:var(--bronze)">✧</span><?php endif; ?>
          <?= htmlspecialchars($p['username']) ?>
        </div>
        <div class="score-cell"><?= $p['score'] ?></div>
        <div class="flags-cell"><?= $p['flag_count'] ?>/<?= count(KNOWN_FLAGS) ?></div>
        <div class="time-cell"><?= format_time($p['elapsed']) ?></div>
        <div>
          <div class="dots">
            <?php foreach (KNOWN_FLAGS as $fv => $meta): ?>
              <div class="dot <?= in_array($meta['name'], $cap_names) ? 'on' : 'off' ?>" title="<?= htmlspecialchars($meta['label']) ?>"></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="text-align:center;margin-top:24px;font-size:.78rem;color:var(--text-muted)">
    Auto-refreshes every 60 seconds ·
    <?php if (!is_logged_in()): ?>
      <a href="login.php?mode=register" style="color:var(--blue);text-decoration:none">Join the challenge →</a>
    <?php else: ?>
      <a href="dashboard.php" style="color:var(--blue);text-decoration:none">Back to dashboard →</a>
    <?php endif; ?>
  </div>
</div>
<script>
const canvas = document.getElementById('matrix');
const ctx = canvas.getContext('2d');
function resize() { canvas.width = innerWidth; canvas.height = innerHeight; }
resize(); window.addEventListener('resize', resize);
const chars = '01アBCDEF{}[]'.split('');
const colors = ['#3b82f6','#06b6d4','#8b5cf6'];
const cols = Math.floor(canvas.width / 22);
const drops = Array.from({length: cols}, () => Math.random() * -100);
const dc = drops.map(() => colors[Math.floor(Math.random() * colors.length)]);
function draw() {
  ctx.fillStyle = 'rgba(6,7,15,.06)'; ctx.fillRect(0, 0, canvas.width, canvas.height);
  drops.forEach((y, i) => { ctx.fillStyle = dc[i]; ctx.font = '13px monospace';
    ctx.fillText(chars[Math.floor(Math.random() * chars.length)], i * 22, y * 22);
    if (y * 22 > canvas.height && Math.random() > 0.975) { drops[i] = 0; dc[i] = colors[Math.floor(Math.random() * colors.length)]; } drops[i]++; }); }
setInterval(draw, 55);
setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>
