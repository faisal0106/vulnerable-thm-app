<?php
require_once 'config.php';
require_admin();

$msg = '';
$msg_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'reset_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid) {
            $pdb->prepare("DELETE FROM flag_captures WHERE user_id=?")->execute([$uid]);
            $user_r = $pdb->prepare("SELECT username FROM portal_users WHERE id=?");
            $user_r->execute([$uid]);
            $urow = $user_r->fetch(PDO::FETCH_ASSOC);
            $msg = 'Score reset for operator: ' . ($urow['username'] ?? $uid);
            $msg_type = 'warn';
        }
    } elseif ($action === 'reset_all') {
        $pdb->exec("DELETE FROM flag_captures");
        $msg = 'ALL SCORES RESET — CHALLENGE RESTARTED';
        $msg_type = 'error';
    } elseif ($action === 'delete_user') {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid) {
            $pdb->prepare("DELETE FROM flag_captures WHERE user_id=?")->execute([$uid]);
            $pdb->prepare("DELETE FROM portal_users WHERE id=? AND is_admin=0")->execute([$uid]);
            $msg = 'Operator account deleted.';
            $msg_type = 'warn';
        }
    }
}

$stmt = $pdb->query("
    SELECT u.id, u.username, u.created_at, COUNT(fc.id) as flag_count
    FROM portal_users u
    LEFT JOIN flag_captures fc ON fc.user_id=u.id
    WHERE u.is_admin=0
    GROUP BY u.id
    ORDER BY flag_count DESC, u.created_at ASC
");
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

$full_data = [];
foreach ($players as $p) {
    $score_data = get_user_score($pdb, $p['id']);
    $full_data[] = array_merge($p, $score_data);
}
usort($full_data, function($a, $b) {
    if ($b['score'] !== $a['score']) return $b['score'] - $a['score'];
    return $a['elapsed'] - $b['elapsed'];
});

$total_flags_captured = $pdb->query("SELECT COUNT(*) FROM flag_captures")->fetchColumn();
$total_players = count($full_data);
$top_scorer = !empty($full_data) ? $full_data[0]['username'] : '—';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>VectorScope CTF // Admin Console</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&display=swap');
  *{box-sizing:border-box;margin:0;padding:0}
  :root{--green:#00ff41;--green-dim:#00b32c;--green-dark:#003d0c;--bg:#020d03;--panel:#040f05;--red:#ff2a2a;--yellow:#f0e130;--cyan:#00e5ff;--purple:#b347ea;--gold:#ffd700}
  body{background:var(--bg);color:var(--green);font-family:'Share Tech Mono',monospace;min-height:100vh}
  .scanlines{pointer-events:none;position:fixed;inset:0;background:repeating-linear-gradient(to bottom,transparent 0,transparent 2px,rgba(0,0,0,.15) 2px,rgba(0,0,0,.15) 4px);z-index:999}
  .topbar{background:rgba(4,15,5,.95);border-bottom:1px solid rgba(179,71,234,.3);padding:14px 32px;display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;z-index:100}
  .logo{font-family:'Orbitron',monospace;font-size:1rem;font-weight:900;letter-spacing:.2em;color:var(--purple);text-shadow:0 0 10px var(--purple)}
  .topbar-right{display:flex;gap:20px;align-items:center;font-size:.8rem}
  .topbar-right a{color:var(--cyan);text-decoration:none;letter-spacing:.1em}
  .main{max-width:1200px;margin:0 auto;padding:32px 24px}
  .page-title{font-family:'Orbitron',monospace;font-size:1.3rem;font-weight:900;color:var(--purple);text-shadow:0 0 12px var(--purple);margin-bottom:4px}
  .page-sub{color:rgba(179,71,234,.6);font-size:.78rem;letter-spacing:.2em;margin-bottom:28px}
  .section-title{font-family:'Orbitron',monospace;font-size:.8rem;letter-spacing:.25em;color:var(--purple);margin-bottom:14px;padding-bottom:8px;border-bottom:1px solid rgba(179,71,234,.2)}
  .card{background:var(--panel);border:1px solid var(--green-dark);border-radius:4px;padding:24px;margin-bottom:24px}
  .stat-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:28px}
  .stat{background:var(--panel);border:1px solid var(--green-dark);border-radius:4px;padding:18px;text-align:center}
  .stat-val{font-family:'Orbitron',monospace;font-size:1.8rem;font-weight:900;color:var(--purple);text-shadow:0 0 10px var(--purple)}
  .stat-label{font-size:.7rem;color:var(--green-dim);letter-spacing:.15em;margin-top:6px}
  .table-wrap{background:var(--panel);border:1px solid var(--green-dark);border-radius:4px;overflow:hidden;margin-bottom:24px}
  .table-header{display:grid;grid-template-columns:50px 1fr 100px 100px 120px 120px 160px;padding:10px 20px;border-bottom:1px solid rgba(179,71,234,.2);font-size:.73rem;color:rgba(179,71,234,.6);letter-spacing:.15em}
  .table-row{display:grid;grid-template-columns:50px 1fr 100px 100px 120px 120px 160px;padding:12px 20px;border-bottom:1px solid rgba(0,255,65,.04);align-items:center;font-size:.83rem}
  .table-row:hover{background:rgba(179,71,234,.03)}
  .table-row:last-child{border-bottom:none}
  .score-cell{color:var(--yellow);font-family:'Orbitron',monospace;font-weight:700}
  .btn-sm{padding:5px 12px;background:transparent;border:1px solid;border-radius:2px;font-family:'Share Tech Mono',monospace;font-size:.75rem;cursor:pointer;letter-spacing:.05em;transition:all .2s}
  .btn-reset{border-color:rgba(240,225,48,.4);color:var(--yellow)}
  .btn-reset:hover{background:rgba(240,225,48,.1)}
  .btn-delete{border-color:rgba(255,42,42,.4);color:var(--red)}
  .btn-delete:hover{background:rgba(255,42,42,.1)}
  .btn-danger{padding:10px 20px;background:transparent;border:1px solid rgba(255,42,42,.5);border-radius:3px;color:var(--red);font-family:'Orbitron',monospace;font-size:.78rem;letter-spacing:.15em;cursor:pointer;transition:all .2s;margin-right:10px}
  .btn-danger:hover{background:rgba(255,42,42,.1);box-shadow:0 0 15px rgba(255,42,42,.2)}
  .msg{padding:12px 16px;border-radius:3px;margin-bottom:20px;font-size:.85rem;letter-spacing:.05em}
  .msg.success{background:rgba(0,255,65,.08);border:1px solid rgba(0,255,65,.3);color:var(--green)}
  .msg.error{background:rgba(255,42,42,.08);border:1px solid rgba(255,42,42,.3);color:var(--red)}
  .msg.warn{background:rgba(240,225,48,.06);border:1px solid rgba(240,225,48,.3);color:var(--yellow)}
  .flag-mini{display:flex;gap:3px}
  .dot{width:9px;height:9px;border-radius:1px}
  .dot.filled{background:var(--green);box-shadow:0 0 3px var(--green)}
  .dot.empty{background:var(--green-dark)}
  .expand-row{display:none;padding:12px 20px 16px;background:rgba(0,0,0,.3);border-bottom:1px solid rgba(0,255,65,.04)}
  .expand-row.open{display:block}
  .flag-detail-row{font-size:.78rem;padding:4px 0;color:var(--green-dim);border-bottom:1px solid rgba(0,255,65,.04);display:flex;justify-content:space-between}
  .flag-detail-row.captured{color:var(--green)}
  @media(max-width:800px){.table-header,.table-row{grid-template-columns:40px 1fr 80px 80px 80px}}
</style>
</head>
<body>
<div class="scanlines"></div>
<div class="topbar">
  <div class="logo">// ADMIN CONSOLE //</div>
  <div class="topbar-right">
    <span style="color:var(--purple)">OPERATOR: ADMIN</span>
    <a href="leaderboard.php">LEADERBOARD</a>
    <a href="logout.php" style="color:var(--red)">LOGOUT</a>
  </div>
</div>

<div class="main">
  <div class="page-title">CHALLENGE ADMINISTRATION</div>
  <div class="page-sub">// SCORE MANAGEMENT // PLAYER OVERVIEW // RESET CONTROLS //</div>

  <?php if ($msg): ?>
    <div class="msg <?= $msg_type ?>">&gt; <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>

  <div class="stat-grid">
    <div class="stat">
      <div class="stat-val"><?= $total_players ?></div>
      <div class="stat-label">// REGISTERED OPERATORS</div>
    </div>
    <div class="stat">
      <div class="stat-val"><?= $total_flags_captured ?></div>
      <div class="stat-label">// TOTAL FLAG CAPTURES</div>
    </div>
    <div class="stat">
      <div class="stat-val"><?= count(KNOWN_FLAGS) ?></div>
      <div class="stat-label">// FLAGS IN CHALLENGE</div>
    </div>
    <div class="stat">
      <div class="stat-val" style="font-size:1rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= htmlspecialchars(strtoupper($top_scorer)) ?></div>
      <div class="stat-label">// CURRENT LEADER</div>
    </div>
  </div>

  <!-- Leaderboard Table -->
  <div style="margin-bottom:6px">
    <div class="section-title">// OPERATOR RANKINGS</div>
  </div>
  <div class="table-wrap">
    <div class="table-header">
      <div>RANK</div>
      <div>OPERATOR</div>
      <div>SCORE</div>
      <div>FLAGS</div>
      <div>TIME</div>
      <div>PROGRESS</div>
      <div>ACTIONS</div>
    </div>
    <?php if (empty($full_data)): ?>
      <div style="text-align:center;padding:40px;color:var(--green-dim);font-size:.85rem">&gt; NO OPERATORS REGISTERED YET_</div>
    <?php endif; ?>
    <?php foreach ($full_data as $i => $p): ?>
      <?php $rank = $i + 1; $cap_names = array_column($p['captures'], 'flag_name'); ?>
      <div class="table-row" id="row-<?= $p['id'] ?>" style="cursor:pointer" onclick="toggleRow(<?= $p['id'] ?>)">
        <div style="font-family:'Orbitron',monospace;color:var(--green-dim)">#<?= str_pad($rank,2,'0',STR_PAD_LEFT) ?></div>
        <div style="color:var(--green)"><?= htmlspecialchars(strtoupper($p['username'])) ?></div>
        <div class="score-cell"><?= $p['score'] ?></div>
        <div style="color:var(--cyan)"><?= $p['flag_count'] ?>/<?= count(KNOWN_FLAGS) ?></div>
        <div style="color:var(--green-dim);font-size:.78rem"><?= format_time($p['elapsed']) ?></div>
        <div>
          <div class="flag-mini">
            <?php foreach (KNOWN_FLAGS as $fv => $meta): ?>
              <div class="dot <?= in_array($meta['name'], $cap_names) ? 'filled' : 'empty' ?>"></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div style="display:flex;gap:8px" onclick="event.stopPropagation()">
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="reset_user">
            <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn-sm btn-reset" onclick="return confirm('Reset scores for <?= htmlspecialchars($p['username']) ?>?')">RESET</button>
          </form>
          <form method="POST" style="display:inline">
            <input type="hidden" name="action" value="delete_user">
            <input type="hidden" name="user_id" value="<?= $p['id'] ?>">
            <button type="submit" class="btn-sm btn-delete" onclick="return confirm('Delete operator <?= htmlspecialchars($p['username']) ?>?')">DEL</button>
          </form>
        </div>
      </div>
      <div class="expand-row" id="expand-<?= $p['id'] ?>">
        <div style="font-size:.75rem;color:var(--purple);letter-spacing:.15em;margin-bottom:10px">// FLAG CAPTURE LOG — <?= htmlspecialchars(strtoupper($p['username'])) ?></div>
        <?php $cap_map = []; foreach($p['captures'] as $c) $cap_map[$c['flag_name']] = $c['captured_at']; ?>
        <?php foreach (KNOWN_FLAGS as $fv => $meta): ?>
          <div class="flag-detail-row <?= isset($cap_map[$meta['name']]) ? 'captured' : '' ?>">
            <span><?= isset($cap_map[$meta['name']]) ? '[+' . $meta['points'] . '] ✓' : '[ -- ] ✗' ?> <?= htmlspecialchars($meta['label']) ?></span>
            <span><?= isset($cap_map[$meta['name']]) ? date('Y-m-d H:i:s', $cap_map[$meta['name']]) : 'not captured' ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endforeach; ?>
  </div>

  <!-- Danger Zone -->
  <div class="card" style="border-color:rgba(255,42,42,.2)">
    <div class="section-title" style="color:var(--red);border-bottom-color:rgba(255,42,42,.2)">// DANGER ZONE — GLOBAL RESET</div>
    <div style="color:var(--green-dim);font-size:.82rem;margin-bottom:16px;line-height:1.6">
      &gt; WARNING: Resetting all scores will erase all flag captures for every operator. This action is irreversible.
    </div>
    <form method="POST" onsubmit="return confirm('CONFIRM: Reset ALL scores for ALL operators? This cannot be undone.')">
      <input type="hidden" name="action" value="reset_all">
      <button type="submit" class="btn-danger">⚠ RESET ALL SCORES</button>
    </form>
  </div>

  <!-- Flag reference -->
  <div class="card">
    <div class="section-title">// FLAG REGISTRY</div>
    <?php foreach (KNOWN_FLAGS as $fval => $meta): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid rgba(0,255,65,.05);font-size:.83rem">
        <span style="color:var(--green)"><?= htmlspecialchars($meta['label']) ?></span>
        <span style="color:var(--green-dim);font-family:monospace"><?= htmlspecialchars($fval) ?></span>
        <span style="color:var(--yellow)">+<?= $meta['points'] ?>pts</span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
function toggleRow(id) {
  const el = document.getElementById('expand-' + id);
  el.classList.toggle('open');
}
</script>
</body>
</html>
