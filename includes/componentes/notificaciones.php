<?php
/**
 * Componente de Notificaciones - Callqui Chico
 * Profesional v2.0
 */
?>
<style>
.notif-dropdown {
    position: relative;
}

.notif-btn {
    background: none;
    border: none;
    color: white;
    padding: 0.5rem;
    position: relative;
    cursor: pointer;
    font-size: 1.2rem;
}

.notif-btn:hover {
    color: var(--accent);
}

.notif-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: var(--danger);
    color: white;
    font-size: 0.7rem;
    padding: 0.15rem 0.4rem;
    border-radius: 50%;
    font-weight: 700;
    display: none;
}

.notif-badge.has-notif {
    display: block;
}

.notif-menu {
    position: absolute;
    right: 0;
    top: 100%;
    width: 350px;
    background: white;
    border-radius: var(--radius-md);
    box-shadow: var(--shadow-lg);
    z-index: 1000;
    display: none;
    max-height: 400px;
    overflow-y: auto;
}

.notif-menu.show {
    display: block;
    animation: fadeIn 0.2s ease;
}

.notif-header {
    padding: 1rem;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.notif-header h6 {
    margin: 0;
    color: var(--primary);
    font-weight: 600;
}

.notif-list {
    list-style: none;
    padding: 0;
    margin: 0;
}

.notif-item {
    padding: 1rem;
    border-bottom: 1px solid #f1f5f9;
    cursor: pointer;
    transition: background 0.2s ease;
    display: flex;
    gap: 0.75rem;
    align-items: flex-start;
}

.notif-item:hover {
    background: #f8fafc;
}

.notif-item.unread {
    background: rgba(201, 164, 91, 0.08);
}

.notif-icon {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notif-icon.info { background: rgba(59, 130, 246, 0.1); color: #3b82f6; }
.notif-icon.success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
.notif-icon.warning { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
.notif-icon.danger { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

.notif-content {
    flex: 1;
    min-width: 0;
}

.notif-title {
    font-weight: 600;
    font-size: 0.85rem;
    color: var(--text-dark);
    margin-bottom: 0.25rem;
}

.notif-message {
    font-size: 0.8rem;
    color: var(--text-light);
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.notif-time {
    font-size: 0.7rem;
    color: var(--text-muted);
    margin-top: 0.25rem;
}

.notif-empty {
    padding: 2rem;
    text-align: center;
    color: var(--text-muted);
}

.notif-empty i {
    font-size: 2rem;
    margin-bottom: 0.5rem;
    display: block;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<div class="notif-dropdown">
    <button class="notif-btn" onclick="toggleNotif()" title="Notificaciones">
        <i class="bi bi-bell"></i>
        <span class="notif-badge" id="notifBadge">0</span>
    </button>
    
    <div class="notif-menu" id="notifMenu">
        <div class="notif-header">
            <h6>Notificaciones</h6>
            <button class="btn btn-sm btn-link text-primary" onclick="marcarTodasLeidas(event)">
                Marcar todo leído
            </button>
        </div>
        <div class="notif-list" id="notifList">
            <div class="notif-empty">
                <i class="bi bi-bell-slash"></i>
                <p>No hay notificaciones</p>
            </div>
        </div>
    </div>
</div>

<script>
let notifAbierta = false;
let checkInterval = null;

function toggleNotif() {
    const menu = document.getElementById('notifMenu');
    notifAbierta = !notifAbierta;
    menu.classList.toggle('show', notifAbierta);
    
    if (notifAbierta) {
        cargarNotificaciones();
    }
}

function cargarNotificaciones() {
    fetch('api/notificaciones.php?action=list')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                renderNotificaciones(data.notificaciones);
                actualizarBadge(data.noLeidas);
            }
        });
}

function renderNotificaciones(notificaciones) {
    const list = document.getElementById('notifList');
    
    if (!notificaciones || notificaciones.length === 0) {
        list.innerHTML = `
            <div class="notif-empty">
                <i class="bi bi-bell-slash"></i>
                <p>No hay notificaciones</p>
            </div>
        `;
        return;
    }
    
    list.innerHTML = notificaciones.map(n => `
        <div class="notif-item ${n.leido ? '' : 'unread'}" onclick="marcarLeida(${n.id})">
            <div class="notif-icon ${n.tipo}">
                <i class="bi bi-${getIcon(n.tipo)}"></i>
            </div>
            <div class="notif-content">
                <div class="notif-title">${escapeHtml(n.titulo)}</div>
                <div class="notif-message">${escapeHtml(n.mensaje)}</div>
                <div class="notif-time">${tiempoRelativo(n.fecha_creacion)}</div>
            </div>
        </div>
    `).join('');
}

function getIcon(tipo) {
    const icons = {
        'info': 'info-circle',
        'success': 'check-circle',
        'warning': 'exclamation-triangle',
        'danger': 'x-circle'
    };
    return icons[tipo] || 'info-circle';
}

function actualizarBadge(cantidad) {
    const badge = document.getElementById('notifBadge');
    if (cantidad > 0) {
        badge.textContent = cantidad > 9 ? '9+' : cantidad;
        badge.classList.add('has-notif');
    } else {
        badge.classList.remove('has-notif');
    }
}

function marcarLeida(id) {
    fetch('api/notificaciones.php?action=marcar_leida', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'id=' + id
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones();
        }
    });
}

function marcarTodasLeidas(e) {
    e.stopPropagation();
    fetch('api/notificaciones.php?action=marcar_todas_leidas', {
        method: 'POST'
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            cargarNotificaciones();
        }
    });
}

function tiempoRelativo(fecha) {
    const now = new Date();
    const then = new Date(fecha);
    const diff = Math.floor((now - then) / 1000);
    
    if (diff < 60) return 'Ahora';
    if (diff < 3600) return 'Hace ' + Math.floor(diff/60) + ' min';
    if (diff < 86400) return 'Hace ' + Math.floor(diff/3600) + ' h';
    if (diff < 604800) return 'Hace ' + Math.floor(diff/86400) + ' días';
    return then.toLocaleDateString('es-PE');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Cerrar al hacer click fuera
document.addEventListener('click', function(e) {
    const dropdown = document.querySelector('.notif-dropdown');
    if (dropdown && !dropdown.contains(e.target)) {
        document.getElementById('notifMenu').classList.remove('show');
        notifAbierta = false;
    }
});

// Auto-actualizar cada 30 segundos
setInterval(() => {
    if (notifAbierta) {
        cargarNotificaciones();
    } else {
        fetch('api/notificaciones.php?action=no_leidas')
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    actualizarBadge(data.noLeidas);
                }
            });
    }
}, 30000);
</script>
