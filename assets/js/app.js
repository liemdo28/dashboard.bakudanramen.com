/**
 * TaskFlow v2 - Main JS
 */

// Auto-dismiss alerts
document.querySelectorAll('.alert').forEach(a => {
    setTimeout(() => { a.style.opacity = '0'; a.style.transition = 'opacity .3s'; setTimeout(() => a.remove(), 300); }, 4000);
});

// Mobile sidebar
document.querySelectorAll('.sidebar .nav-item').forEach(item => {
    item.addEventListener('click', () => document.getElementById('sidebar')?.classList.remove('open'));
});
document.addEventListener('click', e => {
    const sb = document.getElementById('sidebar');
    const tog = document.querySelector('.mobile-toggle');
    if (sb?.classList.contains('open') && !sb.contains(e.target) && !tog?.contains(e.target)) sb.classList.remove('open');
});

// ESC to close modals
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
        const nd = document.getElementById('notifDropdown');
        if (nd) nd.classList.remove('open');
    }
});

// Notification dropdown
let notifLoaded = false;

function toggleNotifDropdown() {
    const dd = document.getElementById('notifDropdown');
    dd.classList.toggle('open');
    if (dd.classList.contains('open') && !notifLoaded) loadNotifications();
}

function loadNotifications() {
    fetch(location.origin + '/api/notifications')
        .then(r => r.json())
        .then(data => {
            notifLoaded = true;
            const list = document.getElementById('notifList');
            if (!data.notifications?.length) {
                list.innerHTML = '<div style="padding:24px;text-align:center;color:var(--text-muted);font-size:12px">Chưa có thông báo</div>';
                return;
            }
            const icons = {
                task_assigned: '🎯', task_commented: '💬',
                task_due_soon: '⏰', task_overdue: '🔥', task_completed: '✅'
            };
            const colors = {
                task_assigned: 'var(--blue)', task_commented: 'var(--purple)',
                task_due_soon: 'var(--amber)', task_overdue: 'var(--accent)', task_completed: 'var(--green)'
            };
            list.innerHTML = data.notifications.slice(0, 15).map(n => {
                const icon = icons[n.type] || '📌';
                const color = colors[n.type] || 'var(--text-muted)';
                const href = n.task_id ? '/tasks/' + n.task_id : '/inbox';
                const unread = !n.is_read ? 'unread' : '';
                return `<a href="${href}" class="inbox-item ${unread}" style="text-decoration:none;color:inherit" onclick="fetch('/api/notifications/${n.id}/read',{method:'PUT'})">
                    <div class="inbox-dot" style="background:${color}"></div>
                    <div class="inbox-body">
                        <div class="inbox-title">${esc(n.title)}</div>
                        <div class="inbox-msg">${esc(n.message || '')}</div>
                    </div>
                    <div class="inbox-time">${timeAgo(n.created_at)}</div>
                </a>`;
            }).join('');
        })
        .catch(() => {
            document.getElementById('notifList').innerHTML = '<div style="padding:16px;text-align:center;color:var(--text-muted);font-size:11px">Không tải được</div>';
        });
}

function markAllNotifRead() {
    fetch(location.origin + '/api/notifications/read-all', { method: 'PUT' })
        .then(() => {
            const badge = document.querySelector('.notif-badge');
            if (badge) badge.remove();
            notifLoaded = false;
            loadNotifications();
        });
}

// Close notif dropdown on outside click
document.addEventListener('click', e => {
    const wrap = document.getElementById('notifWrap');
    const dd = document.getElementById('notifDropdown');
    if (dd?.classList.contains('open') && wrap && !wrap.contains(e.target)) dd.classList.remove('open');
});

// Helpers
function esc(s) {
    if (!s) return '';
    return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function timeAgo(dt) {
    if (!dt) return '';
    const d = Date.now() - new Date(dt).getTime();
    if (d < 60000) return 'Vừa xong';
    if (d < 3600000) return Math.floor(d/60000) + 'p trước';
    if (d < 86400000) return Math.floor(d/3600000) + 'h trước';
    return Math.floor(d/86400000) + 'd trước';
}

function formatBytes(b) {
    if (!b) return '0 B';
    const k = 1024;
    const s = ['B','KB','MB','GB'];
    const i = Math.floor(Math.log(b) / Math.log(k));
    return parseFloat((b / Math.pow(k,i)).toFixed(1)) + ' ' + s[i];
}

function apiRequest(url, method = 'GET', data = null) {
    const opt = { method, headers: { 'X-Requested-With': 'XMLHttpRequest' } };
    if (data && !(data instanceof FormData)) {
        opt.headers['Content-Type'] = 'application/json';
        opt.body = JSON.stringify(data);
    } else if (data) { opt.body = data; }
    return fetch(url, opt).then(r => r.json());
}

// PWA install prompt
let deferredPrompt;
window.addEventListener('beforeinstallprompt', e => { deferredPrompt = e; });

console.log('TaskFlow v2 loaded');
