<?php
// ---------------------------------------------------------------
// Database bootstrap — auto-detects environment:
//   Production (Vercel): uses POSTGRES_URL env var → PostgreSQL
//   Local dev:           falls back to SQLite
// ---------------------------------------------------------------

function _vs_make_pdo() {
    $pg_url = getenv('POSTGRES_URL') ?: getenv('DATABASE_URL');
    if ($pg_url) {
        $u    = parse_url($pg_url);
        $host = $u['host'];
        $port = $u['port'] ?? 5432;
        $db   = ltrim(explode('?', $u['path'])[0], '/');
        $user = $u['user'];
        $pass = rawurldecode($u['pass'] ?? '');
        $pdo  = new PDO("pgsql:host=$host;port=$port;dbname=$db;sslmode=prefer", $user, $pass);
    } else {
        $path = __DIR__ . '/../db/app.sqlite';
        if (!is_dir(dirname($path))) mkdir(dirname($path), 0777, true);
        $pdo = new PDO('sqlite:' . $path);
    }
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

$pdo = _vs_make_pdo();
$_is_pg = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql';

// ── Schema ──────────────────────────────────────────────────────
if ($_is_pg) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id       SERIAL PRIMARY KEY,
            username VARCHAR(50),
            password VARCHAR(50),
            email    VARCHAR(100),
            notes    TEXT
        );
        CREATE TABLE IF NOT EXISTS orders (
            id     INTEGER,
            status VARCHAR(50)
        );
        CREATE TABLE IF NOT EXISTS flags (
            id   SERIAL PRIMARY KEY,
            name VARCHAR(50),
            flag VARCHAR(100)
        );
    ");
} else {
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username VARCHAR(50), password VARCHAR(50),
        email VARCHAR(100), notes TEXT)");
    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (id INTEGER, status VARCHAR(50))");
    $pdo->exec("CREATE TABLE IF NOT EXISTS flags (
        id INTEGER PRIMARY KEY AUTOINCREMENT, name VARCHAR(50), flag VARCHAR(100))");
}

// ── Seed data ───────────────────────────────────────────────────
$user_count = (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($user_count === 0) {
    if ($_is_pg) {
        $pdo->exec("INSERT INTO users (id, username, password, email, notes) VALUES
            (1, 'user',  'userpass',  'user@example.com',  'Standard user.'),
            (2, 'admin', 'adminpass', 'admin@example.com', 'Admin Notes: THM{IDOR_ACCESS}')");
        $pdo->exec("SELECT setval(pg_get_serial_sequence('users','id'), (SELECT MAX(id) FROM users))");
    } else {
        $pdo->exec("INSERT INTO users (id, username, password, email, notes) VALUES
            (1,'user','userpass','user@example.com','Standard user.'),
            (2,'admin','adminpass','admin@example.com','Admin Notes: THM{IDOR_ACCESS}')");
    }
}

if ((int)$pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn() === 0) {
    $pdo->exec("INSERT INTO orders VALUES (1, 'shipped')");
}

if ((int)$pdo->query("SELECT COUNT(*) FROM flags")->fetchColumn() === 0) {
    $pdo->exec("INSERT INTO flags (name, flag) VALUES
        ('login_flag', 'THM{SQLI_LOGIN_BYPASS}'),
        ('xss_flag',   'THM{XSS_REFLECTED}'),
        ('order_flag', 'THM{BLIND_SQL_INJECTION}'),
        ('idor_flag',  'THM{IDOR_ACCESS}'),
        ('debug_flag', 'THM{DEBUG_EXPOSED}')");
}

// ── Mysqli compatibility shim ────────────────────────────────────
class MysqliShim {
    public $connect_error = null;
    public $insert_id = 0;
    private $pdo;

    public function __construct($pdo) { $this->pdo = $pdo; }

    public function real_escape_string($str) {
        return str_replace(["'", "\\"], ["''", "\\\\"], $str);
    }

    public function query($sql) {
        try {
            $stmt = $this->pdo->query($sql);
            return $stmt ? new ResultShim($stmt) : false;
        } catch (Exception $e) {
            return false;
        }
    }
}

class ResultShim {
    public $num_rows = 0;
    private $rows = [];
    private $pos  = 0;

    public function __construct($stmt) {
        $this->rows     = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc() {
        return ($this->pos < $this->num_rows) ? $this->rows[$this->pos++] : null;
    }
}

$conn = new MysqliShim($pdo);

function get_flag($name) {
    global $pdo;
    $s = $pdo->prepare("SELECT flag FROM flags WHERE name=:n LIMIT 1");
    $s->execute([':n' => $name]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    return $r ? $r['flag'] : null;
}
?>
