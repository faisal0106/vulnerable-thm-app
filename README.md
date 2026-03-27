# VectorScope CTF Lab

A deliberately vulnerable Web application for practising common web security vulnerabilities. Built for educational purposes as a self-hosted CTF (Capture The Flag) lab.

**Live instance:** [vulnerable-thm-app--fassufaisal678.replit.app](https://vulnerable-thm-app--fassufaisal678.replit.app)

---

## What is this?

VectorScope is a fake internal asset management platform with six intentionally introduced security flaws. Your job is to find and exploit them, capturing a flag from each one.

Inspired by TryHackMe-style labs — the app looks like a real corporate tool, but the vulnerabilities are real and fully exploitable.

---

## Vulnerabilities & Flags

| # | Vulnerability | Location | Difficulty |
|---|---|---|---|
| 1 | SQL Injection — Login Bypass | `/login.php` | Easy |
| 2 | Reflected XSS | `/search.php` | Easy |
| 3 | Blind SQL Injection | `/orders.php` | Medium |
| 4 | IDOR — Insecure Direct Object Reference | `/profile.php` | Easy |
| 5 | Unauthenticated Admin Access | `/admin.php` | Easy |
| 6 | Misconfiguration & Information Disclosure | Hidden | Medium |

All flags follow the format: `THM{...}`

---

## Getting Started

### Play online

Open the live instance linked above — no setup required.

### Run locally with Docker

```bash
git clone https://github.com/yourusername/your-repo-name.git
cd your-repo-name
docker build -t vectorscope .
docker run -p 8080:8080 vectorscope
```

Then open [http://localhost:8080](http://localhost:8080) in your browser.

The database is created and seeded automatically on first run. No external database needed.

### Run locally with PHP (no Docker)

Requires PHP 8.2+ with the `pdo_sqlite` extension.

```bash
cd Task-1
php -S 0.0.0.0:8080 -t app/
```

Then open [http://localhost:8080](http://localhost:8080).

---

## Project Structure

```
Task-1/
├── app/                  # All PHP source files
├── db/                   # SQLite databases (auto-created)
├── Dockerfile            # Container setup
└── README.md
```

---

## Tech Stack

- PHP 8.2
- SQLite (auto-created on first run, no setup needed)
- Docker

---

## Hints

- Not everything requires authentication
- URL parameters are powerful
- Look at what the server reflects back to you
- Try changing numbers in URLs
- Some pages were never meant to be public
- Think like an attacker

---

## Rules

- For **educational use only**
- Do not apply these techniques to real systems without explicit permission
- All vulnerabilities are intentional and contained within this app

---

## Reset

To reset the lab, simply restart the container — the database wipes and re-seeds itself automatically on startup.

---

## Disclaimer

This application contains intentional security vulnerabilities. It is designed for learning in a controlled environment. The authors are not responsible for misuse.
