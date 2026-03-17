<?php
require_once 'config.php';

$stmt = $pdb->query("
    SELECT u.id, u.username, u.created_at,
           COUNT(fc.id) as flag_count,
           MIN(fc.captured_at) as first_cap,
           MAX(fc.captured_at) as last_cap
    FROM portal_users u
    LEFT JOIN flag_captures fc ON fc.user_id=u.id
    WHERE u.is_admin=0
    GROUP BY u.id
    ORDER BY flag_count DESC, last_cap ASC, u.created_at ASC
");
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

$leaderboard = [];
foreach ($players as $p) {
    $score_data = get_user_score($pdb, $p['id']);
    $leaderboard[] = array_merge($p, ['score' => $score_data['score'], 'elapsed' => $score_data['elapsed'], 'captures' => $score_data['captures']]);
}
usort($leaderboard, function($a, $b) {
    if ($b['score'] !== $a['score']) return $b['score'] - $a['score'];
    if ($a['elapsed'] !== $b['elapsed']) return $a['elapsed'] - $b['elapsed'];
    return $a['created_at'] - $b['created_at'];
});

$total_players = count($leaderboard);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>VectorScope CTF // Leaderboard</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&display=swap');
  *{box-sizing:border-box;margin:0;padding:0}
  :root{--green:#00ff41;--green-dim:#00b32c;--green-dark:#003d0c;--bg:#020d03;--panel:#040f05;--red:#ff2a2a;--yellow:#f0e130;--cyan:#00e5ff;--gold:#ffd700;--silver:#c0c0c0;--bronze:#cd7f32}
  body{background:var(--bg);color:var(--green);font-family:'Share Tech Mono',monospace;min-height:100vh}
  .scanlines{pointer-events:none;position:fixed;inset:0;background:repeating-linear-gradient(to bottom,transparent 0,transparent 2px,rgba(0,0,0,.15) 2px,rgba(0,0,0,.15) 4px);z-index:999}
  .topbar{background:rgba(4,15,5,.95);border-bottom:1px solid var(--green-dark);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
  .logo{font-family:'Orbitron',monospace;font-size:1rem;font-weight:900;letter-spacing:.2em;color:var(--green);text-shadow:0 0 10px var(--green)}
  .topbar-right{display:flex;gap:20px;align-items:center;font-size:.8rem}
  .topbar-right a{color:var(--cyan);text-decoration:none;letter-spacing:.1em}
  .topbar-right a:hover{text-shadow:0 0 8px var(--cyan)}
  .main{max-width:1000px;margin:0 auto;padding:32px 24px}
  .page-title{font-family:'Orbitron',monospace;font-size:1.5rem;font-weight:900;color:var(--green);text-shadow:0 0 15px var(--green);text-align:center;letter-spacing:.3em;margin-bottom:8px}
  .page-sub{text-align:center;color:var(--green-dim);font-size:.8rem;letter-spacing:.2em;margin-bottom:36px}
  .table-wrap{background:var(--panel);border:1px solid var(--green-dark);border-radius:4px;overflow:hidden}
  .table-header{display:grid;grid-template-columns:60px 1fr 120px 120px 120px 140px;padding:12px 20px;border-bottom:1px solid rgba(0,255,65,.15);font-size:.75rem;color:var(--green-dim);letter-spacing:.15em}
  .table-row{display:grid;grid-template-columns:60px 1fr 120px 120px 120px 140px;padding:14px 20px;border-bottom:1px solid rgba(0,255,65,.05);align-items:center;font-size:.85rem;transition:background .15s}
  .table-row:hover{background:rgba(0,255,65,.03)}
  .table-row:last-child{border-bottom:none}
  .rank-1{color:var(--gold);font-family:'Orbitron',monospace;font-weight:900;font-size:.95rem}
  .rank-2{color:var(--silver);font-family:'Orbitron',monospace;font-weight:700}
  .rank-3{color:var(--bronze);font-family:'Orbitron',monospace;font-weight:700}
  .rank-n{color:var(--green-dim);font-family:'Orbitron',monospace}
  .username-cell{color:var(--green);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
  .score-cell{color:var(--yellow);font-family:'Orbitron',monospace;font-weight:700}
  .flag-cell{color:var(--cyan)}
  .time-cell{color:var(--green-dim);font-size:.8rem}
  .empty-state{text-align:center;padding:60px 20px;color:var(--green-dim);font-size:.9rem;letter-spacing:.1em}
  .flag-dots{display:flex;gap:4px;flex-wrap:wrap}
  .dot{width:10px;height:10px;border-radius:1px}
  .dot.filled{background:var(--green);box-shadow:0 0 4px var(--green)}
  .dot.empty{background:var(--green-dark);border:1px solid var(--green-dark)}
  .stats-bar{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px}
  .stat-mini{background:var(--panel);border:1px solid var(--green-dark);border-radius:4px;padding:16px;text-align:center}
  .stat-mini-val{font-family:'Orbitron',monospace;font-size:1.4rem;font-weight:900;color:var(--green);text-shadow:0 0 8px var(--green)}
  .stat-mini-label{font-size:.7rem;color:var(--green-dim);letter-spacing:.15em;margin-top:4px}
  @media(max-width:700px){
    .table-header,.table-row{grid-template-columns:40px 1fr 80px 80px}
    .time-cell,.flag-cell:last-child{display:none}
  }
</style>
</head>
<body>
<div class="scanlines"></div>
<div class="topbar">
  <div class="logo">VECTORSCOPE CTF</div>
  <div class="topbar-right">
    <?php if (is_logged_in()): ?>
      <span style="color:var(--green-dim)">// <?= htmlspecialchars(strtoupper($_SESSION['portal_username'])) ?></span>
      <a href="dashboard.php">DASHBOARD</a>
    <?php else: ?>
      <a href="login.php">LOGIN</a>
    <?php endif; ?>
    <a href="../home.php">TARGET SYSTEM</a>
  </div>
</div>

<div class="main">
  <div class="page-title">LEADERBOARD</div>
  <div class="page-sub">// GLOBAL OPERATOR RANKINGS // LIVE //</div>

  <div class="stats-bar">
    <div class="stat-mini">
      <div class="stat-mini-val"><?= $total_players ?></div>
      <div class="stat-mini-label">// OPERATORS</div>
    </div>
    <div class="stat-mini">
      <div class="stat-mini-val"><?= count(KNOWN_FLAGS) ?></div>
      <div class="stat-mini-label">// TOTAL FLAGS</div>
    </div>
    <div class="stat-mini">
      <div class="stat-mini-val"><?= MAX_SCORE ?></div>
      <div class="stat-mini-label">// MAX SCORE</div>
    </div>
  </div>

  <div class="table-wrap">
    <div class="table-header">
      <div>RANK</div>
      <div>OPERATOR</div>
      <div>SCORE</div>
      <div>FLAGS</div>
      <div>TIME</div>
      <div>PROGRESS</div>
    </div>

    <?php if (empty($leaderboard)): ?>
      <div class="empty-state">&gt; NO OPERATORS REGISTERED YET_</div>
    <?php endif; ?>

    <?php foreach ($leaderboard as $i => $p): ?>
      <?php $rank = $i + 1; ?>
      <div class="table-row">
        <div class="<?= $rank===1?'rank-1':($rank===2?'rank-2':($rank===3?'rank-3':'rank-n')) ?>">
          <?= $rank===1?'#01':($rank===2?'#02':($rank===3?'#03':'#'.str_pad($rank,2,'0',STR_PAD_LEFT))) ?>
        </div>
        <div class="username-cell">
          <?php if ($rank <= 3): ?>
            <?= $rank===1?'★ ':($rank===2?'✦ ':'✧ ') ?>
          <?php endif; ?>
          <?= htmlspecialchars(strtoupper($p['username'])) ?>
        </div>
        <div class="score-cell"><?= $p['score'] ?></div>
        <div class="flag-cell"><?= $p['flag_count'] ?>/<?= count(KNOWN_FLAGS) ?></div>
        <div class="time-cell"><?= format_time($p['elapsed']) ?></div>
        <div>
          <div class="flag-dots">
            <?php
            $cap_names = array_column($p['captures'], 'flag_name');
            foreach (KNOWN_FLAGS as $fv => $meta):
            ?>
              <div class="dot <?= in_array($meta['name'], $cap_names) ? 'filled' : 'empty' ?>" title="<?= htmlspecialchars($meta['label']) ?>"></div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div style="text-align:center;margin-top:24px;font-size:.78rem;color:var(--green-dim)">
    <?php if (!is_logged_in()): ?>
      <a href="login.php" style="color:var(--cyan);text-decoration:none">[ JOIN THE CHALLENGE ]</a>
    <?php else: ?>
      <a href="dashboard.php" style="color:var(--cyan);text-decoration:none">[ BACK TO DASHBOARD ]</a>
    <?php endif; ?>
  </div>
</div>

<script>
setTimeout(() => location.reload(), 60000);
</script>
</body>
</html>
