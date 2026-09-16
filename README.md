# 🛡️ LabGuard

### Automated Computer Laboratory Management, Workstation Kiosk Lock, & Peripheral Telemetry Platform

[![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.5+-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://php.net)
[![Python](https://img.shields.io/badge/Python-3.10+-3776AB?style=for-the-badge&logo=python&logoColor=white)](https://python.org)
[![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white)](https://alpinejs.dev)
[![Institution](https://img.shields.io/badge/Institution-PHINMA_Araullo_University-D4AF37?style=for-the-badge)](#)

---

## 📌 Executive Summary

**LabGuard** is an automated campus laboratory management and hardware auditing platform engineered specifically for institutional academic computing environments (tailored for **PHINMA Araullo University**).

Traditional campus computer labs face chronic issues: unreported peripheral damage, unauthorized workstation use during off-hours, scheduling conflicts, and inaccurate manual paper attendance. LabGuard resolves these challenges by bridging a centralized **Laravel 12 Management Server** with a specialized, low-level **Python/Win32 Kiosk Lock Client** deployed on every laboratory workstation.

Before gaining desktop access, students must authenticate using their university credentials and complete a mandatory **6-Point Pre-Session Hardware Integrity Inspection**. This process creates a transparent digital audit trail, holds students accountable for missing or damaged components, and provides lab technicians with real-time maintenance telemetry.

---

## 🏛️ System Architecture

```
                                  ┌────────────────────────────────────────────────────────┐
                                  │               CENTRAL LABGUARD SERVER                  │
                                  │  • Laravel 12 API / Web Hub                            │
                                  │  • MySQL Central Relational Database                   │
                                  │  • Role-Based Dashboards (Super Admin, Admin, Staff)   │
                                  └───────────────▲────────────────────────▲───────────────┘
                                                  │                        │
                          HTTPS / REST API (JSON) │                        │ Secure Web Browsing
                                                  │                        │ (Livewire & Alpine.js)
        ┌─────────────────────────────────────────┴─────────┐    ┌─────────┴──────────────────────────────┐
        │                                                   │    │                                        │
┌───────┴───────────────────────┐   ┌───────────────────────┴──┐ │  FACILITY WORKSTATIONS / REMOTE ADMIN  │
│  WORKSTATION TERMINAL (PC-01) │   │  WORKSTATION (PC-02..30) │ │  • Super Admin Command Center          │
│  • Win32 Low-Level Keyboard   │   │  • OS-Level Taskbar Lock │ │  • Admin Operational Dashboard         │
│    Hook (WH_KEYBOARD_LL)      │   │  • Hardware Audit GUI    │ │  • Personnel Master Schedule Hub       │
│  • Native C Wi-Fi Manager     │   │  • Real-Time Heartbeat   │ │  • Student Profile & 2FA Management    │
└───────────────────────────────┘   └──────────────────────────┘ └────────────────────────────────────────┘
```

---

## 🚀 Key Features & Subsystems

### 1. Workstation Terminal Security & Lockdown Client (`terminal_lock.py`)

- **Low-Level Keystroke Interception:** Uses a 64-bit safe Ctypes Windows API hook (`WH_KEYBOARD_LL`) to suppress `Alt + Tab`, `Alt + F4`, `Windows Key`, `Ctrl + Esc`, and Task Manager hotkeys.
- **Taskbar Suppression:** Hides primary and secondary taskbars (`Shell_TrayWnd`) at the kernel level until authorized.
- **Native C Wi-Fi Management:** Communicates directly with Windows `wlanapi.dll` to query SSIDs and establish network connectivity without exposing Windows Settings.
- **Fail-Safe Session Heartbeat:** Background thread polls server health every 5 seconds. If connection drops for >25s or a remote lockdown order is issued, the terminal locks immediately.
- **Dual Exit Architecture:** An unobtrusive desktop floating sign-out pill and system tray icon (`pystray` + `Pillow`) allow one-click logout upon session completion.

### 2. Mandatory 6-Point Hardware Integrity Inspection

Before unlocking the desktop, students are prompted with a digital audit checklist:

1. 🖥️ **System Unit:** Casing sealed, power button responsive, internal hardware secure.
2. 🖥️ **Display Monitor:** Clear display panel, free of cracks, lines, or signal flicker.
3. ⚡ **Power Regulator (AVR):** Active voltage surge indicator, grounded power cord.
4. 🖱️ **Optical Mouse:** Smooth laser sensor tracking, mechanical left & right clicks.
5. ⌨️ **Keyboard Unit:** All keycaps present, clean USB connection, responsive typing.
6. 🔌 **Power & I/O Cabling:** Display (HDMI/VGA), power, and peripheral cables firmly seated.

> **Unrestricted Accountability:** Students can proceed even with damaged items. Unchecked items are recorded as `false` in MySQL, automatically flagging the desk for technician triage while exempting the student from liability.

### 3. Role-Based Access Control (RBAC) & Clearance Gate

- **Super Admin:** System-wide management, global analytics, campus-wide emergency lockdown, database backup snapshots, user account provisioning.
- **Admin:** Facility configuration, schedule establishment, incident ticket resolution/discarding, audit log inspection.
- **Personnel (Teachers / Lab Custodians):** Real-time station assignment/release, master weekly schedule management, 24/7 advance roster enrollment via CSV/bulk paste, attendance CSV exports.
- **Student:** Self-service registration, zero-trust profile management (email updating, password changes, Two-Factor Authentication, account deletion).
- **Staff Bypass:** When Admins, Teachers, or Techs log into a physical terminal, the hardware inspection is bypassed, providing immediate desktop access.

### 4. Scheduling Engine, Anti-Conflict Lock, & One-Time Events

- **Anti-Conflict Detection:** Validates time collisions before reserving slots, preventing double-booking between recurring classes.
- **One-Time Event Architecture with Auto-Masking:** Supports guest-speaker seminars, hackathons, and workshops. An event temporarily hides overlapping recurring classes for that date only. Once the event finishes, it disappears, and the recurring class restores automatically.
- **Open / Free Lab Protocol:** Supports non-restricted slots (using `OPEN LAB` or `FREE LAB`) allowing walk-ins without subject enrollment requirements.
- **Permanent Attendance Archive:** Dedicated attendance archive modal preserves attendee logs for past events even after the slot expires from the active schedule.

### 5. Incident Management & False-Alarm Triage

- **Terminal Problem Dispatch:** Students can lodge technical support tickets directly from the lock screen.
- **Tri-State Lifecycle:** Alerts can be marked as **Resolved**, dismissed as **Discarded** (false alarms / trolling), or restored back to **Pending** via an **Undo** mechanism.

### 6. Hardware Analytics & Predictive Telemetry

- **Peripheral Wear Index:** Visual breakdown analyzing which specific component (e.g., mice vs. keyboards) has the highest failure rate over selectable date ranges.
- **Hourly Check-in Velocity:** Area chart charting student check-in volume from 7:00 AM to 8:00 PM to identify peak lab hours.
- **Dual CSV Export:** Generate targeted CSV reports for either **Hardware Check-in Audits** or **Security Incident Tickets** with date and room filtering.

---

## 🛠️ Technology Stack

### Backend Server

- **Framework:** Laravel 12.x running on PHP 8.5+
- **Authentication & Security:** Laravel Jetstream / Fortify, Laravel Sanctum, Cloudflare Turnstile CAPTCHA
- **Frontend UI:** Tailwind CSS, Alpine.js, Chart.js, Heroicons (Vector SVGs)
- **Database:** MySQL 8.0 / MariaDB

### Workstation Terminal Client

- **Core Language:** Python 3.10+
- **GUI Engine:** Tkinter with dynamic glassmorphic styling
- **Win32 Integration:** `ctypes`, `wlanapi.dll`, `user32.dll`, `kernel32.dll`
- **Networking:** `requests`, `urllib3` (Sanctum CSRF Cookie Handshake)
- **Desktop Tray:** `pystray`, `Pillow`

---

## 📂 Project Directory Structure

```text
labguard/
├── app/
│   ├── Actions/Fortify/          # User creation, password rules, profile updaters
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/              # TerminalController.php (Client API endpoints)
│   │   │   └── Dashboard/        # Web admin, analytics, scheduling, alert controllers
│   │   ├── Middleware/           # RestrictStudents.php, CheckClearance.php
│   │   └── Responses/            # Role-based LoginResponse & RegisterResponse
│   └── Models/                   # LabSession, SessionChecklist, Computer, Alert, Schedule
├── database/
│   └── migrations/               # MySQL database schemas & foreign keys
├── resources/
│   └── views/
│       ├── auth/                 # Register, Login, Verify Email (Dark Cyber-Luxe)
│       ├── dashboard/            # Admin analytics, sessions, labs, schedule views
│       └── personnel/            # Teacher lab consoles & alerts
├── routes/
│   ├── api.php                   # Terminal client endpoints (/api/pc/*)
│   └── web.php                   # Role-gated web routes
└── LabGuard_Client/              # Python Client Terminal Directory
    ├── terminal_lock.py          # Full Win32 Lock Screen Client
    └── config.json               # Terminal station identifier (Lab, PC Number, API URL)
```

---

## ⚡ Installation & Quick Start

### 1. Backend Server Setup (Laravel)

#### Prerequisites

- PHP 8.2 or higher
- Composer
- Node.js & NPM
- MySQL Database

#### Setup Steps

```bash
# Clone the repository
git clone https://github.com/your-username/labguard.git
cd labguard

# Install PHP dependencies
composer install

# Install Frontend dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

Configure your `.env` database and domain parameters:

```env
APP_NAME="LabGuard"
APP_URL=https://labguard.test

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=labguard
DB_USERNAME=root
DB_PASSWORD=your_password

SESSION_LIFETIME=43200
SESSION_EXPIRE_ON_CLOSE=false

# Cloudflare Turnstile Keys
TURNSTILE_SITE_KEY=your_site_key
TURNSTILE_SECRET_KEY=your_secret_key
```

Execute database migrations and link storage:

```bash
php artisan migrate
php artisan storage:link
php artisan optimize:clear
```

Compile assets and start the development server:

```bash
npm run build
php artisan serve
```

---

### 2. Workstation Terminal Setup (Python Client)

#### Prerequisites

- Windows 10 or 11 (64-bit)
- Python 3.10+ installed and added to system `PATH`

#### Setup Steps

```powershell
cd LabGuard_Client

# Install required Python libraries
pip install requests urllib3 pystray pillow
```

#### Terminal Configuration (`config.json`)

Configure each workstation's JSON configuration located beside `terminal_lock.py`:

```json
{
    "server_url": "https://labguard.test/api/pc",
    "lab": "LAB 1",
    "pc": "PC-01"
}
```

#### Launching the Terminal Lock

```powershell
python terminal_lock.py
```

> **Emergency Administrator Override:** If you ever need to bypass or exit the lock screen during testing without server intervention, press:
>
> ```
> Ctrl + Alt + Shift + X
> ```
>
> _(This immediately unhooks low-level keyboard listeners, restores the Windows taskbar, and closes the application)._

---

## 🔒 Security & Defense Justifications

| Architecture Decision                    | Defense Justification                                                                                                                                                                           |
| :--------------------------------------- | :---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| **Post-Registration Verification Model** | Creating the account first reserves the institutional Student ID and email immediately in the database, preventing race conditions or impersonation attacks.                                    |
| **Zero-Trust Clearance Gate**            | Creating an account creates a digital profile, but grants **zero access** to physical lab computers. Workstations remain locked until email verification and schedule enrollment are validated. |
| **Self-Service Typo Recovery**           | Unverified students can log in, access their profile to fix misspelled email addresses, and request a resend without requiring technician intervention.                                         |
| **Fail-Safe Offline Mode**               | Terminals that lose network connectivity for >25 seconds automatically lock down, preventing students from unplugging the Ethernet cable to bypass lock policies.                               |

---

## 👥 Authors & Academic Attribution

- **Institution:** PHINMA Araullo University
- **College:** College of Information Technology
- **Project:** Computer Laboratory Management, Access Control, and Workstation Telemetry System
