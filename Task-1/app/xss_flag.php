<?php
// Endpoint that returns the XSS challenge flag from the DB.
include 'config.php';
header('Content-Type: text/plain');
$f = get_flag('xss_flag');
if ($f) {
    echo $f;
} else {
    echo 'FLAG_NOT_FOUND';
}
?>
