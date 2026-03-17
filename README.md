# 🛡️ CTF Vector Scope Lab

Welcome to the CTF Vector Scope Lab.
This platform contains intentionally vulnerable components designed for hands-on practice in web application security.

---

## 🚀 Getting Started

### 1. Start the Lab Environment

Make sure you have Docker installed.

Run:

```bash
docker-compose up --build
```

This will start:

* Backend API server
* Frontend web application

---

### 2. Access the Application

Open your browser and navigate to:

```
http://localhost:3000
```

---

## 🎯 Objective

Your goal is to:

* Explore the application
* Identify vulnerabilities
* Exploit them
* Capture flags

---

## 🏁 Flags

Flags are hidden across different parts of the application.

Example format:

```
THM{something_here}
```

Each flag corresponds to a specific vulnerability.

---

## 🧠 Hints (Minimal Guidance)

* Not everything is properly secured 👀
* Trust boundaries may be broken
* Inputs may not be sanitized
* Access controls might be flawed
* Think like an attacker

---

## 🧪 Possible Attack Vectors

* Authentication bypass
* Input-based attacks
* Client-side injection
* Direct object access
* Hidden or unlinked routes

---

## ⚠️ Rules

* This lab is for **educational purposes only**
* Do not use these techniques on real systems without permission
* All vulnerabilities are intentional

---

## 🔄 Reset Lab (Optional)

To reset the environment:

```bash
docker-compose down -v
docker-compose up --build
```

---

## 💀 Final Note

Flags are not always obvious.
Look deeper. Break things. Think critically.

Good luck, hacker.
