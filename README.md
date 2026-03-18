# VectorScope CTF Lab

A deliberately vulnerable web application for practising common web security vulnerabilities. Built for educational purposes as a self-hosted CTF (Capture The Flag) lab.

**Live instance:** [crescence-ctf.up.railway.app](https://crescence-ctf.up.railway.app)

---

## What is this?

VectorScope is a fake internal asset management platform with six intentionally introduced security flaws. Your job is to find and exploit them, capturing a flag from each one.

This is inspired by TryHackMe-style labs — the app looks like a real corporate tool, but the vulnerabilities are real and fully exploitable.

---

## Vulnerabilities & Flags

| # | Vulnerability | Location | Difficulty |
|---|---|---|---|
| 1 | SQL Injection — Login Bypass | `/login.php` | Easy |
| 2 | Reflected XSS | `/search.php` | Easy |
| 3 | Blind SQL Injection | `/orders.php` | Medium |
| 4 | IDOR — Insecure Direct Object Reference | `/profile.php` | Easy |
| 5 | Unauthenticated Admin Access | `/admin.php` | Easy |
| 6 | Debug Page Exposure | `/debug.php` | Easy |

All flags follow the format: `THM{...}`

---

## Getting Started

### Play online

Just open the live instance linked above — no setup required.

### Run locally with Docker

```bash
git clone https://github.com/yourusername/your-repo-name.git
cd your-repo-name
docker build -t vectorscope .
docker run -p 8080:80 vectorscope
```

Then open [http://localhost:8080](http://localhost:8080) in your browser.

> The database is created and seeded automatically on first run. No external database needed.

### Run locally with PHP (no Docker)

Requires PHP 8.2+ with the `pdo_sqlite` extension.

```bash
cd Task-1
php -S 0.0.0.0:8080 -t app/
```

Then open [http://localhost:8080](http://localhost:8080).

---

## Tech Stack

- PHP 8.2
- SQLite (auto-created on first run, no setup needed)
- Apache 2.4
- Docker

---

## Deployment

This app is deployed on [Railway](https://railway.app) using the included Dockerfile.

To deploy your own instance:

1. Fork this repo
2. Go to [railway.app](https://railway.app) → New Project → Deploy from GitHub
3. Select your fork
4. Under **Settings → Networking**, generate a domain
5. Done — Railway auto-builds and deploys from the Dockerfile

---

## Hints

- Not everything requires authentication
- URL parameters are powerful
- Look at what the server reflects back to you
- Try changing numbers in URLs
- Some pages were never meant to be public

---

## Rules

- For **educational use only**
- Do not apply these techniques to real systems without explicit permission
- All vulnerabilities are intentional and contained within this app

---

## Reset

On Railway, every redeploy wipes and re-seeds the database automatically — so just trigger a redeploy to reset the lab to its original state.

For local Docker, stop and restart the container:

```bash
docker stop <container-id>
docker run -p 8080:80 vectorscope
```

---

## Disclaimer

This application contains intentional security vulnerabilities. It is designed for learning in a controlled environment. The authors are not responsible for misuse.
