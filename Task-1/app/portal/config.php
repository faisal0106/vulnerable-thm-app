<?php
session_start();

// ---------------------------------------------------------------
// Portal database bootstrap — auto-detects environment:
//   Production (Vercel): uses POSTGRES_URL env var → PostgreSQL
//   Local dev:           falls back to SQLite
// ---------------------------------------------------------------

function _portal_make_pdo() {
    $pg_url = getenv('POSTGRES_URL') ?: getenv('DATABASE_URL');
    if ($pg_url) {
        $u    = parse_url($pg_url);
        $host = $u['host'];
        $port = $u['port'] ?? 5432;
        $db   = ltrim(explode('?', $u['path'])[0], '/');
        $user = $u['user'];
        $pass = rawurldecode($u['pass'] ?? '');
        $pdo  = new PDO("pgsql:host=$host;port=$port;dbname=$db;sslmode=require", $user, $pass);
    } else {
        $path = __DIR__ . '/../../db/portal.sqlite';
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
        $pdo = new PDO('sqlite:' . $path);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

$pdb    = _portal_make_pdo();
$_p_pg  = $pdb->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';

// ── Schema ──────────────────────────────────────────────────────
if ($_p_pg) {
    $pdb->exec("
        CREATE TABLE IF NOT EXISTS portal_users (
            id            SERIAL PRIMARY KEY,
            username      TEXT UNIQUE NOT NULL,
            password_hash TEXT NOT NULL,
            is_admin      INTEGER DEFAULT 0,
            created_at    INTEGER DEFAULT EXTRACT(EPOCH FROM NOW())::INTEGER
        );
        CREATE TABLE IF NOT EXISTS flag_captures (
            id          SERIAL PRIMARY KEY,
            user_id     INTEGER NOT NULL,
            flag_name   TEXT NOT NULL,
            flag_value  TEXT NOT NULL,
            captured_at INTEGER DEFAULT EXTRACT(EPOCH FROM NOW())::INTEGER,
            UNIQUE(user_id, flag_name)
        );
    ");
} else {
    $pdb->exec("CREATE TABLE IF NOT EXISTS portal_users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT UNIQUE NOT NULL,
        password_hash TEXT NOT NULL,
        is_admin INTEGER DEFAULT 0,
        created_at INTEGER DEFAULT (strftime('%s','now'))
    )");
    $pdb->exec("CREATE TABLE IF NOT EXISTS flag_captures (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        flag_name TEXT NOT NULL,
        flag_value TEXT NOT NULL,
        captured_at INTEGER DEFAULT (strftime('%s','now')),
        UNIQUE(user_id, flag_name)
    )");
}

// ── Seed admin account ───────────────────────────────────────────
$admin = $pdb->query("SELECT id FROM portal_users WHERE username='admin'")->fetch();
if (!$admin) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdb->prepare("INSERT INTO portal_users (username, password_hash, is_admin) VALUES ('admin', ?, 1)")
        ->execute([$hash]);
}

// ── Constants ────────────────────────────────────────────────────
define('KNOWN_FLAGS', [
    'THM{SQLI_LOGIN_BYPASS}'   => ['name' => 'login_flag',  'label' => 'SQL Injection — Login Bypass',    'points' => 100],
    'THM{XSS_REFLECTED}'       => ['name' => 'xss_flag',    'label' => 'XSS — Reflected Script Injection', 'points' => 100],
    'THM{BLIND_SQL_INJECTION}' => ['name' => 'order_flag',  'label' => 'Blind SQL Injection — Records',   'points' => 150],
    'THM{IDOR_ACCESS}'         => ['name' => 'idor_flag',   'label' => 'IDOR — Admin Profile Access',     'points' => 100],
    'THM{DEBUG_EXPOSED}'       => ['name' => 'debug_flag',  'label' => 'Debug Page Exposure',             'points' => 50],
]);
define('MAX_SCORE', 500);

// ── Helpers ──────────────────────────────────────────────────────
function is_logged_in() { return isset($_SESSION['portal_user_id']); }

function require_login() {
    if (!is_logged_in()) { header('Location: login.php'); exit; }
}

function require_admin() {
    require_login();
    if (empty($_SESSION['portal_is_admin'])) { header('Location: dashboard.php'); exit; }
}

function get_user_score($pdb, $user_id) {
    $s = $pdb->prepare("SELECT flag_name, captured_at FROM flag_captures WHERE user_id=? ORDER BY captured_at ASC");
    $s->execute([$user_id]);
    $rows = $s->fetchAll(PDO::FETCH_ASSOC);

    $u = $pdb->prepare("SELECT created_at FROM portal_users WHERE id=?");
    $u->execute([$user_id]);
    $row = $u->fetch(PDO::FETCH_ASSOC);
    $start = (int)($row['created_at'] ?? time());

    $score = 0; $last_time = $start;
    foreach ($rows as $r) {
        foreach (KNOWN_FLAGS as $meta) {
            if ($meta['name'] === $r['flag_name']) { $score += $meta['points']; break; }
        }
        $last_time = (int)$r['captured_at'];
    }

    return [
        'score'    => $score,
        'count'    => count($rows),
        'elapsed'  => ($last_time > $start && count($rows) > 0) ? $last_time - $start : 0,
        'captures' => $rows,
    ];
}

function format_time($seconds) {
    if ($seconds <= 0) return '—';
    $h = floor($seconds / 3600);
    $m = floor(($seconds % 3600) / 60);
    $s = $seconds % 60;
    if ($h > 0) return sprintf('%dh %02dm %02ds', $h, $m, $s);
    if ($m > 0) return sprintf('%dm %02ds', $m, $s);
    return sprintf('%ds', $s);
}
?>
