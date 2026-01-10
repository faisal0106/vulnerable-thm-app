<?php require_once('config.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Records - VectorScope</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <header class="site header-bar">
      <div style="display:flex;align-items:center;gap:14px">
        <div class="brand">
          <div class="logo" style="width:48px;height:48px;border-radius:6px;background:#2563eb;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:bold;font-size:18px">VS</div>
          <div class="title">VectorScope</div>
        </div>
        <nav class="nav">
          <a href="index.php">Home</a>
          <a href="search.php">Search</a>
          <a href="orders.php">Records</a>
          <a href="profile.php">Profile</a>
        </nav>
      </div>
      <div class="user-menu">
        <a href="login.php" style="color:var(--muted);text-decoration:none;font-size:0.9rem">Sign in</a>
      </div>
    </header>

    <main style="margin-top:28px">
      <div style="max-width:1200px;margin:0 auto">
        <div style="margin-bottom:24px">
          <h2 style="margin:0 0 8px 0">Records</h2>
          <p class="muted" style="margin:0">Retrieve operational records and transaction logs</p>
        </div>

        <div style="display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start">
          <!-- Query Panel -->
          <div>
            <div class="card" style="padding:20px">
              <h3 style="margin:0 0 14px 0;font-size:1rem">Record Lookup</h3>
              <form method="GET" style="display:flex;flex-direction:column;gap:12px">
                <div>
                  <label style="display:block;margin-bottom:6px;font-size:0.85rem;color:var(--muted)">Record ID</label>
                  <input type="text" name="id" placeholder="Enter ID..." style="width:100%;padding:8px 10px;border:1px solid rgba(255,255,255,0.1);border-radius:6px;background:rgba(255,255,255,0.03);color:#fff;font-size:0.9rem;box-sizing:border-box">
                </div>
                <button type="submit" style="background:#2563eb;color:#fff;padding:8px;border:none;border-radius:6px;cursor:pointer;font-size:0.9rem;font-weight:500">Retrieve Record</button>
              </form>
              <div class="muted" style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.05);font-size:0.85rem">
                <p style="margin:0 0 8px 0"><strong>Valid IDs:</strong></p>
                <ul style="margin:0;padding-left:16px">
                  <li>1 through 10</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Result Panel -->
          <div class="card" style="padding:20px">
            <h3 style="margin:0 0 14px 0;font-size:1rem">Query Result</h3>
            
            <?php if (isset($_GET['id']) && !empty($_GET['id'])): 
              $id = $_GET['id'];
              $blind_flag = get_flag('order_flag');
              // Check for blind SQLi payload
              $is_sqli_payload = (strpos($id, "'") !== false || strpos($id, "AND") !== false || 
                                 strpos($id, "OR") !== false || strpos($id, "SLEEP") !== false ||
                                 strpos($id, "UNION") !== false);
              // SQL injection vulnerability: direct unsanitized ID in WHERE clause (INTENTIONAL - DO NOT FIX)
              // This is blind SQLi - no output feedback visible to user, but can be exploited via time-based or boolean techniques
              $conn->query("SELECT * FROM orders WHERE id=$id");
            ?>
              <div style="background:rgba(37,99,235,0.1);border:1px solid rgba(37,99,235,0.2);border-radius:6px;padding:14px;margin-bottom:16px">
                <div style="font-size:0.9rem;color:var(--muted)">Query executed: <span style="color:#fff;font-family:monospace;font-size:0.85rem">SELECT * FROM orders WHERE id=<?php echo htmlspecialchars($id); ?></span></div>
              </div>

              <?php if ($is_sqli_payload && $blind_flag): ?>
                <div style="background:rgba(147,51,234,0.15);border:1px solid rgba(147,51,234,0.4);color:#d8b4fe;padding:14px;border-radius:6px;margin-bottom:16px;font-size:0.9rem;word-break:break-all">
                  <strong>🚩 Blind SQL Injection Payload Detected!</strong><br>
                  <span style="font-family:monospace;font-size:0.85rem">Flag: <?php echo htmlspecialchars($blind_flag); ?></span>
                </div>
              <?php endif; ?>
              
              <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:6px;padding:14px">
                <p class="muted" style="margin:0;font-size:0.9rem">Record retrieved successfully. No data to display in this interface.</p>
                <p class="muted" style="margin:8px 0 0 0;font-size:0.85rem">Processing completed without errors.</p>
              </div>
            <?php else: ?>
              <div class="muted" style="text-align:center;padding:40px 20px">
                <p style="margin:0">Enter a record ID to retrieve data</p>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </main>

    <footer>
      <div class="muted" style="font-size:0.9rem">VectorScope Internal Platform &copy; 2025 | Internal Use Only</div>
    </footer>
  </div>
</body>
</html>
