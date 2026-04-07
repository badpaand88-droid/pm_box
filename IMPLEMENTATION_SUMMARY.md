# Реализованные функции PM Box

## ✅ Выполненные требования

### 1. ЛЕНДИНГ + АВТОРИЗАЦИЯ
- Главная страница с описанием (через `/login` и `/register`)
- Кнопки "Вход/Регистрация" реализованы
- Авторизация через сессии + CSRF токены
- Восстановление пароля не реализовано (по требованию)

### 2. ДАШБОРД
- Статистика: всего проектов, активные проекты, уведомления
- Карточки проектов с быстрым доступом
- Список просроченных задач
- **Новое:** График загрузки команды (color-coded: зеленый ≤5, желтый 6-7, красный >7)
- **Новое:** Кнопка экспорта в Excel

### 3. ПРОЕКТЫ И КАТЕГОРИИ
- При создании проекта автоматически добавляются категории:
  - Разработка (синий)
  - Маркетинг (фиолетовый)
  - Юр. часть (оранжевый)
  - Дизайн (розовый)
  - Тестирование (зеленый)
  - Документация (серый)
- Выбор категории при создании/редактировании задачи
- Модель `Category.php` с методами для работы с категориями

### 4. ЗАДАЧИ (CRUD)
- Полный CRUD операций
- Вложенность (подзадачи) через `parent_task_id`
- Статусы: todo, in_progress, review, done, closed
- Приоритеты: low, medium, high, critical
- Даты: start_date, due_date, duration_days
- Длительность в часах (estimated_hours, actual_hours)
- Комментарии через модель Comment
- Ссылки (JSON поле links)
- Метод `calculateProgress()` для расчета прогресса по подзадачам

### 5. ЗАВИСИМОСТИ
- Таблица `task_dependencies` (task_id, depends_on_task_id)
- Модель `TaskDependency.php` с методами:
  - `getDependencies()` - получить зависимости задачи
  - `getDependents()` - получить зависимые задачи
  - `canStartTask()` - проверка возможности начала задачи
  - `wouldCreateCycle()` - DFS алгоритм для обнаружения циклов
  - `getAllBlockingTasks()` - рекурсивный поиск блокирующих задач
- Проверка при смене статуса на `in_progress` (все зависимости должны быть `done`)

### 6. ЖУРНАЛ ИЗМЕНЕНИЙ
- Автоматический лог в таблицу `task_history`
- Запись: кто, когда, действие, старое значение → новое
- Отображение истории во вкладке задачи
- Метод `logHistory()` в модели Task

### 7. УВЕДОМЛЕНИЯ
- Внутренние уведомления через AJAX-polling (`/api/notifications?since=timestamp`)
- Отображение в колокольчике + панель уведомлений
- Типы уведомлений:
  - `task_assigned` - при назначении задачи
  - `task_status_changed` - при смене статуса
  - `deadline_tomorrow` - дедлайн завтра (метод `notifyDeadlineTomorrow()`)
  - `dependency_resolved` - разблокировка зависимости
- Метод `checkAndNotifyUnblockedTasks()` для проверки разблокированных задач

### 8. ЗАГРУЗКА КОМАНДЫ
- API endpoint `/api/team/workload`
- Бар-чарт на дашборде
- Цветовая индикация:
  - Зеленый: ≤5 задач
  - Желтый: 6-7 задач
  - Красный: >7 задач
- Подсчет активных задач (todo, in_progress, review)
- Weighted load с учетом приоритетов

### 9. ЭКСПОРТ В EXCEL
- Контроллер `ExportController.php`
- Страница фильтрации `/export`
- Фильтры: проект, статус, категория, пользователь, приоритет
- Генерация `.xlsx` файла с колонками:
  - Задача, Категория, Ответственный, Статус, Приоритет
  - Старт, Конец, Длительность, Зависит от, Комментарий
- Использование ZipArchive для создания XLSX (Office Open XML)

### 10. МОБИЛЬНАЯ ВЕРСИЯ
- Touch-кнопки ≥44px (min-height, min-width)
- Нижнее меню (bottom-nav) для мобильной навигации
- Адаптивные модалки (max-height: 90vh, overflow-y: auto)
- Карточный layout вместо таблиц на мобильных
- Адаптивная верстка через media queries (@media max-width: 768px)
- Padding для main-content с учетом нижнего меню

## 📁 Созданные файлы

### Модели
- `app/Models/Category.php` - работа с категориями
- `app/Models/TaskDependency.php` - зависимости между задачами

### Контроллеры
- `app/Controllers/ExportController.php` - экспорт в Excel

### Представления
- `app/Views/export/index.php` - форма экспорта с фильтрами

### Конфигурация
- `config/migrations.php` - обновлено:
  - Таблица `categories`
  - Поля в `tasks`: parent_task_id, category_id, start_date, duration_days, links
  - Таблица `task_dependencies`

### Стили
- `public/assets/css/style.css` - добавлены mobile-friendly стили

## 🔧 Обновленные файлы

### Модели
- `app/Models/Task.php`:
  - Добавлены методы: getSubtasks(), calculateProgress(), getDueTomorrow(), getTeamWorkload(), getFiltered()
  - Обновлены запросы с JOIN категорий

- `app/Models/Notification.php`:
  - Добавлены методы: notifyDeadlineTomorrow(), notifyDependencyResolved(), checkAndNotifyUnblockedTasks()

### Контроллеры
- `app/Controllers/ApiController.php`:
  - Добавлен метод teamWorkload()

- `app/Controllers/DashboardController.php`:
  - Добавлена передача team_workload в view

### Роуты
- `public/index.php`:
  - GET /export - форма экспорта
  - POST /export/excel - генерация Excel
  - GET /api/team/workload - API загрузки команды

### Представления
- `app/Views/dashboard/index.php`:
  - Добавлена карточка экспорта
  - Добавлен график загрузки команды

## 🚀 Как использовать

### Категории
При создании проекта категории создаются автоматически. Для ручного создания:
```php
$categoryModel = new Category();
$categoryModel->createDefaultCategories($projectId);
```

### Зависимости
```php
$depModel = new TaskDependency();

// Добавить зависимость
$depModel->add($taskId, $dependsOnTaskId);

// Проверить возможность старта
$result = $depModel->canStartTask($taskId);
if (!$result['can_start']) {
    // Задача заблокирована
}

// Проверка на циклы
if ($depModel->wouldCreateCycle($taskId, $dependsOnTaskId)) {
    // Нельзя добавить - будет цикл
}
```

### Экспорт
Перейти на `/export`, выбрать фильтры, нажать "Скачать Excel".

### Загрузка команды
На дашборде отображается автоматически.也可以通过 API:
```
GET /api/team/workload?project_id=1
```

### Уведомления о дедлайнах
Вызывать ежедневно (cron):
```php
$notificationModel = new Notification();
$notificationModel->notifyDeadlineTomorrow();
```

## 📝 Примечания

1. Для работы экспорта требуется расширение PHP `ZipArchive`
2. Mobile bottom nav требует добавления HTML в layout (опционально)
3. Для production рекомендуется использовать PHPSpreadsheet вместо самописного XLSX генератора
4. Требуется запуск миграций для новых таблиц
