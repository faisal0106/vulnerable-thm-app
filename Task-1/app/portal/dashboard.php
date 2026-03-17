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
            $msg = 'FLAG ALREADY CAPTURED: ' . $meta['label'];
            $msg_type = 'warn';
        } else {
            $pdb->prepare("INSERT INTO flag_captures (user_id, flag_name, flag_value) VALUES (?,?,?)")
                ->execute([$uid, $meta['name'], $submitted]);
            $msg = 'FLAG CAPTURED [+' . $meta['points'] . ' pts]: ' . $meta['label'];
            $msg_type = 'success';
        }
    } else {
        $msg = 'INVALID FLAG — NOT RECOGNIZED IN DATABASE';
        $msg_type = 'error';
    }
}

$stats = get_user_score($pdb, $uid);
$captures_map = [];
foreach ($stats['captures'] as $c) {
    $captures_map[$c['flag_name']] = $c['captured_at'];
}

$rank_stmt = $pdb->query("
    SELECT u.id, SUM(CASE
        WHEN fc.flag_name IS NOT NULL THEN (
            SELECT points FROM (
                SELECT 'login_flag' AS fn, 100 AS points UNION ALL
                SELECT 'xss_flag',100 UNION ALL
                SELECT 'order_flag',150 UNION ALL
                SELECT 'idor_flag',100 UNION ALL
                SELECT 'debug_flag',50
            ) p WHERE p.fn=fc.flag_name
        ) ELSE 0 END) as score
    FROM portal_users u
    LEFT JOIN flag_captures fc ON fc.user_id=u.id
    WHERE u.is_admin=0
    GROUP BY u.id
    ORDER BY score DESC, (SELECT MIN(captured_at) FROM flag_captures WHERE user_id=u.id) ASC
");
$rank = 1;
while ($r = $rank_stmt->fetch(PDO::FETCH_ASSOC)) {
    if ($r['id'] == $uid) break;
    $rank++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>VectorScope CTF // Operator Dashboard</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&display=swap');
  *{box-sizing:border-box;margin:0;padding:0}
  :root{--green:#00ff41;--green-dim:#00b32c;--green-dark:#003d0c;--bg:#020d03;--panel:#040f05;--red:#ff2a2a;--yellow:#f0e130;--cyan:#00e5ff;--purple:#b347ea}
  body{background:var(--bg);color:var(--green);font-family:'Share Tech Mono',monospace;min-height:100vh}
  .scanlines{pointer-events:none;position:fixed;inset:0;background:repeating-linear-gradient(to bottom,transparent 0,transparent 2px,rgba(0,0,0,.15) 2px,rgba(0,0,0,.15) 4px);z-index:999}
  .topbar{background:rgba(4,15,5,.95);border-bottom:1px solid var(--green-dark);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
  .logo{font-family:'Orbitron',monospace;font-size:1rem;font-weight:900;letter-spacing:.2em;color:var(--green);text-shadow:0 0 10px var(--green)}
  .topbar-right{display:flex;gap:20px;align-items:center;font-size:.8rem;color:var(--green-dim)}
  .topbar-right a{color:var(--cyan);text-decoration:none;letter-spacing:.1em}
  .topbar-right a:hover{text-shadow:0 0 8px var(--cyan)}
  .main{max-width:1100px;margin:0 auto;padding:32px 24px}
  .section-title{font-family:'Orbitron',monospace;font-size:.85rem;letter-spacing:.25em;color:var(--green-dim);margin-bottom:16px;padding-bottom:8px;border-bottom:1px solid var(--green-dark)}
  .card{background:var(--panel);border:1px solid var(--green-dark);border-radius:4px;padding:24px;margin-bottom:24px;position:relative}
  .card::before{content:'';position:absolute;top:-1px;left:-1px;right:-1px;bottom:-1px;border-radius:4px;pointer-events:none}
  .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px}
  .stat{background:var(--panel);border:1px solid var(--green-dark);border-radius:4px;padding:20px;text-align:center}
  .stat-val{font-family:'Orbitron',monospace;font-size:2rem;font-weight:900;color:var(--green);text-shadow:0 0 12px var(--green);line-height:1}
  .stat-label{font-size:.72rem;color:var(--green-dim);letter-spacing:.15em;margin-top:8px}
  .flag-grid{display:flex;flex-direction:column;gap:10px}
  .flag-item{display:flex;align-items:center;justify-content:space-between;padding:12px 16px;border:1px solid var(--green-dark);border-radius:3px;background:rgba(0,255,65,.02);font-size:.85rem}
  .flag-item.captured{border-color:rgba(0,255,65,.3);background:rgba(0,255,65,.05)}
  .flag-status{font-size:.75rem;padding:3px 10px;border-radius:2px;letter-spacing:.1em}
  .flag-status.done{background:rgba(0,255,65,.12);color:var(--green);border:1px solid rgba(0,255,65,.3)}
  .flag-status.pending{background:rgba(255,255,255,.03);color:var(--green-dim);border:1px solid var(--green-dark)}
  .flag-pts{color:var(--yellow);font-size:.8rem}
  .flag-time{color:var(--green-dim);font-size:.75rem}
  .submit-form{display:flex;gap:12px;align-items:center}
  .submit-form input{flex:1;background:rgba(0,255,65,.04);border:1px solid var(--green-dark);border-radius:3px;padding:12px 14px;color:var(--green);font-family:'Share Tech Mono',monospace;font-size:.95rem;outline:none}
  .submit-form input:focus{border-color:var(--green);box-shadow:0 0 10px rgba(0,255,65,.1)}
  .submit-form input::placeholder{color:var(--green-dark)}
  .btn{padding:12px 24px;background:transparent;border:1px solid var(--green);border-radius:3px;color:var(--green);font-family:'Orbitron',monospace;font-size:.78rem;font-weight:700;letter-spacing:.15em;cursor:pointer;transition:all .2s;white-space:nowrap}
  .btn:hover{background:rgba(0,255,65,.1);box-shadow:0 0 15px rgba(0,255,65,.2)}
  .msg{padding:12px 16px;border-radius:3px;margin-bottom:20px;font-size:.85rem;letter-spacing:.05em}
  .msg.success{background:rgba(0,255,65,.08);border:1px solid rgba(0,255,65,.3);color:var(--green)}
  .msg.error{background:rgba(255,42,42,.08);border:1px solid rgba(255,42,42,.3);color:var(--red)}
  .msg.warn{background:rgba(240,225,48,.06);border:1px solid rgba(240,225,48,.3);color:var(--yellow)}
  .progress-bar{height:6px;background:var(--green-dark);border-radius:3px;margin-top:12px;overflow:hidden}
  .progress-fill{height:100%;background:linear-gradient(90deg,var(--green-dim),var(--green));border-radius:3px;transition:width .5s ease;box-shadow:0 0 8px var(--green)}
  .blinking{animation:blink 1s step-end infinite}
  @keyframes blink{50%{opacity:0}}
  .nav-link{color:var(--green-dim);text-decoration:none;font-size:.8rem;letter-spacing:.1em}
  .nav-link:hover{color:var(--green)}
  @media(max-width:700px){.stat-grid{grid-template-columns:repeat(2,1fr)}}
</style>
</head>
<body>
<div class="scanlines"></div>
<div class="topbar">
  <div class="logo">VECTORSCOPE CTF</div>
  <div class="topbar-right">
    <span style="color:var(--green)">// <?= htmlspecialchars(strtoupper($username)) ?></span>
    <a href="leaderboard.php">LEADERBOARD</a>
    <a href="../index.php">TARGET SYSTEM</a>
    <a href="logout.php" style="color:var(--red)">LOGOUT</a>
  </div>
</div>

<div class="main">
  <div style="margin-bottom:28px">
    <div style="font-family:'Orbitron',monospace;font-size:1.3rem;font-weight:900;color:var(--green);text-shadow:0 0 12px var(--green)">
      OPERATOR DASHBOARD <span class="blinking">_</span>
    </div>
    <div style="color:var(--green-dim);font-size:.8rem;margin-top:4px;letter-spacing:.1em">
      &gt; SESSION ACTIVE // RANK #<?= $rank ?>
    </div>
  </div>

  <div class="stat-grid">
    <div class="stat">
      <div class="stat-val"><?= $stats['score'] ?></div>
      <div class="stat-label">// TOTAL SCORE</div>
    </div>
    <div class="stat">
      <div class="stat-val"><?= $stats['count'] ?>/<?= count(KNOWN_FLAGS) ?></div>
      <div class="stat-label">// FLAGS CAPTURED</div>
    </div>
    <div class="stat">
      <div class="stat-val">#<?= $rank ?></div>
      <div class="stat-label">// GLOBAL RANK</div>
    </div>
    <div class="stat">
      <div class="stat-val" style="font-size:1.1rem"><?= format_time($stats['elapsed']) ?></div>
      <div class="stat-label">// TIME ELAPSED</div>
    </div>
  </div>

  <div class="card">
    <div class="section-title">// SUBMIT CAPTURED FLAG</div>
    <?php if ($msg): ?>
      <div class="msg <?= $msg_type ?>">&gt; <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
    <form method="POST" class="submit-form">
      <input type="text" name="flag" placeholder="THM{...}" autocomplete="off" autofocus>
      <button type="submit" class="btn">SUBMIT FLAG</button>
    </form>
    <div style="color:var(--green-dim);font-size:.75rem;margin-top:10px;letter-spacing:.05em">
      &gt; Explore the target system, find vulnerabilities, capture flags and submit them here.
    </div>
  </div>

  <div class="card">
    <div class="section-title">// MISSION OBJECTIVES — FLAG PROGRESS</div>
    <div class="progress-bar" style="margin-bottom:20px">
      <div class="progress-fill" style="width:<?= $stats['count'] > 0 ? round($stats['count']/count(KNOWN_FLAGS)*100) : 0 ?>%"></div>
    </div>
    <div class="flag-grid">
      <?php foreach (KNOWN_FLAGS as $fval => $meta): ?>
        <?php $captured = isset($captures_map[$meta['name']]); ?>
        <div class="flag-item <?= $captured ? 'captured' : '' ?>">
          <div>
            <div style="color:<?= $captured ? 'var(--green)' : 'var(--green-dim)' ?>;letter-spacing:.05em">
              <?= $captured ? '&#x2588;' : '&#x2591;' ?> <?= htmlspecialchars($meta['label']) ?>
            </div>
            <?php if ($captured): ?>
              <div class="flag-time" style="margin-top:4px">&gt; captured: <?= date('Y-m-d H:i:s', $captures_map[$meta['name']]) ?></div>
            <?php endif; ?>
          </div>
          <div style="display:flex;align-items:center;gap:12px">
            <span class="flag-pts">+<?= $meta['points'] ?>pts</span>
            <span class="flag-status <?= $captured ? 'done' : 'pending' ?>"><?= $captured ? 'CAPTURED' : 'PENDING' ?></span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div style="text-align:center;margin-top:12px">
    <a href="../index.php" class="nav-link">[ ENTER TARGET SYSTEM → ]</a>
    &nbsp;&nbsp;
    <a href="leaderboard.php" class="nav-link">[ VIEW LEADERBOARD ]</a>
  </div>
</div>
</body>
</html>
