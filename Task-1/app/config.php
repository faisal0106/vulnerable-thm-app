<?php
// SQLite-backed database layer that mimics the mysqli interface used throughout the app.
$db_path = __DIR__ . '/../db/app.sqlite';

// Ensure db directory exists
if (!is_dir(dirname($db_path))) {
    mkdir(dirname($db_path), 0777, true);
}

// Initialize SQLite database
$pdo = new PDO('sqlite:' . $db_path);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create tables and seed data if not present
$pdo->exec("CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username VARCHAR(50),
    password VARCHAR(50),
    email VARCHAR(100),
    notes TEXT
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS orders (
    id INTEGER,
    status VARCHAR(50)
)");

$pdo->exec("CREATE TABLE IF NOT EXISTS flags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(50),
    flag VARCHAR(100)
)");

// Seed data only if empty
$count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
if ($count == 0) {
    $pdo->exec("INSERT INTO users (id, username, password, email, notes) VALUES
        (1, 'user', 'userpass', 'user@example.com', 'Standard user.'),
        (2, 'admin', 'adminpass', 'admin@example.com', 'Admin Notes: THM{IDOR_ACCESS}')");
}

$count = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
if ($count == 0) {
    $pdo->exec("INSERT INTO orders VALUES (1, 'shipped')");
}

$count = $pdo->query("SELECT COUNT(*) FROM flags")->fetchColumn();
if ($count == 0) {
    $pdo->exec("INSERT INTO flags (name, flag) VALUES
        ('login_flag', 'THM{SQLI_LOGIN_BYPASS}'),
        ('xss_flag',   'THM{XSS_REFLECTED}'),
        ('order_flag', 'THM{BLIND_SQL_INJECTION}'),
        ('idor_flag',  'THM{IDOR_ACCESS}'),
        ('debug_flag', 'THM{DEBUG_EXPOSED}')");
}

// Mysqli compatibility shim
class MysqliShim {
    public $connect_error = null;
    private $pdo;
    public $insert_id = 0;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function real_escape_string($str) {
        return str_replace(["'", "\\"], ["''", "\\\\"], $str);
    }

    public function query($sql) {
        try {
            $stmt = $this->pdo->query($sql);
            if ($stmt === false) return false;
            return new ResultShim($stmt);
        } catch (Exception $e) {
            return false;
        }
    }
}

class ResultShim {
    private $stmt;
    private $rows = [];
    private $pos = 0;
    public $num_rows = 0;

    public function __construct($stmt) {
        $this->stmt = $stmt;
        $this->rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->num_rows = count($this->rows);
    }

    public function fetch_assoc() {
        if ($this->pos >= count($this->rows)) return null;
        return $this->rows[$this->pos++];
    }
}

$conn = new MysqliShim($pdo);

// Helper: fetch a named flag from the `flags` table.
function get_flag($name) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT flag FROM flags WHERE name=:name LIMIT 1");
    $stmt->execute([':name' => $name]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ? $row['flag'] : null;
}
?>
