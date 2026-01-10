<?php
$conn = new mysqli("db", "root", "root", "thm");

if ($conn->connect_error) {
    die("DB connection failed");
}

// Helper: fetch a named flag from the `flags` table.
function get_flag($name) {
    global $conn;
    $n = $conn->real_escape_string($name);
    $res = $conn->query("SELECT flag FROM flags WHERE name='$n' LIMIT 1");
    if ($res && $row = $res->fetch_assoc()) {
        return $row['flag'];
    }
    return null;
}

?>
