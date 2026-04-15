<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php echo csrf_meta(); ?>
    <title><?php echo $pageTitle ?? 'Sistema Callqui Chico'; ?></title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts: Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0a2b3c;
            --primary-dark: #06212e;
            --primary-light: #1e4a6a;
            --accent: #c9a45c;
            --accent-light: #dbb67b;
            --accent-dark: #a88642;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --bg-page: #f2efe6;
            --text-dark: #1e2b37;
            --text-light: #5a6b7a;
            --white: #ffffff;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
            color: var(--text-dark);
            min-height: 100vh;
        }

        /* Navbar */
        .navbar-callqui {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 0.75rem 0;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid var(--accent);
        }

        .navbar-callqui .navbar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--white);
            text-decoration: none;
        }

        .navbar-callqui .brand-logo {
            width: 45px;
            height: 45px;
            background: var(--white);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .navbar-callqui .brand-logo img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .navbar-callqui .brand-text h5 {
            font-weight: 700;
            font-size: 1.1rem;
            margin: 0;
            color: var(--white);
        }

        .navbar-callqui .brand-text small {
            color: var(--accent);
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* User Menu */
        .user-menu {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-info {
            text-align: right;
            color: var(--white);
        }

        .user-info .user-name {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .user-info .user-role {
            font-size: 0.75rem;
            color: var(--accent);
            text-transform: uppercase;
        }

        .user-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 2px solid var(--accent);
            overflow: hidden;
            background: var(--accent-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary);
            font-weight: 700;
        }

        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Logout Button */
        .btn-logout {
            background: rgba(255,255,255,0.1);
            border: 1px solid rgba(255,255,255,0.2);
            color: var(--white);
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.85rem;
        }

        .btn-logout:hover {
            background: var(--danger);
            border-color: var(--danger);
            color: var(--white);
        }

        /* Page Title */
        .page-header {
            background: var(--white);
            padding: 1.5rem 0;
            border-bottom: 1px solid #e2e8f0;
            margin-bottom: 1.5rem;
        }

        .page-header h2 {
            font-weight: 700;
            font-size: 1.5rem;
            color: var(--primary);
            margin: 0;
        }

        .page-header .breadcrumb {
            margin: 0.5rem 0 0;
            font-size: 0.85rem;
        }

        .page-header .breadcrumb-item a {
            color: var(--accent);
            text-decoration: none;
        }

        .page-header .breadcrumb-item.active {
            color: var(--text-light);
        }

        /* Cards */
        .card-callqui {
            border: none;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-callqui:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .card-callqui .card-header {
            background: var(--primary);
            color: var(--white);
            border-radius: 12px 12px 0 0;
            padding: 1rem 1.25rem;
            border: none;
        }

        .card-callqui .card-header h5 {
            margin: 0;
            font-weight: 600;
            font-size: 1rem;
        }

        .card-callqui .card-body {
            padding: 1.25rem;
        }

        /* Buttons */
        .btn-callqui {
            padding: 0.6rem 1.25rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
        }

        .btn-primary-callqui {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border: none;
            color: var(--white);
        }

        .btn-primary-callqui:hover {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary) 100%);
            color: var(--white);
            transform: translateY(-1px);
        }

        .btn-accent {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            border: none;
            color: var(--primary);
        }

        .btn-accent:hover {
            background: linear-gradient(135deg, var(--accent-light) 0%, var(--accent) 100%);
            color: var(--primary);
        }

        /* Alerts */
        .alert-callqui {
            border: none;
            border-radius: 10px;
            padding: 1rem 1.25rem;
        }

        .alert-success-callqui {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success);
            border-left: 4px solid var(--success);
        }

        .alert-danger-callqui {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger);
            border-left: 4px solid var(--danger);
        }

        .alert-warning-callqui {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning);
            border-left: 4px solid var(--warning);
        }

        .alert-info-callqui {
            background: rgba(59, 130, 246, 0.1);
            color: var(--info);
            border-left: 4px solid var(--info);
        }

        /* Tables */
        .table-callqui {
            border-collapse: separate;
            border-spacing: 0;
        }

        .table-callqui thead th {
            background: var(--primary);
            color: var(--white);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1rem;
            border: none;
        }

        .table-callqui thead th:first-child {
            border-radius: 10px 0 0 0;
        }

        .table-callqui thead th:last-child {
            border-radius: 0 10px 0 0;
        }

        .table-callqui tbody tr {
            background: var(--white);
            transition: background 0.2s ease;
        }

        .table-callqui tbody tr:hover {
            background: rgba(201, 164, 91, 0.05);
        }

        .table-callqui tbody td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        /* Badges */
        .badge-estado {
            padding: 0.4rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-pendiente {
            background: rgba(245, 158, 11, 0.15);
            color: var(--warning);
        }

        .badge-aprobado {
            background: rgba(16, 185, 129, 0.15);
            color: var(--success);
        }

        .badge-rechazado {
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
        }

        .badge-en-revision {
            background: rgba(59, 130, 246, 0.15);
            color: var(--info);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .navbar-callqui .brand-text h5 {
                display: none;
            }
            
            .user-info {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php 
    // Si es una página que requiere autenticación, mostrar navbar
    $mostrarNavbar = $mostrarNavbar ?? true;
    if ($mostrarNavbar && SessionManager::isLoggedIn()): 
    ?>
    <nav class="navbar-callqui">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center w-100">
                <a href="../dashboard/<?php echo SessionManager::getRol(); ?>/<?php echo basename($_SERVER['PHP_SELF']); ?>" class="navbar-brand">
                    <div class="brand-logo">
                        <img src="../assets/img/logo_callqui.png" alt="Logo" onerror="this.style.display='none'">
                    </div>
                    <div class="brand-text">
                        <h5>Callqui Chico</h5>
                        <small>Sistema Comunal</small>
                    </div>
                </a>
                
                <div class="user-menu">
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars(SessionManager::get('nombre')); ?></div>
                        <div class="user-role"><?php echo htmlspecialchars(SessionManager::get('rol')); ?></div>
                    </div>
                    <div class="user-avatar">
                        <?php if (!empty(SessionManager::get('foto'))): ?>
                            <img src="../<?php echo htmlspecialchars(SessionManager::get('foto')); ?>" alt="Foto">
                        <?php else: ?>
                            <?php echo strtoupper(substr(SessionManager::get('nombre'), 0, 1)); ?>
                        <?php endif; ?>
                    </div>
                    <a href="../logout.php" class="btn-logout" title="Cerrar Sesión">
                        <i class="bi bi-box-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </nav>
    <?php endif; ?>
