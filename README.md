[![Deploy](https://github.com/fuermenschen/hfm/actions/workflows/deploy.yml/badge.svg?branch=main)](https://github.com/fuermenschen/hfm/actions/workflows/deploy.yml)
[![Tests](https://github.com/fuermenschen/hfm/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/fuermenschen/hfm/actions/workflows/tests.yml)
[![Linter](https://github.com/fuermenschen/hfm/actions/workflows/lint.yml/badge.svg?branch=main)](https://github.com/fuermenschen/hfm/actions/workflows/lint.yml)
[![Security Checks](https://github.com/fuermenschen/hfm/actions/workflows/security.yml/badge.svg?branch=main)](https://github.com/fuermenschen/hfm/actions/workflows/security.yml)

# Höhenmeter für Menschen

_Höhenmeter für Menschen_ is a Laravel-based application designed to support both the **charity run event** "Höhenmeter für Menschen" and the **association** "Verein für Menschen."

Currently, the app includes modules for athlete registration, donation management, partner organization support, and member management.

## 🚀 Features

- **Event Management:** Athlete registration, donor contributions, and donation tracking for the charity run.
- **Association Management:** Member registration (via the shiny new "Become Member" form!) and member database management.
- Laravel-based backend with Livewire and FluxUI.

## 🌐 Hosted at

[https://hfm-winti.ch](https://hfm-winti.ch)

## 🛠 Installation

To set up Höhenmeter für Menschen locally, follow [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md).

Quick start:

```bash
git clone https://github.com/fuermenschen/hfm.git
cd hfm
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run dev
```

## 🎛 Tech Stack

- **Backend:** Laravel (PHP)
- **Frontend:** Livewire, FluxUI
- **Database:** SQLite (locally) / MariaDB (production)

## 📦 Dependencies

- **PHP:** 8.4+
- **Node.js:** 22+

## ✅ Quality Checks

Before opening a pull request, run:

```bash
composer precommit
```

This command runs formatting (`pint --dirty`), frontend build checks, static analysis (`phpstan`), parallel Pest tests, and Playwright end-to-end tests.

## 📘 Additional Docs

- Development setup: [DEVELOPMENT_SETUP.md](DEVELOPMENT_SETUP.md)
- Repository conventions: [CONVENTIONS.md](CONVENTIONS.md)

## 🤝 Dual Purpose

This app is like a Swiss Army knife for good causes. It helps manage the **Höhenmeter für Menschen** charity run while also supporting the **Verein für Menschen** association. Whether you're climbing mountains or signing up members, we've got your back.
