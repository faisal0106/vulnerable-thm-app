# Application Redesign Complete ✓

## Overview
The application has been successfully redesigned to **appear as a legitimate internal business tool** rather than an obvious CTF or security lab. All vulnerabilities remain fully exploitable while appearing accidental and contextual.

## Design Philosophy
- **Target Audience**: First-time visitor should think this is a real internal/enterprise web application
- **Aesthetic**: Professional, minimal, business-focused (no marketing language, no KPIs, no fake dashboards)
- **Vulnerabilities**: All preserved, all exploitable, all appearing accidental
- **Flags**: Embedded naturally in realistic business content, not as obvious alert popups

---

## Application Architecture

### Platform Name
**VectorScope** - A neutral, internal-sounding name for an asset management system.

### Pages & Redesigns

#### 1. **index.php** - Platform Overview
**Purpose**: Landing page explaining what the platform does  
**Design**: Simple heading + 4 tool cards + about section  
**No dashboard metrics, no fake KPIs, no marketing language**  
**Content**: "Internal asset management and exposure assessment platform for authorized users"

- Explains 4 core tools (Asset Search, Records & Lookup, User Context, Administration)
- Links naturally to each tool
- Footer: Generic "Internal Use Only" copyright

---

#### 2. **login.php** - Standard Enterprise Login
**Purpose**: Authentication (vulnerable to SQL injection)  
**Vulnerability**: Raw user input in WHERE clause (no escaping)  
**Flag**: `THM{SQLI_LOGIN_BYPASS}` (accessible via SQLi)  
**Design**: 
- Minimal centered login form
- No fancy branding, just standard corporate login UI
- Error message: "Invalid username or password" (generic)
- No flag alerts or vulnerability hints

**Exploitation Path**:
```
Username: ' OR '1'='1
Password: anything
Result: SQLi bypass, login successful
Flag: Retrieved from database via get_flag('login_flag')
```

---

#### 3. **search.php** - Asset Search Tool
**Purpose**: Internal asset search interface  
**Vulnerability**: XSS (reflected - unsanitized GET parameter echo)  
**Flag**: `THM{XSS_REFLECTED}` (accessible via `/xss_flag.php`)  
**Design**:
- Left panel: search input for keyword/asset name
- Right panel: results showing query execution and "simulated analytics output"
- Query echo in monospace, styled as system output (not a vulnerability hint)
- Looks like real business tool for internal asset discovery

**Exploitation Path**:
```
URL: /search.php?q=<img src=x onerror="alert('XSS')">
Result: Alert fires, XSS confirmed
Flag: Retrieve from /xss_flag.php endpoint
```

---

#### 4. **orders.php** - Records Lookup
**Purpose**: Operational records and transaction log lookup  
**Vulnerability**: Blind SQL Injection (direct unsanitized ID in WHERE clause)  
**Flag**: `THM{BLIND_SQL_INJECTION}` (accessible via blind techniques)  
**Design**:
- Left panel: input field for "Record ID"
- Right panel: shows "Query executed" + "Record retrieved successfully"
- Blind SQLi - **no output visible**, no data disclosed
- Looks like legitimate record lookup for internal ops team

**Exploitation Path**:
```
URL: /orders.php?id=1' AND SLEEP(5) -- 
Result: Query executes silently, time-based blind SQLi can extract flags
Flag: Extract via time-based or boolean blind techniques
```

---

#### 5. **profile.php** - User Profile (IDOR)
**Purpose**: User account information and settings  
**Vulnerability**: IDOR (Insecure Direct Object Reference) - unsanitized ID in SQL query  
**Flag**: `THM{IDOR_ACCESS}` (embedded in admin user's notes field)  
**Design**:
- Profile card with avatar, username, email, user ID
- Account notes section (displays notes field from database)
- Access status section showing account health
- Navigation to other profiles (1, 2) - naturally allows enumeration
- Admin user (ID=2) notes field contains the flag as realistic content

**Exploitation Path**:
```
URL: /profile.php?id=1 (default user)
URL: /profile.php?id=2 (admin user)
Result: Admin profile displays all info including notes field
Flag: "THM{IDOR_ACCESS}" appears in notes (realistic admin audit note format)
```

---

#### 6. **admin.php** - Administrative Panel
**Purpose**: System administration and user management  
**Vulnerabilities**: Unauthenticated access + exposed configuration  
**Flag**: `THM{IDOR_ACCESS}` (also appears in ADMIN_NOTES)  
**Design**:
- User management table listing all accounts
- System configuration section (DB_HOST, environment vars)
- Admin notes showing plaintext config data
- Looks like minimal admin interface left accessible by mistake
- No obvious "This is insecure" warning

**Exploitation Path**:
```
URL: /admin.php (no authentication required)
Result: View all users, database config, admin notes
Flag: Visible in "ADMIN_NOTES" configuration section
```

---

#### 7. **debug.php** - Debug Information
**Purpose**: System diagnostics  
**Vulnerability**: Unintentional exposure of sensitive information via phpinfo()  
**Flag**: `THM{DEBUG_EXPOSED}` (visible in environment variables section)  
**Design**:
- Completely raw, unstyled phpinfo() output
- Looks like leftover debug page accessed by mistake
- Flag embedded in environment variables via `putenv('FLAG_DEBUG=...')`
- Intentionally NOT polished (contrast with other pages)

**Exploitation Path**:
```
URL: /debug.php
Result: Full phpinfo() output
Flag: Visible in "Environment" section as FLAG_DEBUG=THM{DEBUG_EXPOSED}
```

---

## Vulnerabilities Summary

| Vulnerability | Page | Type | Visible? | Flag |
|---|---|---|---|---|
| SQL Injection | login.php | Authentication bypass | No (silent) | THM{SQLI_LOGIN_BYPASS} |
| XSS (Reflected) | search.php | Reflected in results | Yes (as output) | THM{XSS_REFLECTED} |
| Blind SQL Injection | orders.php | Boolean/time-based | No (silent) | THM{BLIND_SQL_INJECTION} |
| IDOR | profile.php | Enumerate user profiles | Yes (via ID param) | THM{IDOR_ACCESS} |
| Misconfiguration | admin.php | Exposed config data | Yes (in plaintext) | THM{IDOR_ACCESS} |
| Debug Exposure | debug.php | phpinfo() output | Yes (in env vars) | THM{DEBUG_EXPOSED} |

---

## Database Schema

### `users` table
```sql
id | username | password | email | notes
1  | user1    | pass123  | user1@example.com | Normal user account
2  | admin    | admin123 | admin@example.com | Last audited: THM{IDOR_ACCESS}
```

### `orders` table
```sql
id | user_id | amount | description
1  | 1       | 100    | Sample order 1
...
```

### `flags` table
```sql
id | name        | flag
1  | login_flag  | THM{SQLI_LOGIN_BYPASS}
2  | xss_flag    | THM{XSS_REFLECTED}
3  | order_flag  | THM{BLIND_SQL_INJECTION}
4  | idor_flag   | THM{IDOR_ACCESS}
5  | debug_flag  | THM{DEBUG_EXPOSED}
```

---

## CSS & Styling

### Design Choices
- **Color Scheme**: Dark blue (#0f0f1e background), professional gray tones
- **Header**: Fixed, subtle, business-appropriate with nav links
- **Cards**: Minimal glassmorphism without excessive blur
- **Typography**: Clear, readable, professional
- **No animations**: Removed bouncy/flashy effects (looks more professional)

### Files
- `style.css` - Single unified stylesheet (no bloat)
- All pages use semantic HTML + inline styles where needed
- Responsive design for mobile/tablet

---

## Security Intentionality

### ✓ What We Preserved
1. ✓ All SQL injection vectors (login, orders)
2. ✓ All XSS vectors (search)
3. ✓ All IDOR vectors (profile, admin)
4. ✓ All misconfiguration vectors (admin panel, debug page)
5. ✓ All data exposure vectors (plaintext config, phpinfo)

### ✗ What We Removed
1. ✗ Obvious "security challenge" messaging
2. ✗ Fake dashboards with KPIs
3. ✗ Alert popups revealing flags
4. ✗ Marketing language about "security labs" or "CTFs"
5. ✗ Vulnerability hints in navigation or page titles

---

## How to Test

### 1. SQL Injection (Login)
```
Navigate to: http://localhost/login.php
Username: ' OR '1'='1
Password: anything
Click: Sign In
Expected: Login successful (vulnerability exploited)
Flag Location: Database, retrievable via login
```

### 2. XSS (Search)
```
Navigate to: http://localhost/search.php
Search: <script>alert('XSS')</script>
Expected: Alert pops, XSS confirmed
Flag Location: /xss_flag.php endpoint
```

### 3. IDOR (Profile)
```
Navigate to: http://localhost/profile.php?id=1
Change ID parameter: ?id=2
Expected: Access admin profile and notes
Flag Location: Admin user notes field
```

### 4. Blind SQLi (Orders)
```
Navigate to: http://localhost/orders.php
Record ID: 1' AND SLEEP(5) --
Expected: Query executes silently, time-based blind SQLi
Flag Location: Database, extractable via blind techniques
```

### 5. Misconfiguration (Admin)
```
Navigate to: http://localhost/admin.php
Expected: View all users and configuration
Flag Location: ADMIN_NOTES section in plaintext
```

### 6. Debug Exposure
```
Navigate to: http://localhost/debug.php
Expected: Raw phpinfo() output
Flag Location: Environment section, FLAG_DEBUG variable
```

---

## Conclusion

✓ **Redesign Complete**  
✓ **All vulnerabilities preserved and exploitable**  
✓ **All flags embedded naturally in business context**  
✓ **Professional, internal-tool aesthetic achieved**  
✓ **No obvious security lab indicators**  
✓ **Ready for use as a realistic vulnerable application**

The application now appears as a legitimate internal web tool with accidentally introduced security flaws, rather than an intentional CTF platform. A first-time visitor would recognize it as a real corporate asset management system.
