<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>VectorScope - Asset Management</title>
  <script src="https://cdn.tailwindcss.com"></script>
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
          <a href="home.php">Home</a>
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
      <div style="max-width:1000px;margin:0 auto">
        <h1 style="font-size:2rem;margin-bottom:8px">VectorScope</h1>
        <p class="muted" style="margin-bottom:20px">Internal asset management and exposure assessment platform for authorized users.</p>

        <div class="capability-grid">
          <div class="card" style="padding:18px">
            <h3 style="margin:0 0 8px 0;font-size:1.1rem">Asset Search</h3>
            <p class="muted" style="margin:0">Query and explore internal assets, configurations, and metadata across the organization.</p>
            <a href="search.php" style="margin-top:12px;display:inline-block;color:var(--accent);text-decoration:none;font-size:0.9rem">Access Tool →</a>
          </div>

          <div class="card" style="padding:18px">
            <h3 style="margin:0 0 8px 0;font-size:1.1rem">Records &amp; Lookup</h3>
            <p class="muted" style="margin:0">Review operational records, transaction logs, and system diagnostics.</p>
            <a href="orders.php" style="margin-top:12px;display:inline-block;color:var(--accent);text-decoration:none;font-size:0.9rem">Access Tool →</a>
          </div>

          <div class="card" style="padding:18px">
            <h3 style="margin:0 0 8px 0;font-size:1.1rem">User Context</h3>
            <p class="muted" style="margin:0">Review user accounts, permissions, and access boundaries.</p>
            <a href="profile.php?id=1" style="margin-top:12px;display:inline-block;color:var(--accent);text-decoration:none;font-size:0.9rem">View Profile →</a>
          </div>

          <div class="card" style="padding:18px">
            <h3 style="margin:0 0 8px 0;font-size:1.1rem">Administration</h3>
            <p class="muted" style="margin:0">Internal administrative functions. Restricted access.</p>
            <a href="admin.php" style="margin-top:12px;display:inline-block;color:var(--accent);text-decoration:none;font-size:0.9rem">Admin Panel →</a>
          </div>
        </div>

        <div class="card" style="padding:18px;margin-top:20px;background:rgba(255,255,255,0.01)">
          <h3 style="margin:0 0 8px 0;font-size:1rem">About This Platform</h3>
          <p class="muted" style="margin:0;line-height:1.6">VectorScope is an internal tool used by authorized personnel to manage and assess organizational assets. The platform provides search, lookup, and administrative capabilities for internal use only. For access requests or technical issues, contact your system administrator.</p>
        </div>
      </div>
    </main>

    <footer>
      <div class="muted" style="font-size:0.9rem">VectorScope Internal Platform &copy; 2025 | Developed by Faisal Ashraf | Internal Use Only</div>
    </footer>
  </div>
</body>
</html>
