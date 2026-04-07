// PM Box - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Initialize notification polling
    initNotificationPolling();
    
    // Initialize search
    initSearch();
    
    // Auto-hide alerts after 5 seconds
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            alert.style.transition = 'opacity 0.3s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        });
    }, 5000);
});

// Notification Polling (every 30 seconds)
let lastNotificationCount = 0;

function initNotificationPolling() {
    fetchNotifications();
    setInterval(fetchNotifications, 30000); // 30 seconds
}

async function fetchNotifications() {
    try {
        const response = await fetch('/api/notifications');
        if (!response.ok) return;
        
        const data = await response.json();
        const badge = document.getElementById('notification-badge');
        const list = document.getElementById('notifications-list');
        
        if (data.unread_count > 0) {
            badge.textContent = data.unread_count;
            badge.style.display = 'inline-block';
            
            if (lastNotificationCount === 0 && data.unread_count > 0) {
                // New notification - could add visual indicator here
                console.log('New notifications!');
            }
        } else {
            badge.style.display = 'none';
        }
        
        lastNotificationCount = data.unread_count;
        
        // Update dropdown list
        if (data.notifications.length === 0) {
            list.innerHTML = '<div class="dropdown-item">No notifications</div>';
        } else {
            list.innerHTML = data.notifications.map(n => `
                <div class="dropdown-item ${n.is_read ? '' : 'unread'}" onclick="markNotificationRead(${n.id})">
                    <strong>${escapeHtml(n.title)}</strong>
                    <p style="font-size: 0.75rem; color: #64748b; margin-top: 0.25rem;">
                        ${escapeHtml(n.message)}
                    </p>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    }
}

async function markNotificationRead(id) {
    try {
        await fetch(`/api/notifications/${id}/read`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            }
        });
        fetchNotifications();
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
}

async function markAllNotificationsRead() {
    try {
        await fetch('/api/notifications/read-all', {
            method: 'POST'
        });
        fetchNotifications();
    } catch (error) {
        console.error('Failed to mark all notifications as read:', error);
    }
}

// Search functionality
function initSearch() {
    const input = document.getElementById('search-input');
    const results = document.getElementById('search-results');
    
    if (!input || !results) return;
    
    let debounceTimer;
    
    input.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        const query = this.value.trim();
        
        if (query.length < 2) {
            results.classList.remove('active');
            return;
        }
        
        debounceTimer = setTimeout(() => {
            performSearch(query);
        }, 300);
    });
    
    // Close results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.navbar-search')) {
            results.classList.remove('active');
        }
    });
}

async function performSearch(query) {
    try {
        const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
        if (!response.ok) return;
        
        const data = await response.json();
        const results = document.getElementById('search-results');
        
        let html = '';
        
        if (data.tasks.length > 0) {
            html += '<div class="search-result-header">Tasks</div>';
            data.tasks.forEach(task => {
                html += `
                    <a href="/tasks/${task.id}" class="search-result-item">
                        <strong>${escapeHtml(task.title)}</strong>
                        <span style="font-size: 0.75rem; color: #64748b;">
                            in ${escapeHtml(task.project_name || 'Unknown Project')}
                        </span>
                    </a>
                `;
            });
        }
        
        if (data.projects.length > 0) {
            html += '<div class="search-result-header">Projects</div>';
            data.projects.forEach(project => {
                html += `
                    <a href="/projects/${project.id}" class="search-result-item">
                        <strong>${escapeHtml(project.name)}</strong>
                    </a>
                `;
            });
        }
        
        if (html === '') {
            html = '<div class="search-result-item">No results found</div>';
        }
        
        results.innerHTML = html;
        results.classList.add('active');
    } catch (error) {
        console.error('Search failed:', error);
    }
}

// Utility function to escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Form submission helper
async function submitForm(formElement, url, method = 'POST') {
    const formData = new FormData(formElement);
    
    try {
        const response = await fetch(url, {
            method: method,
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        return await response.json();
    } catch (error) {
        console.error('Form submission failed:', error);
        throw error;
    }
}

// Task status update
async function updateTaskStatus(taskId, newStatus) {
    try {
        const response = await fetch(`/tasks/${taskId}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `status=${newStatus}&csrf_token=${csrfToken}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update task status');
        }
    } catch (error) {
        console.error('Status update failed:', error);
        alert('Failed to update task status');
    }
}

// Task assignee update
async function updateTaskAssignee(taskId, assignedTo) {
    try {
        const response = await fetch(`/tasks/${taskId}/update`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: `assigned_to=${assignedTo}&csrf_token=${csrfToken}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            location.reload();
        } else {
            alert('Failed to update assignee');
        }
    } catch (error) {
        console.error('Assignee update failed:', error);
        alert('Failed to update assignee');
    }
}

// Confirm delete actions
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Add CSRF token to all forms dynamically
document.querySelectorAll('form[method="POST"]').forEach(form => {
    if (!form.querySelector('input[name="csrf_token"]')) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = csrfToken;
        form.appendChild(input);
    }
});
