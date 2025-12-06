# ChronoNav — Campus Navigation & Scheduling Web App

ChronoNav is a PHP-based web application and landing site for a campus navigation and scheduling system. The project aims to help students import their class schedules (using OCR), view them in a smart calendar, receive reminders, and get turn-by-turn navigation across campus (including offline access). This repository currently contains a complete front-end landing page and structure for a PHP backend, with plans to integrate OCR-based schedule import using Tesseract.

Status
- Repository state: partial / scaffolded
  - Full front-end (index.php) is present.
  - Backend code, assets, and some referenced PHP pages (e.g., `auth/login.php`, `assets/styles/style.css`, `src/`) may be missing or need to be added.
  - Composer is configured to use the Tesseract OCR PHP wrapper.

Table of Contents
- About
- Key Features
- Architecture & Structure
- Requirements
- Installation (Developer)
- Configuration (.env example)
- Running (development)
- Important Notes / Security
- Contributing
- License
- Contact

About
ChronoNav provides:
- OCR-based schedule import from uploaded images/screenshots of official study loads
- Smart schedule/calendar that organizes classes and reminders
- Turn-by-turn navigation and campus maps with offline access
- Notifications and alerts for classes and events

Key Features
- Smart Schedule Import (OCR)
- Turn-by-Turn Campus Navigation
- Offline Access for maps and schedules
- Reminders & Alerts
- Accessibility considerations (voice guidance, high-contrast modes, screen readers)

Architecture & Repository Structure (intended)
- index.php — Main landing / marketing / front-end entrypoint (present)
- assets/ — Frontend static files: CSS, JS, images (add if missing)
- auth/ — Authentication pages (login, register) (implement/complete)
- api/ — REST endpoints for frontend/backend interactions
- backend/ or src/ — PHP classes and application logic (PSR-4 autoload configured)
- classes/ includes/ middleware/ config/ database/ pages/ templates/ — helper folders for modularization
- Composer autoload: PSR-4 mapping "Ericdominicmomo\\ChrononavWebDoss\\" => "src/"

Requirements
- PHP 8.0+ recommended (composer.json supports ^5.3 || ^7.0 || ^8.0 but use >= 8.0)
- Composer
- Tesseract OCR binary (system package) — required for thiagoalessio/tesseract_ocr usage
- PHP extensions typically required:
  - ext-fileinfo
  - ext-mbstring
  - ext-json
  - ext-ctype
  - ext-curl (if calling external APIs)
  - ext-dom
  - ext-zip (optional for packaging)
  - Database extension (pdo_mysql or pdo_pgsql) depending on chosen DB

Installing Tesseract (system)
- Debian/Ubuntu:
  - sudo apt update
  - sudo apt install -y tesseract-ocr
- MacOS (Homebrew):
  - brew install tesseract

Installation (developer)
1. Clone repository
   git clone https://github.com/Vinzz290034/CHRONONAV_WEB_DOSS.git
   cd CHRONONAV_WEB_DOSS

2. Install PHP dependencies
   composer install

3. Ensure Tesseract is installed and accessible in PATH (see above).

4. Create and populate environment configuration
   - Copy `.env.example` to `.env` (create `.env.example` if missing)
   - Provide DB credentials, app secrets, file storage paths, etc. (see example below)

5. Generate autoload files (if needed)
   composer dump-autoload

Configuration (.env.example)
- Create `.env` at project root with content similar to:

```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=chrononav
DB_USERNAME=root
DB_PASSWORD=

# File uploads
UPLOADS_PATH=uploads
MAX_UPLOAD_SIZE=5242880 # 5MB in bytes

# Tesseract
TESSERACT_BINARY=/usr/bin/tesseract

# Mail (optional)
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your@mail.com
MAIL_PASSWORD=secret
MAIL_FROM=chrononav@example.com

# App secrets
APP_KEY=base64:replace_with_secure_random_key
```

Running locally (development)
- Using PHP built-in server (for front-end preview and basic dev):
  php -S 127.0.0.1:8000 -t .

- If you use a more complex framework later (routing, controllers), configure a web server (Apache/Nginx) or run via Docker.

Database / Migrations
- This repository does not yet include migration scripts. Choose a database (MySQL/MariaDB or PostgreSQL), create the schema, and add migration tools (Phinx, Doctrine Migrations, or framework-specific migrations).
- Be sure to securely store DB credentials and do not commit .env files.

Important Notes / Security
- File uploads: validate file types (images only), enforce size limits, and store outside web root or with appropriate access controls.
- Sanitize and validate all user inputs server-side (never rely solely on front-end validation).
- Use CSRF protection for forms and authentication flows.
- Use parameterized queries / prepared statements to protect against SQL injection.
- Keep third-party libraries up-to-date. Verify permissions on uploaded files and directories.
- Do not commit secrets or credentials; use `.env` and `.gitignore` (already configured to ignore .env and uploads/).

Missing / TODO (observed from current repo)
- Backend application code (src/ or backend/) — implement controllers/services for OCR, navigation, user auth, and schedule management.
- Static assets: `assets/styles/style.css` and any referenced JavaScript for UI actions and modals.
- Authentication pages: `auth/login.php`, `auth/register.php`, and session handling.
- Database schema and migrations.
- Unit tests and/ or integration tests.
- CI/CD pipeline for linting, tests, and deployment.

Development suggestions
- Implement a small REST API to separate the front-end from back-end logic.
- Use a PHP micro-framework (Slim/Laravel Lumen) or full-stack framework (Laravel) for faster development (routing, middleware, authentication, migrations).
- Containerize with Docker to provide consistent development and deployment environments (include service for Tesseract).
- Add automated tests (PHPUnit) and static analysis (PHPStan/Psalm).

Contributing
- Contributions are welcome. Please:
  - Fork the repository
  - Create feature branches from `main`
  - Open pull requests describing changes
  - Add tests for new features
  - Follow PSR-12 coding standards

License
- MIT — see `composer.json`. Include a LICENSE file in the repo if not already present.

Acknowledgements
- Uses the thiagoalessio/tesseract_ocr package to integrate Tesseract OCR in PHP.

Contact
- Project lead (as indicated in composer.json): Eric Dominic Momo — Momoe2957@gmail.com
- For repository owner and issues: Vince Andrew Santoya —https://github.com/Vinzz290034/CHRONONAV_WEB_DOSS
- UI/UX Designer and Frontend Developer: Tristan Jesus V. Elvinia — AKUMON12
- Tester: Karl Kent Amarila
---
