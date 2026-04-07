# Implementation Plan - Missing Features

## Database Changes Needed

### 1. Add categories table
- id, name, color, project_id (nullable for global categories)

### 2. Add category_id to tasks table
- Foreign key to categories

### 3. Add dependencies table
- id, task_id, depends_on_task_id, created_at
- Unique constraint on (task_id, depends_on_task_id)

### 4. Add parent_task_id to tasks table
- For subtasks support

### 5. Add links JSON field to tasks
- Store external links

## Features to Implement

### 1. Categories System
- Create categories when creating project (default: Разработка, Маркетинг, Юр. часть, Дизайн, Тестирование)
- Select category when creating/editing task
- Filter tasks by category

### 2. Dependencies System
- UI to select dependent tasks
- Cycle detection algorithm (DFS)
- Block status change to in_progress if dependencies not done
- Notification when dependency is resolved

### 3. Team Workload Chart
- Calculate active tasks per user
- Bar chart with color coding (green ≤5, yellow 6-7, red >7)
- Display on dashboard

### 4. Excel Export
- Filter by status/category/user
- Generate .xlsx file with all required columns
- Use PHPSpreadsheet or simple XML-based Excel

### 5. Subtasks
- Parent task relationship
- Progress calculation from subtasks
- Visual hierarchy in task list

### 6. Mobile Optimizations
- Touch-friendly buttons (min 44px)
- Bottom navigation menu
- Card layout instead of tables on mobile
- Responsive modals

### 7. Enhanced Notifications
- Deadline tomorrow notification (daily cron/check)
- Dependency unblocked notification

## Files to Create/Modify

### New Files:
- app/Models/Category.php
- app/Controllers/ExportController.php
- public/assets/js/charts.js (for workload chart)
- config/categories.php (default categories)

### Modified Files:
- config/migrations.php (add new tables/columns)
- app/Models/Task.php (add dependency checks, subtasks)
- app/Models/Notification.php (add new notification types)
- app/Controllers/TaskController.php (add dependency validation)
- app/Controllers/DashboardController.php (add workload data)
- app/Views/* (update forms and displays)
- public/assets/css/style.css (mobile optimizations)
- public/assets/js/app.js (enhanced functionality)
