# PM Box - Project Management System

Лёгкая альтернатива Jira на чистом PHP 8.2+ и MySQL для shared-хостинга.

## 🚀 Особенности

- **Чистый PHP 8.2+** без фреймворков
- **MySQL/MariaDB** с PDO
- **Vanilla JS/CSS** без сборщиков
- **MVC-подобная архитектура**
- **Адаптивный дизайн** (mobile-first)
- **Безопасность**: CSRF, XSS, SQLi защита
- **Роли**: Admin, Manager, Developer, Viewer
- **AJAX-polling** для уведомлений (30 сек)

## 📁 Структура проекта

```
capital-pm/
├── public/           # Document root
│   ├── index.php     # Точка входа
│   ├── .htaccess     # URL rewriting
│   └── assets/       # CSS, JS, изображения
├── app/              # Приложение
│   ├── config/       # Конфигурация
│   ├── core/         # Ядро (Database, Auth, Router)
│   ├── models/       # Модели данных
│   ├── controllers/  # Контроллеры
│   └── views/        # Представления
├── database/         # SQL схема
├── .env              # Переменные окружения
└── composer.json     # Зависимости
```

## 🛠️ Установка

### 1. Требования
- PHP 8.2+
- MySQL 8+ или MariaDB 10.6+
- mod_rewrite для Apache

### 2. Настройка БД
Скопируйте `.env.example` в `.env` и настройте:

```env
DB_HOST=localhost
DB_NAME=pm_box
DB_USER=root
DB_PASS=your_password
APP_URL=http://localhost
SECRET_KEY=your_secret_key_32_chars_min
```

### 3. Импорт БД
Импортируйте `database/schema.sql` в вашу базу данных:

```bash
mysql -u root -p pm_box < database/schema.sql
```

### 4. Настройка веб-сервера
Установите `public/` как document root.

Для Apache убедитесь, что `.htaccess` активен:
```apache
RewriteEngine On
```

### 5. Установка зависимостей
```bash
composer install
```

### 6. Первый вход
- Логин: `admin@example.com`
- Пароль: `admin123`

**Важно:** Смените пароль после первого входа!

## 🔐 Роли пользователей

| Роль | Права |
|------|-------|
| Admin | Полный доступ ко всем проектам и пользователям |
| Manager | Создание проектов, управление задачами |
| Developer | Работа с назначенными задачами |
| Viewer | Только просмотр |

## 📋 Функционал

- ✅ Управление проектами (CRUD)
- ✅ Kanban-доска для задач
- ✅ Приоритеты и дедлайны
- ✅ Комментарии к задачам
- ✅ История изменений
- ✅ Уведомления (AJAX-polling)
- ✅ Поиск по задачам и проектам
- ✅ Экспорт в Excel (через PhpSpreadsheet)

## 🔒 Безопасность

- `password_hash()` для паролей
- Подготовленные выражения PDO
- CSRF-токены для всех форм
- Экранирование вывода
- Валидация входных данных

## 📝 Лицензия

MIT
