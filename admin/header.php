<!-- Admin Sidebar Header - include in all admin pages -->
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
    /* ===== ADMIN LAYOUT RESET ===== */
    * { box-sizing: border-box; margin: 0; padding: 0; }
    :root {
        --yellow: #FFD600;
        --black: #0A0A0A;
        --white: #FFFFFF;
        --gray: #1A1A1A;
        --gray-2: #222222;
        --gray-text: #888888;
        --sidebar-w: 260px;
    }
    html, body { min-height: 100%; font-family: 'DM Sans', sans-serif; background: #111; color: var(--white); }

    /* ===== SIDEBAR ===== */
    .admin-sidebar {
        position: fixed; left: 0; top: 0; bottom: 0; width: var(--sidebar-w);
        background: var(--black); border-right: 1px solid #1A1A1A;
        display: flex; flex-direction: column;
        z-index: 200; transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    .admin-sidebar.open { transform: translateX(0); }

    @media (min-width: 1024px) {
        .admin-sidebar { transform: translateX(0); }
        .admin-content { margin-left: var(--sidebar-w); }
        .sidebar-overlay { display: none !important; }
    }

    .sidebar-logo {
        padding: 24px 20px; border-bottom: 1px solid #1A1A1A;
        display: flex; align-items: center; gap: 12px;
    }
    .sidebar-logo svg { width: 36px; height: 36px; flex-shrink: 0; }
    .sidebar-logo-text { }
    .sidebar-logo-name { font-family: 'Bebas Neue', sans-serif; font-size: 22px; letter-spacing: 2px; }
    .sidebar-logo-name span { color: var(--yellow); }
    .sidebar-logo-sub { font-size: 10px; letter-spacing: 2px; text-transform: uppercase; color: var(--gray-text); }

    /* Admin badge */
    .admin-badge {
        margin: 16px 20px; background: rgba(255,214,0,0.1); border: 1px solid rgba(255,214,0,0.2);
        border-radius: 10px; padding: 10px 14px;
        display: flex; align-items: center; gap: 10px;
    }
    .admin-avatar {
        width: 32px; height: 32px; border-radius: 50%; background: var(--yellow);
        display: flex; align-items: center; justify-content: center;
        font-family: 'Bebas Neue', sans-serif; font-size: 16px; color: var(--black); flex-shrink: 0;
    }
    .admin-name { font-size: 13px; font-weight: 600; }
    .admin-role { font-size: 10px; letter-spacing: 1px; text-transform: uppercase; color: var(--yellow); }

    /* Nav */
    .sidebar-nav { flex: 1; padding: 8px 12px; overflow-y: auto; }
    .nav-group-label {
        font-size: 10px; letter-spacing: 2px; text-transform: uppercase;
        color: var(--gray-text); padding: 12px 8px 6px;
    }
    .nav-item {
        display: flex; align-items: center; gap: 12px;
        padding: 11px 12px; border-radius: 10px; margin-bottom: 2px;
        text-decoration: none; color: var(--gray-text);
        transition: background 0.2s, color 0.2s;
        font-size: 14px; font-weight: 500;
    }
    .nav-item:hover { background: var(--gray); color: var(--white); }
    .nav-item.active { background: rgba(255,214,0,0.12); color: var(--yellow); }
    .nav-item.active svg { stroke: var(--yellow); }
    .nav-item svg { width: 18px; height: 18px; flex-shrink: 0; }
    .nav-badge {
        margin-left: auto; background: var(--yellow); color: var(--black);
        font-size: 11px; font-weight: 700; border-radius: 50px;
        padding: 2px 7px; font-family: 'Bebas Neue', sans-serif;
    }

    /* Sidebar footer */
    .sidebar-footer { padding: 16px 12px; border-top: 1px solid #1A1A1A; }
    .sidebar-footer .nav-item { color: #666; }

    /* ===== TOP HEADER (mobile) ===== */
    .admin-topbar {
        position: sticky; top: 0; z-index: 100;
        background: rgba(10,10,10,0.97); backdrop-filter: blur(12px);
        border-bottom: 1px solid #1A1A1A;
        padding: 12px 16px; display: flex; align-items: center; gap: 12px;
    }
    .menu-btn {
        width: 36px; height: 36px; border-radius: 10px;
        background: var(--gray); border: 1px solid #2A2A2A;
        display: flex; align-items: center; justify-content: center;
        cursor: pointer; color: var(--white);
    }
    @media (min-width: 1024px) { .menu-btn { display: none; } }
    .topbar-title { font-family: 'Bebas Neue', sans-serif; font-size: 20px; letter-spacing: 2px; flex: 1; }
    .topbar-title span { color: var(--yellow); }

    /* Overlay */
    .sidebar-overlay {
        position: fixed; inset: 0; background: rgba(0,0,0,0.7);
        z-index: 150; display: none;
    }
    .sidebar-overlay.visible { display: block; }

    /* ===== CONTENT AREA ===== */
    .admin-content { min-height: 100vh; padding-bottom: 40px; }
    .content-inner { max-width: 1200px; padding: 24px 20px; }
</style>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-logo">
        <svg viewBox="0 0 80 96" fill="none">
            <path d="M40 4L4 20V52C4 72 22 88 40 94C58 88 76 72 76 52V20L40 4Z" fill="#FFD600"/>
            <path d="M40 18L14 30V50C14 65 27 78 40 83C53 78 66 65 66 50V30L40 18Z" fill="#0A0A0A"/>
            <path d="M30 50L38 58L54 40" stroke="#FFD600" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <div class="sidebar-logo-text">
            <div class="sidebar-logo-name">Safety <span>Academy</span></div>
            <div class="sidebar-logo-sub">Panel Admin</div>
        </div>
    </div>

    <div class="admin-badge">
        <div class="admin-avatar"><?= strtoupper(substr(getUserName(), 0, 1)) ?></div>
        <div>
            <div class="admin-name"><?= htmlspecialchars(getUserName()) ?></div>
            <div class="admin-role">Administrador</div>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-group-label">Principal</div>

        <a href="/admin/dashboard.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/>
                <rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/>
            </svg>
            Dashboard
        </a>

        <a href="/admin/questions.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'questions.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="10"/>
                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>
            </svg>
            Preguntas Quiz
        </a>

        <a href="/admin/sections.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'sections.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                <polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/>
                <line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
            </svg>
            Secciones
        </a>

        <a href="/admin/users.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'users.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Usuarios
        </a>

        <a href="/admin/reports.php" class="nav-item <?= basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/>
                <line x1="6" y1="20" x2="6" y2="14"/>
            </svg>
            Reportes
        </a>

        <div class="nav-group-label" style="margin-top: 8px;">Módulos</div>

        <a href="/M1/index.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
            </svg>
            Módulo 1
            <span class="nav-badge">6</span>
        </a>
    </nav>

    <div class="sidebar-footer">
        <a href="/logout.php" class="nav-item">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
            Cerrar sesión
        </a>
    </div>
</aside>

<script>
function toggleSidebar() {
    document.getElementById('adminSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('visible');
}
</script>
