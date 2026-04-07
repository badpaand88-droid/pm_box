# PM Box - Lightweight Project Management System

A lightweight Jira alternative built with pure PHP 8.2+ and MySQL, designed for shared hosting environments.

## 🚀 Features

- **Project Management** - Create and manage multiple projects
- **Task Tracking** - Kanban-style task boards with status workflow
- **User Roles** - Admin, Manager, Developer, Viewer roles
- **Comments & History** - Track all changes and discussions
- **Notifications** - AJAX polling-based real-time notifications (30s interval)
- **Search** - Global search for tasks and projects
- **Responsive Design** - Mobile-first CSS with Flexbox/Grid
- **Security** - CSRF protection, SQL injection prevention, XSS protection

## 📋 Requirements

- PHP 8.2 or higher
- MySQL 8.0+ / MariaDB 10.6+
- Apache/Nginx with mod_rewrite support

## 🛠️ Installation

### 1. Clone/Upload Files

Upload all files to your shared hosting account.

### 2. Configure Environment

Copy `.env.example` to `.env` (outside the `public/` directory):

```bash
cp .env.example .env
```

Edit `.env` with your database credentials:

```env
DB_HOST=localhost
DB_NAME=pm_box
DB_USER=your_db_user
DB_PASS=your_db_password

APP_NAME="PM Box"
APP_URL=http://your-domain.com
APP_ENV=production
APP_DEBUG=false

CSRF_SECRET=generate-a-random-string-here
```

### 3. Set Document Root

Point your web server's document root to the `public/` directory.

### 4. Run Installer

Visit `http://your-domain.com/install.php` in your browser.

The installer will:
- Create the database (if it doesn't exist)
- Create all required tables
- Create a default admin user

**Default Admin Credentials:**
- Email: `admin@example.com`
- Password: `admin123`

⚠️ **IMPORTANT:** Delete `install.php` after successful installation!

### 5. Secure Your Installation

1. Delete `public/install.php`
2. Change the default admin password
3. Update `CSRF_SECRET` in `.env` with a random string
4. Set `APP_ENV=production` and `APP_DEBUG=false`

## 📁 Directory Structure

```
pm_box/
├── .env                    # Environment configuration
├── app/
│   ├── Controllers/        # Request handlers
│   ├── Core/               # Core classes (Auth, Database, Router, etc.)
│   ├── Models/             # Data models
│   └── Views/              # HTML templates
├── config/
│   └── migrations.php      # Database schema
├── public/                 # Document root
│   ├── assets/
│   │   ├── css/style.css
│   │   └── js/app.js
│   ├── uploads/            # File attachments
│   ├── index.php           # Application entry point
│   └── install.php         # One-time installer
└── storage/                # Logs and cache
```

## 🔐 Security Features

- **Password Hashing**: BCrypt via `password_hash()`
- **SQL Injection Prevention**: PDO prepared statements
- **CSRF Protection**: Token validation on all forms
- **XSS Prevention**: Output escaping with `htmlspecialchars()`
- **Session Security**: Regeneration on login
- **Role-based Access Control**: Different permissions per role

## 📝 User Roles

| Role | Permissions |
|------|-------------|
| Admin | Full access to everything |
| Manager | Create/edit/delete projects and tasks |
| Developer | View assigned projects, create/edit own tasks |
| Viewer | Read-only access |

## 🔧 Troubleshooting

### Database Connection Error
1. Check `.env` file exists and has correct credentials
2. Verify MySQL server is running
3. Ensure database user has proper permissions

### Permission Denied
Ensure these directories are writable by the web server:
- `public/uploads/`
- `storage/`

---

Built with ❤️ using pure PHP
