<?php require_once('config.php'); ?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Asset Search - VectorScope</title>
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
          <h2 style="margin:0 0 8px 0">Asset Search</h2>
          <p class="muted" style="margin:0">Find and retrieve internal assets, configurations, and metadata</p>
        </div>

        <div style="display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start">
          <!-- Search Panel -->
          <div>
            <div class="card" style="padding:20px">
              <h3 style="margin:0 0 14px 0;font-size:1rem">Search Query</h3>
              <form style="display:flex;flex-direction:column;gap:12px">
                <div>
                  <label style="display:block;margin-bottom:6px;font-size:0.85rem;color:var(--muted)">Asset Name or Keyword</label>
                  <input type="text" name="q" placeholder="Search..." style="width:100%;padding:8px 10px;border:1px solid rgba(255,255,255,0.1);border-radius:6px;background:rgba(255,255,255,0.03);color:#fff;font-size:0.9rem;box-sizing:border-box">
                </div>
                <button type="submit" style="background:#2563eb;color:#fff;padding:8px;border:none;border-radius:6px;cursor:pointer;font-size:0.9rem;font-weight:500">Search</button>
              </form>
              <div class="muted" style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(255,255,255,0.05);font-size:0.85rem">
                <p style="margin:0 0 8px 0"><strong>Tips:</strong></p>
                <ul style="margin:0;padding-left:16px">
                  <li>Use keywords to find assets</li>
                  <li>Results appear in real-time</li>
                </ul>
              </div>
            </div>
          </div>

          <!-- Results Panel -->
          <div class="card" style="padding:20px">
            <h3 style="margin:0 0 14px 0;font-size:1rem">Search Results</h3>
            
            <?php if (isset($_GET['q']) && !empty($_GET['q'])): 
              $q = $_GET['q'];
              $xss_flag = get_flag('xss_flag');
              // Check for XSS payloads (script tags, event handlers, etc)
              $is_xss_payload = (stripos($q, '<script') !== false || stripos($q, 'onerror=') !== false || 
                                stripos($q, 'onload=') !== false || stripos($q, 'onclick=') !== false ||
                                stripos($q, 'javascript:') !== false || stripos($q, 'alert(') !== false);
              // XSS vulnerability: unsanitized echo of GET parameter (INTENTIONAL - DO NOT FIX)
            ?>
              <div style="background:rgba(37,99,235,0.1);border:1px solid rgba(37,99,235,0.2);border-radius:6px;padding:14px;margin-bottom:16px">
                <div style="font-size:0.9rem;color:var(--muted)">Query executed: <span style="color:#fff;font-family:monospace;font-size:0.85rem"><?php echo $q; ?></span></div>
              </div>
              
              <?php if ($is_xss_payload && $xss_flag): ?>
                <div style="background:rgba(147,51,234,0.15);border:1px solid rgba(147,51,234,0.4);color:#d8b4fe;padding:14px;border-radius:6px;margin-bottom:16px;font-size:0.9rem;word-break:break-all">
                  <strong>🚩 XSS Payload Detected!</strong><br>
                  <span style="font-family:monospace;font-size:0.85rem">Flag: <?php echo htmlspecialchars($xss_flag); ?></span>
                </div>
              <?php endif; ?>
              
              <div style="background:rgba(255,255,255,0.02);border:1px solid rgba(255,255,255,0.05);border-radius:6px;padding:14px;min-height:80px">
                <p class="muted" style="margin:0;font-size:0.9rem">Analytics output simulating asset metadata from the system. Direct content from your query appears below:</p>
                <div style="margin-top:12px;padding:10px;background:rgba(0,0,0,0.3);border-left:3px solid #666;font-family:monospace;font-size:0.85rem;color:#ccc;white-space:pre-wrap;word-break:break-all">
<?php echo $q; ?>
                </div>
              </div>
            <?php else: ?>
              <div class="muted" style="text-align:center;padding:40px 20px">
                <p style="margin:0">Enter a search query to begin</p>
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
