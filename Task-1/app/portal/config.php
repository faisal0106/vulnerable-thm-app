<?php
session_start();

$portal_db_path = __DIR__ . '/../../db/portal.sqlite';

if (!is_dir(dirname($portal_db_path))) {
    mkdir(dirname($portal_db_path), 0777, true);
}

$pdb = new PDO('sqlite:' . $portal_db_path);
$pdb->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

// Seed admin account
$admin = $pdb->query("SELECT id FROM portal_users WHERE username='admin'")->fetch();
if (!$admin) {
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $pdb->prepare("INSERT INTO portal_users (username, password_hash, is_admin) VALUES ('admin', ?, 1)")
        ->execute([$hash]);
}

// Known flags for VectorScope
define('KNOWN_FLAGS', [
    'THM{SQLI_LOGIN_BYPASS}'    => ['name' => 'login_flag',   'label' => 'SQL Injection — Login Bypass',   'points' => 100],
    'THM{XSS_REFLECTED}'        => ['name' => 'xss_flag',     'label' => 'XSS — Reflected Script Injection','points' => 100],
    'THM{BLIND_SQL_INJECTION}'  => ['name' => 'order_flag',   'label' => 'Blind SQL Injection — Records',  'points' => 150],
    'THM{IDOR_ACCESS}'          => ['name' => 'idor_flag',    'label' => 'IDOR — Admin Profile Access',    'points' => 100],
    'THM{DEBUG_EXPOSED}'        => ['name' => 'debug_flag',   'label' => 'Debug Page Exposure',            'points' => 50],
]);

define('MAX_SCORE', 500);

function is_logged_in() {
    return isset($_SESSION['portal_user_id']);
}

function require_login() {
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

function require_admin() {
    require_login();
    if (empty($_SESSION['portal_is_admin'])) {
        header('Location: dashboard.php');
        exit;
    }
}

function get_user_score($pdb, $user_id) {
    $captures = $pdb->prepare("SELECT flag_name, captured_at FROM flag_captures WHERE user_id=? ORDER BY captured_at ASC");
    $captures->execute([$user_id]);
    $rows = $captures->fetchAll(PDO::FETCH_ASSOC);

    $user = $pdb->prepare("SELECT created_at FROM portal_users WHERE id=?");
    $user->execute([$user_id]);
    $u = $user->fetch(PDO::FETCH_ASSOC);
    $start = $u['created_at'];

    $score = 0;
    $flag_names_captured = [];
    $last_time = $start;
    foreach ($rows as $r) {
        $flag_val = $r['flag_name'];
        foreach (KNOWN_FLAGS as $fv => $meta) {
            if ($meta['name'] === $flag_val) {
                $score += $meta['points'];
                break;
            }
        }
        $flag_names_captured[] = $flag_val;
        $last_time = $r['captured_at'];
    }

    $elapsed = ($last_time > $start && count($rows) > 0) ? ($last_time - $start) : 0;
    return [
        'score'    => $score,
        'count'    => count($rows),
        'elapsed'  => $elapsed,
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
