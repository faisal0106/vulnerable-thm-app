<?php
require_once 'config.php';
require_admin();

$msg = ''; $msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'reset_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid) {
            $pdb->prepare("DELETE FROM flag_captures WHERE user_id=?")->execute([$uid]);
            $ur = $pdb->prepare("SELECT username FROM portal_users WHERE id=?"); $ur->execute([$uid]);
            $urow = $ur->fetch(PDO::FETCH_ASSOC);
            $msg = 'Score reset for: ' . ($urow['username'] ?? $uid); $msg_type = 'warn';
        }
    } elseif ($action === 'reset_all') {
        $pdb->exec("DELETE FROM flag_captures");
        $msg = 'All scores reset — challenge restarted.'; $msg_type = 'error';
    } elseif ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid) {
            $pdb->prepare("DELETE FROM flag_captures WHERE user_id=?")->execute([$uid]);
            $pdb->prepare("DELETE FROM portal_users WHERE id=? AND is_admin=0")->execute([$uid]);
            $msg = 'Operator deleted.'; $msg_type = 'warn';
        }
    }
}

$stmt = $pdb->query("SELECT u.id, u.username, u.created_at, COUNT(fc.id) as flag_count FROM portal_users u LEFT JOIN flag_captures fc ON fc.user_id=u.id WHERE u.is_admin=0 GROUP BY u.id ORDER BY flag_count DESC, u.created_at ASC");
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);
$full_data = [];
foreach ($players as $p) { $sd = get_user_score($pdb, $p['id']); $full_data[] = array_merge($p, $sd); }
usort($full_data, function($a,$b){ if($b['score']!==$a['score']) return $b['score']-$a['score']; return $a['elapsed']-$b['elapsed']; });
$total_flags_captured = $pdb->query("SELECT COUNT(*) FROM flag_captures")->fetchColumn();
$total_players = count($full_data);
$top_scorer = !empty($full_data) ? $full_data[0]['username'] : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin Console — VectorScope CTF</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&family=Orbitron:wght@600;800;900&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#06070f;--bg2:#0d0e1a;
  --blue:#3b82f6;--cyan:#06b6d4;--purple:#8b5cf6;--emerald:#10b981;
  --amber:#f59e0b;--red:#ef4444;--gold:#fbbf24;
  --text:#e2e8f0;--text-dim:#94a3b8;--text-muted:#475569;
  --border:rgba(255,255,255,0.07);--panel:rgba(255,255,255,0.03);
}
body{background:var(--bg);color:var(--text);font-family:'Inter',sans-serif;min-height:100vh}
canvas#matrix{position:fixed;inset:0;z-index:0;opacity:.06}
.scanlines{pointer-events:none;position:fixed;inset:0;background:repeating-linear-gradient(to bottom,transparent 0,transparent 3px,rgba(0,0,0,.05) 3px,rgba(0,0,0,.05) 4px);z-index:1}
.topbar{position:sticky;top:0;z-index:100;background:rgba(6,7,15,.95);backdrop-filter:blur(20px);border-bottom:1px solid rgba(139,92,246,.15);padding:0 32px;height:60px;display:flex;align-items:center;justify-content:space-between}
.logo{font-family:'Orbitron',monospace;font-size:.9rem;font-weight:900;letter-spacing:.2em;background:linear-gradient(135deg,#a78bfa,#60a5fa);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
.nav-links{display:flex;gap:6px;align-items:center}
.nav-btn{padding:6px 14px;border-radius:8px;font-size:.78rem;font-weight:500;text-decoration:none;transition:all .2s;color:var(--text-dim)}
.nav-btn:hover{background:rgba(255,255,255,.05);color:var(--text)}
.nav-btn.danger{color:rgba(239,68,68,.7)} .nav-btn.danger:hover{background:rgba(239,68,68,.08);color:var(--red)}
.main{max-width:1200px;margin:0 auto;padding:32px 24px;position:relative;z-index:2}
.page-title{font-family:'Orbitron',monospace;font-size:1.2rem;font-weight:900;color:var(--text);margin-bottom:4px}
.page-sub{font-size:.78rem;color:var(--text-muted);margin-bottom:28px}
.stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px}
.stat{background:rgba(13,14,26,.7);border:1px solid var(--border);border-radius:12px;padding:20px;text-align:center}
.stat-val{font-family:'Orbitron',monospace;font-size:1.8rem;font-weight:900;margin-bottom:6px}
.stat-val.p{color:var(--purple);text-shadow:0 0 20px rgba(139,92,246,.4)}
.stat-val.b{color:var(--blue);text-shadow:0 0 20px rgba(59,130,246,.4)}
.stat-val.c{color:var(--cyan);text-shadow:0 0 20px rgba(6,182,212,.4)}
.stat-val.g{color:var(--gold);text-shadow:0 0 20px rgba(251,191,36,.4);font-size:1rem}
.stat-label{font-size:.68rem;color:var(--text-muted);letter-spacing:.1em;text-transform:uppercase}
.section-label{font-size:.72rem;font-weight:600;color:var(--text-muted);letter-spacing:.12em;text-transform:uppercase;margin-bottom:14px;display:flex;align-items:center;gap:8px}
.section-label::before{content:'';width:3px;height:13px;background:linear-gradient(to bottom,var(--purple),var(--blue));border-radius:2px}
.table-card{background:rgba(13,14,26,.7);border:1px solid var(--border);border-radius:14px;overflow:hidden;margin-bottom:24px}
.t-head{display:grid;grid-template-columns:52px 1fr 100px 90px 120px 110px 160px;padding:12px 20px;border-bottom:1px solid var(--border);font-size:.68rem;color:var(--text-muted);letter-spacing:.1em;text-transform:uppercase}
.t-row{display:grid;grid-template-columns:52px 1fr 100px 90px 120px 110px 160px;padding:13px 20px;border-bottom:1px solid rgba(255,255,255,.03);align-items:center;font-size:.83rem;cursor:pointer;transition:background .15s}
.t-row:hover{background:rgba(255,255,255,.02)}
.t-row:last-child{border-bottom:none}
.rank-n{font-family:'Orbitron',monospace;font-size:.8rem;color:var(--text-muted)}
.score-c{font-family:'Orbitron',monospace;font-weight:700;color:var(--amber)}
.btn-xs{padding:5px 12px;border-radius:6px;font-size:.73rem;font-weight:500;cursor:pointer;border:1px solid;transition:all .2s;font-family:'Inter',sans-serif}
.btn-reset-xs{border-color:rgba(245,158,11,.3);color:var(--amber);background:transparent}
.btn-reset-xs:hover{background:rgba(245,158,11,.08)}
.btn-del-xs{border-color:rgba(239,68,68,.3);color:var(--red);background:transparent}
.btn-del-xs:hover{background:rgba(239,68,68,.08)}
.btn-danger-lg{padding:11px 22px;background:transparent;border:1px solid rgba(239,68,68,.35);border-radius:10px;color:var(--red);font-family:'Orbitron',monospace;font-size:.75rem;letter-spacing:.12em;cursor:pointer;transition:all .2s}
.btn-danger-lg:hover{background:rgba(239,68,68,.08);box-shadow:0 0 20px rgba(239,68,68,.15)}
.card{background:rgba(13,14,26,.7);border:1px solid var(--border);border-radius:14px;padding:24px;margin-bottom:20px}
.alert{padding:12px 16px;border-radius:8px;font-size:.83rem;margin-bottom:20px}
.alert.success{background:rgba(16,185,129,.08);border:1px solid rgba(16,185,129,.25);color:#6ee7b7}
.alert.error{background:rgba(239,68,68,.08);border:1px solid rgba(239,68,68,.25);color:#fca5a5}
.alert.warn{background:rgba(245,158,11,.08);border:1px solid rgba(245,158,11,.25);color:#fcd34d}
.dots{display:flex;gap:4px}
.dot{width:10px;height:10px;border-radius:2px}
.dot.on{background:linear-gradient(135deg,var(--blue),var(--cyan));box-shadow:0 0 4px rgba(59,130,246,.5)}
.dot.off{background:rgba(255,255,255,.05);border:1px solid var(--border)}
.expand-row{display:none;padding:14px 20px 18px;background:rgba(0,0,0,.3);border-bottom:1px solid rgba(255,255,255,.03)}
.expand-row.open{display:block}
.flag-log-row{display:flex;justify-content:space-between;padding:7px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.78rem}
.flag-log-row:last-child{border-bottom:none}
.flag-log-row.hit{color:var(--emerald)}
.flag-log-row.miss{color:var(--text-muted)}
@media(max-width:900px){.t-head,.t-row{grid-template-columns:42px 1fr 80px 80px 80px}}
</style>
</head>
<body>
<canvas id="matrix"></canvas>
<div class="scanlines"></div>

<div class="topbar">
  <div class="logo">ADMIN CONSOLE</div>
  <div class="nav-links">
    <span style="font-size:.78rem;color:var(--text-muted)">admin</span>
    <a href="leaderboard.php" class="nav-btn">Leaderboard</a>
    <a href="logout.php" class="nav-btn danger">Logout</a>
  </div>
</div>

<div class="main">
  <div class="page-title">Challenge Administration</div>
  <div class="page-sub">Score management · Player oversight · Reset controls</div>

  <?php if ($msg): ?>
    <div class="alert <?= $msg_type ?>"><?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="stat-grid">
    <div class="stat"><div class="stat-val p"><?= $total_players ?></div><div class="stat-label">Operators</div></div>
    <div class="stat"><div class="stat-val b"><?= $total_flags_captured ?></div><div class="stat-label">Flags Captured</div></div>
    <div class="stat"><div class="stat-val c"><?= count(KNOWN_FLAGS) ?></div><div class="stat-label">Flags in Challenge</div></div>
    <div class="stat"><div class="stat-val g"><?= htmlspecialchars(strtoupper($top_scorer)) ?></div><div class="stat-label">Current Leader</div></div>
  </div>

  <div class="section-label" style="margin-bottom:14px">Operator Rankings</div>
  <div class="table-card">
    <div class="t-head">
      <div>Rank</div><div>Operator</div><div>Score</div><div>Flags</div><div>Time</div><div>Progress</div><div>Actions</div>
    </div>
    <?php if (empty($full_data)): ?>
      <div style="text-align:center;padding:40px;color:var(--text-muted);font-size:.85rem">No operators registered yet.</div>
    <?php endif; ?>
    <?php foreach ($full_data as $i => $p): ?>
      <?php $rank = $i + 1; $cap_names = array_column($p['captures'], 'flag_name'); ?>
      <div class="t-row" onclick="toggleRow(<?= $p['id'] ?>)">
        <div class="rank-n">#<?= str_pad($rank,2,'0',STR_PAD_LEFT) ?></div>
        <div style="font-weight:500"><?= htmlspecialchars($p['username']) ?></div>
        <div class="score-c"><?= $p['score'] ?></div>
        <div style="color:var(--cyan);font-size:.82rem"><?= $p['flag_count'] ?>/<?= count(KNOWN_FLAGS) ?></div>
        <div style="color:var(--text-muted);font-family:'JetBrains Mono',monospace;font-size:.75rem"><?= format_time($p['elapsed']) ?></div>
        <div><div class="dots"><?php foreach (KNOWN_FLAGS as $fv => $meta): ?><div class="dot <?= in_array($meta['name'],$cap_names)?'on':'off' ?>" title="<?= htmlspecialchars($meta['label']) ?>"></div><?php endforeach; ?></div></div>
        <div style="display:flex;gap:8px" onclick="event.stopPropagation()">
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="reset_user"><input type="hidden" name="user_id" value="<?= $p['id'] ?>">
            <button class="btn-xs btn-reset-xs" onclick="return confirm('Reset scores for <?= htmlspecialchars($p['username']) ?>?')">Reset</button>
          </form>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete_user"><input type="hidden" name="user_id" value="<?= $p['id'] ?>">
            <button class="btn-xs btn-del-xs" onclick="return confirm('Delete <?= htmlspecialchars($p['username']) ?>?')">Delete</button>
          </form>
        </div>
      </div>
      <div class="expand-row" id="expand-<?= $p['id'] ?>">
        <div style="font-size:.72rem;color:var(--purple);letter-spacing:.1em;text-transform:uppercase;margin-bottom:10px">Flag Capture Log — <?= htmlspecialchars($p['username']) ?></div>
        <?php $cm=[]; foreach($p['captures'] as $c) $cm[$c['flag_name']]=$c['captured_at']; ?>
        <?php foreach (KNOWN_FLAGS as $fv => $meta): ?>
          <div class="flag-log-row <?= isset($cm[$meta['name']])?'hit':'miss' ?>">
            <span><?= isset($cm[$meta['name']]) ? '✓ +' . $meta['points'] . 'pts' : '✗ ---' ?> &nbsp; <?= htmlspecialchars($meta['label']) ?></span>
            <span style="font-family:'JetBrains Mono',monospace"><?= isset($cm[$meta['name']]) ? date('Y-m-d H:i:s',$cm[$meta['name']]) : 'not captured' ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card" style="border-color:rgba(239,68,68,.15)">
    <div class="section-label" style="margin-bottom:14px;color:rgba(239,68,68,.7)">Danger Zone</div>
    <p style="color:var(--text-muted);font-size:.82rem;margin-bottom:16px;line-height:1.6">Resetting all scores permanently erases every player's flag captures. This cannot be undone.</p>
    <form method="POST" onsubmit="return confirm('Reset ALL scores for ALL operators? This cannot be undone.')">
      <input type="hidden" name="action" value="reset_all">
      <button type="submit" class="btn-danger-lg">⚠ Reset All Scores</button>
    </form>
  </div>

  <div class="card">
    <div class="section-label" style="margin-bottom:14px">Flag Registry</div>
    <?php foreach (KNOWN_FLAGS as $fval => $meta): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.04);font-size:.82rem">
        <span style="font-weight:500"><?= htmlspecialchars($meta['label']) ?></span>
        <span style="font-family:'JetBrains Mono',monospace;color:var(--text-muted)"><?= htmlspecialchars($fval) ?></span>
        <span style="color:var(--amber);font-family:'JetBrains Mono',monospace">+<?= $meta['points'] ?>pts</span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
const canvas = document.getElementById('matrix');
const ctx = canvas.getContext('2d');
function resize() { canvas.width = innerWidth; canvas.height = innerHeight; }
resize(); window.addEventListener('resize', resize);
const chars = '01アBCDF{}[]'.split('');
const colors = ['#8b5cf6','#3b82f6','#06b6d4'];
const cols = Math.floor(canvas.width / 22);
const drops = Array.from({length: cols}, () => Math.random() * -100);
const dc = drops.map(() => colors[Math.floor(Math.random() * colors.length)]);
function draw() {
  ctx.fillStyle = 'rgba(6,7,15,.06)'; ctx.fillRect(0,0,canvas.width,canvas.height);
  drops.forEach((y,i) => { ctx.fillStyle=dc[i]; ctx.font='12px monospace';
    ctx.fillText(chars[Math.floor(Math.random()*chars.length)],i*22,y*22);
    if(y*22>canvas.height&&Math.random()>.975){drops[i]=0;dc[i]=colors[Math.floor(Math.random()*colors.length)];} drops[i]++; }); }
setInterval(draw, 55);
function toggleRow(id) { document.getElementById('expand-'+id).classList.toggle('open'); }
</script>
</body>
</html>
