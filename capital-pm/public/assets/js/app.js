// PM Box - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification polling
    initNotificationPolling();
    
    // Initialize sidebar toggle for mobile
    initSidebarToggle();
    
    // Initialize CSRF token handling
    initCSRFHandling();
});

/**
 * Notification Polling (every 30 seconds)
 */
let lastNotificationCount = 0;

function initNotificationPolling() {
    fetchNotifications();
    setInterval(fetchNotifications, 30000); // 30 seconds
}

async function fetchNotifications() {
    try {
        const response = await fetch(APP_URL + '/api/notifications');
        const data = await response.json();
        
        if (data.success) {
            updateNotificationBadge(data.unread_count);
            updateNotificationDropdown(data.notifications);
            
            // Play sound if new notifications arrived
            if (data.unread_count > lastNotificationCount && lastNotificationCount > 0) {
                playNotificationSound();
            }
            
            lastNotificationCount = data.unread_count;
        }
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    }
}

function updateNotificationBadge(count) {
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }
}

function updateNotificationDropdown(notifications) {
    const dropdown = document.querySelector('.notification-list');
    if (!dropdown) return;
    
    if (notifications.length === 0) {
        dropdown.innerHTML = '<div class="notification-item">No notifications</div>';
        return;
    }
    
    dropdown.innerHTML = notifications.map(notif => `
        <div class="notification-item ${notif.is_read ? '' : 'unread'}" 
             data-id="${notif.id}"
             onclick="markNotificationAsRead(${notif.id})">
            <div class="notification-title">${escapeHtml(notif.title)}</div>
            <div class="notification-message text-muted">${escapeHtml(notif.message)}</div>
            <div class="notification-time text-muted" style="font-size: 0.75rem; margin-top: 0.25rem;">
                ${timeAgo(notif.created_at)}
            </div>
        </div>
    `).join('');
}

function markNotificationAsRead(id) {
    fetch(APP_URL + '/api/notifications/' + id + '/read', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        }
    }).then(() => {
        fetchNotifications();
    });
}

function markAllNotificationsAsRead() {
    fetch(APP_URL + '/api/notifications/read-all', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken()
        }
    }).then(() => {
        fetchNotifications();
        toggleNotificationDropdown();
    });
}

function toggleNotificationDropdown() {
    const dropdown = document.querySelector('.notification-dropdown');
    if (dropdown) {
        dropdown.classList.toggle('show');
    }
}

function playNotificationSound() {
    // Optional: Add notification sound
    // const audio = new Audio('/assets/sounds/notification.mp3');
    // audio.play().catch(() => {});
}

/**
 * Sidebar Toggle (Mobile)
 */
function initSidebarToggle() {
    const toggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (toggle && sidebar) {
        toggle.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(e.target) && !toggle.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        }
    });
}

/**
 * CSRF Token Handling
 */
function initCSRFHandling() {
    // Add CSRF token to all AJAX requests
    const originalFetch = window.fetch;
    window.fetch = function(url, options = {}) {
        if (!options.headers) {
            options.headers = {};
        }
        
        const token = getCsrfToken();
        if (token && !options.headers['X-CSRF-TOKEN']) {
            options.headers['X-CSRF-TOKEN'] = token;
        }
        
        return originalFetch.call(this, url, options);
    };
}

function getCsrfToken() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.content : null;
}

/**
 * Kanban Board - Drag and Drop
 */
function initKanbanDragDrop() {
    const taskCards = document.querySelectorAll('.task-card');
    const kanbanColumns = document.querySelectorAll('.kanban-tasks');
    
    taskCards.forEach(card => {
        card.setAttribute('draggable', true);
        
        card.addEventListener('dragstart', (e) => {
            e.dataTransfer.setData('text/plain', card.dataset.taskId);
            card.style.opacity = '0.5';
        });
        
        card.addEventListener('dragend', () => {
            card.style.opacity = '1';
        });
    });
    
    kanbanColumns.forEach(column => {
        column.addEventListener('dragover', (e) => {
            e.preventDefault();
            column.style.background = '#e2e4e9';
        });
        
        column.addEventListener('dragleave', () => {
            column.style.background = '#ebecf0';
        });
        
        column.addEventListener('drop', (e) => {
            e.preventDefault();
            column.style.background = '#ebecf0';
            
            const taskId = e.dataTransfer.getData('text/plain');
            const newStatus = column.dataset.status;
            
            moveTask(taskId, newStatus);
        });
    });
}

async function moveTask(taskId, status) {
    try {
        const response = await fetch(APP_URL + '/tasks/' + taskId + '/move', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify({ status })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Reload or update the board
            location.reload();
        } else {
            alert(data.error || 'Failed to move task');
        }
    } catch (error) {
        console.error('Failed to move task:', error);
        alert('Failed to move task');
    }
}

/**
 * Create Task Modal
 */
function openCreateTaskModal(projectId) {
    const modal = document.getElementById('createTaskModal');
    if (modal) {
        modal.style.display = 'block';
        document.getElementById('taskProjectId').value = projectId;
    }
}

function closeCreateTaskModal() {
    const modal = document.getElementById('createTaskModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

async function createTask(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch(APP_URL + '/tasks', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken()
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            closeCreateTaskModal();
            location.reload();
        } else {
            alert(result.error || 'Failed to create task');
        }
    } catch (error) {
        console.error('Failed to create task:', error);
        alert('Failed to create task');
    }
}

/**
 * Utility Functions
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function timeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const seconds = Math.floor((now - date) / 1000);
    
    if (seconds < 60) return 'Just now';
    if (seconds < 3600) return Math.floor(seconds / 60) + ' minutes ago';
    if (seconds < 86400) return Math.floor(seconds / 3600) + ' hours ago';
    if (seconds < 604800) return Math.floor(seconds / 86400) + ' days ago';
    
    return date.toLocaleDateString();
}

/**
 * Confirm Delete
 */
function confirmDelete(message, callback) {
    if (confirm(message || 'Are you sure you want to delete this item?')) {
        callback();
    }
}

/**
 * Show Toast Notification
 */
function showToast(message, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.textContent = message;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        padding: 1rem 1.5rem;
        background: ${type === 'success' ? '#27ae60' : type === 'error' ? '#e74c3c' : '#3498db'};
        color: white;
        border-radius: 4px;
        z-index: 9999;
        animation: slideIn 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
