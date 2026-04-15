<?php
require_once "../../includes/verificar_sesion.php";
$conn = getDB();
$pageTitle = "Dashboard - Secretario";
$mostrarNavbar = true;
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Ejecutivo | Secretaría Callqui Chico</title>
    
    <!-- Google Fonts: Inter + Playfair Display para títulos -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AOS Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0a2b3c;
            --primary-dark: #06212e;
            --primary-light: #1e4a6a;
            --accent: #c9a45b;
            --accent-light: #dbb67b;
            --accent-dark: #a88642;
            --gold: #d4af37;
            --bg-page: #f2efe6;
            --text-dark: #1e2b37;
            --text-light: #5a6b7a;
            --white: #ffffff;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }

        body {
            background-color: var(--bg-page);
            color: var(--text-dark);
            font-family: 'Inter', sans-serif;
            margin: 0;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Fondo con textura sutil */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23c9a45b' fill-opacity='0.05'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            pointer-events: none;
            z-index: -1;
        }

        /* HEADER INSTITUCIONAL MEJORADO */
        .admin-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: var(--white);
            padding: 5px 0;
            box-shadow: var(--shadow-lg);
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 3px solid var(--accent);
        }

        .header-wrapper {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
        }

        .brand-box {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .brand-logo {
            position: relative;
        }

        .brand-logo img {
            width: 55px;
            height: 55px;
            background: white;
            padding: 8px;
            border-radius: 15px;
            box-shadow: 0 8px 15px rgba(0,0,0,0.3);
            transition: transform 0.3s ease;
        }

        .brand-logo:hover img {
            transform: rotate(5deg) scale(1.05);
        }

        .brand-text h2 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            margin: 0;
            font-weight: 900;
            letter-spacing: 1px;
            background: linear-gradient(135deg, #fff, var(--accent-light));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .brand-text span {
            font-size: 0.7rem;
            opacity: 0.8;
            text-transform: uppercase;
            font-weight: 500;
            letter-spacing: 2px;
            color: var(--accent-light);
        }

        .user-nav {
            display: flex;
            align-items: center;
            gap: 25px;
            background: rgba(255,255,255,0.05);
            padding: 8px 20px;
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .user-badge {
            background: var(--accent);
            color: var(--primary-dark);
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .logout-link {
            color: #fff;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 30px;
            background: rgba(255,255,255,0.1);
        }

        .logout-link:hover {
            background: #ef4444;
            color: white;
            transform: translateY(-2px);
        }

        /* CONTENIDO PRINCIPAL */
        .admin-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 30px;
            flex: 1;
        }

        /* INTRO CON DECORACIÓN */
        .page-intro {
            margin-bottom: 50px;
            position: relative;
            padding-left: 20px;
        }

        .page-intro::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 5px;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            border-radius: 5px;
        }

        .page-intro h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2.8rem;
            font-weight: 900;
            color: var(--primary);
            margin-bottom: 5px;
            line-height: 1.2;
        }

        .page-intro p {
            color: var(--text-light);
            font-size: 1.1rem;
            font-weight: 400;
            max-width: 600px;
        }

        /* ESTADÍSTICAS RÁPIDAS */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(201, 164, 91, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--primary-dark);
        }

        .stat-info h4 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
            line-height: 1;
        }

        .stat-info p {
            color: var(--text-light);
            margin: 5px 0 0;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* GRID DE GESTIÓN MEJORADO */
        .admin-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 30px;
            margin-top: 20px;
        }

        .admin-card {
            background: var(--white);
            border-radius: 24px;
            padding: 30px;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(201, 164, 91, 0.2);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-decoration: none;
            color: inherit;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .admin-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, var(--accent), var(--accent-light));
            transform: translateX(-100%);
            transition: transform 0.4s ease;
        }

        .admin-card:hover::before {
            transform: translateX(0);
        }

        .admin-card::after {
            content: "";
            position: absolute;
            bottom: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(201,164,91,0.1) 0%, transparent 70%);
            border-radius: 50%;
            transform: translate(50%, 50%);
            transition: all 0.4s ease;
        }

        .admin-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent);
        }

        .admin-card:hover::after {
            transform: translate(30%, 30%) scale(1.5);
        }

        .card-icon {
            font-size: 2.5rem;
            color: var(--accent);
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            transition: transform 0.3s ease;
        }

        .admin-card:hover .card-icon {
            transform: scale(1.1) rotate(5deg);
            color: var(--accent-dark);
        }

        .admin-card h3 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--primary);
            position: relative;
            z-index: 1;
        }

        .admin-card p {
            font-size: 0.95rem;
            color: var(--text-light);
            line-height: 1.6;
            margin-bottom: 25px;
            position: relative;
            z-index: 1;
        }

        .card-footer {
            margin-top: auto;
            display: flex;
            align-items: center;
            font-weight: 700;
            font-size: 0.9rem;
            color: var(--accent);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            position: relative;
            z-index: 1;
        }

        .card-footer i {
            margin-left: 10px;
            transition: transform 0.3s ease;
        }

        .admin-card:hover .card-footer i {
            transform: translateX(8px);
        }

        /* CARD DESTACADA (Adjudicaciones) */
        .admin-card.featured {
            background: linear-gradient(135deg, #fff, #faf7f0);
            border: 2px solid var(--accent);
            position: relative;
        }

        .admin-card.featured::before {
            background: linear-gradient(90deg, var(--gold), var(--accent));
        }

        .featured-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--gold);
            color: var(--primary-dark);
            padding: 5px 15px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            z-index: 2;
            box-shadow: 0 4px 10px rgba(212, 175, 55, 0.3);
        }

        /* SECCIÓN DE ACCIONES RÁPIDAS */
        .quick-actions {
            margin-top: 50px;
            background: white;
            border-radius: 24px;
            padding: 30px;
            box-shadow: var(--shadow-md);
            border: 1px solid rgba(201,164,91,0.1);
        }

        .quick-actions h3 {
            font-family: 'Playfair Display', serif;
            color: var(--primary);
            margin-bottom: 25px;
            font-size: 1.8rem;
            font-weight: 700;
        }

        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .action-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px 20px;
            background: var(--bg-page);
            border-radius: 16px;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .action-btn:hover {
            background: var(--primary);
            color: white;
            transform: translateY(-3px);
            box-shadow: var(--shadow-md);
            border-color: var(--accent);
        }

        .action-btn i {
            font-size: 1.3rem;
            color: var(--accent);
            transition: all 0.3s ease;
        }

        .action-btn:hover i {
            color: white;
            transform: scale(1.1);
        }

        /* BOTÓN SINCRO ESPECIAL */
        .btn-sync-container {
            text-align: center;
            margin: 60px 0 20px;
        }

        .btn-sync {
            display: inline-flex;
            align-items: center;
            gap: 15px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            padding: 18px 45px;
            border-radius: 60px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 20px 30px -5px rgba(10, 43, 60, 0.4);
            transition: all 0.3s ease;
            border: 1px solid var(--accent);
            position: relative;
            overflow: hidden;
        }

        .btn-sync::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-sync:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 25px 35px -5px rgba(201, 164, 91, 0.4);
            background: linear-gradient(135deg, var(--primary-light), var(--primary));
        }

        .btn-sync:hover::before {
            left: 100%;
        }

        .btn-sync i {
            font-size: 1.3rem;
            transition: transform 0.3s ease;
        }

        .btn-sync:hover i {
            transform: rotate(180deg);
        }

        /* FOOTER MEJORADO */
        .admin-footer {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 0 30px;
            margin-top: 80px;
            position: relative;
            overflow: hidden;
        }

        .admin-footer::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--accent), transparent);
        }

        .footer-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 40px;
            position: relative;
            z-index: 1;
        }

        .footer-logo {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .footer-logo img {
            width: 60px;
            height: 60px;
            background: white;
            padding: 8px;
            border-radius: 15px;
        }

        .footer-logo h4 {
            font-family: 'Playfair Display', serif;
            font-size: 1.3rem;
            margin: 0;
            color: var(--accent);
        }

        .footer-info p {
            color: rgba(255,255,255,0.7);
            font-size: 0.9rem;
            line-height: 1.6;
        }

        .footer-credits {
            text-align: right;
        }

        .footer-credits p {
            color: var(--accent);
            font-weight: 700;
            font-size: 1.1rem;
            margin-bottom: 10px;
        }

        .footer-credits small {
            color: rgba(255,255,255,0.5);
            letter-spacing: 2px;
        }

        .copyright {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid rgba(255,255,255,0.1);
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
        }

        /* RESPONSIVE */
        @media (max-width: 992px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }

            .footer-content {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .footer-logo {
                justify-content: center;
            }

            .footer-credits {
                text-align: center;
            }
        }

        @media (max-width: 768px) {
            .header-wrapper {
                flex-direction: column;
                gap: 15px;
                padding: 15px;
            }

            .user-nav {
                width: 100%;
                justify-content: center;
            }

            .page-intro h1 {
                font-size: 2rem;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .admin-grid {
                grid-template-columns: 1fr;
            }
        }

        /* ANIMACIONES */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .admin-card {
            animation: float 6s ease-in-out infinite;
            animation-delay: calc(var(--card-index) * 0.2s);
        }

        .admin-card:nth-child(1) { --card-index: 1; }
        .admin-card:nth-child(2) { --card-index: 2; }
        .admin-card:nth-child(3) { --card-index: 3; }
        .admin-card:nth-child(4) { --card-index: 4; }
        .admin-card:nth-child(5) { --card-index: 5; }
        .admin-card:nth-child(6) { --card-index: 6; }
        .admin-card:nth-child(7) { --card-index: 7; }
    </style>
</head>
<body>

    <header class="admin-header" data-aos="fade-down">
        <div class="header-wrapper">
            <div class="brand-box">
                <div class="brand-logo">
                    <img src="../../assets/img/logo_callqui.png" alt="Logo Comunidad">
                </div>
                <div class="brand-text">
                    <h2>COMUNIDAD CAMPESINA CALLQUI CHICO</h2>
                    <span>✦ SECRETARÍA GENERAL ✦</span>
                </div>
            </div>
            <div class="user-nav">
                <div class="user-badge">
                    <i class="bi bi-shield-lock-fill"></i>
                    MÓDULO SECRETARIO
                </div>
                <a href="../../index.html" class="logout-link">
                    <i class="bi bi-door-open-fill"></i>
                    <span>CERRAR SESIÓN</span>
                </a>
            </div>
        </div>
    </header>

    <main class="admin-container">
        <div class="page-intro" data-aos="fade-right">
            <h1>Panel de Control Ejecutivo</h1>
            <p>Gestión estratégica y administrativa de la comunidad campesina</p>
        </div>

        <!-- Estadísticas Rápidas -->
        <div class="stats-row" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-info">
                    <h4>128</h4>
                    <p>Comuneros activos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-file-text-fill"></i>
                </div>
                <div class="stat-info">
                    <h4>47</h4>
                    <p>Actas registradas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-house-gear-fill"></i>
                </div>
                <div class="stat-info">
                    <h4>23</h4>
                    <p>Solicitudes activas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="stat-info">
                    <h4>86%</h4>
                    <p>Asistencia promedio</p>
                </div>
            </div>
        </div>

        <div class="admin-grid">
            <!-- Comuneros -->
            <a href="comuneros.php" class="admin-card" data-aos="flip-left" data-aos-delay="100">
                <div class="card-icon"><i class="bi bi-people-fill"></i></div>
                <h3>Padrón de Comuneros</h3>
                <p>Administración total de miembros: altas, bajas, ediciones y reportes detallados con historial completo.</p>
                <div class="card-footer">
                    <span>GESTIONAR PADRÓN</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>

            <!-- Actas -->
            <a href="subir_acta.php" class="admin-card" data-aos="flip-left" data-aos-delay="200">
                <div class="card-icon"><i class="bi bi-file-earmark-medical-fill"></i></div>
                <h3>Archivo de Actas</h3>
                <p>Registro formal de sesiones comunales y digitalización de libros de actas en formato PDF con respaldo.</p>
                <div class="card-footer">
                    <span>ADMINISTRAR ACTAS</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>

            <!-- Asistencias -->
            <a href="asistencia_secretario.php" class="admin-card" data-aos="flip-left" data-aos-delay="300">
                <div class="card-icon"><i class="bi bi-calendar2-check-fill"></i></div>
                <h3>Control de Asistencia</h3>
                <p>Supervisión de participación obligatoria en faenas, asambleas ordinarias y eventos comunales.</p>
                <div class="card-footer">
                    <span>CONTROLAR ASISTENCIA</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>

            <!-- Permisos -->
            <a href="permisos.php" class="admin-card" data-aos="flip-left" data-aos-delay="400">
                <div class="card-icon"><i class="bi bi-patch-check-fill"></i></div>
                <h3>Gestión de Permisos</h3>
                <p>Evaluación de solicitudes de ausencia, justificaciones oficiales y control de licencias.</p>
                <div class="card-footer">
                    <span>REVISAR PERMISOS</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>

            <!-- Adjudicaciones (Destacada) -->
            <a href="adjudicaciones_secretario.php" class="admin-card featured" data-aos="flip-left" data-aos-delay="500">
                <div class="featured-badge">✦ PRIORIDAD ✦</div>
                <div class="card-icon"><i class="bi bi-house-gear-fill"></i></div>
                <h3>Adjudicación de Predios</h3>
                <p>Trámite de expedientes de terrenos, adjudicaciones de propiedad comunal y catastro rural.</p>
                <div class="card-footer">
                    <span>GESTIONAR PREDIOS</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>

            <!-- Lotes -->
            <a href="../lotes/nuevo_lote.php" class="admin-card" data-aos="flip-left" data-aos-delay="600">
                <div class="card-icon"><i class="bi bi-map-fill"></i></div>
                <h3>Catastro de Lotes</h3>
                <p>Registro técnico de nuevos terrenos, áreas m², geolocalización y planos de predios.</p>
                <div class="card-footer">
                    <span>GENERAR CATASTRO</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>
            
            <!-- Documentos -->
            <a href="documentos.php" class="admin-card" data-aos="flip-left" data-aos-delay="700">
                <div class="card-icon"><i class="bi bi-printer-fill"></i></div>
                <h3>Formatos Oficiales</h3>
                <p>Generación automatizada de certificados, constancias, oficios y documentos legales.</p>
                <div class="card-footer">
                    <span>EMITIR DOCUMENTOS</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>
            
            <!-- Certificados Digitales -->
            <a href="../../perfil/gestionar_certificados.php" class="admin-card" data-aos="flip-left" data-aos-delay="800">
                <div class="card-icon"><i class="bi bi-shield-lock-fill"></i></div>
                <h3>Certificados Digitales</h3>
                <p>Gestión de certificados para firma digital de documentos oficiales con validez legal.</p>
                <div class="card-footer">
                    <span>CONFIGURAR FIRMAS</span>
                    <i class="bi bi-arrow-right"></i>
                </div>
            </a>
        </div>
    </div>
            </a>
        </div>

        <!-- Acciones Rápidas -->
        <div class="quick-actions" data-aos="fade-up" data-aos-delay="200">
            <h3>Acciones Rápidas</h3>
            <div class="actions-grid">
                <a href="nuevo_comunero.php" class="action-btn">
                    <i class="bi bi-plus-circle"></i>
                    <span>Nuevo Comunero</span>
                </a>
                <a href="subir_acta.php" class="action-btn">
                    <i class="bi bi-file-plus"></i>
                    <span>Subir Acta</span>
                </a>
                <a href="#" class="action-btn">
                    <i class="bi bi-qr-code"></i>
                    <span>Generar QR</span>
                </a>
                <a href="#" class="action-btn">
                    <i class="bi bi-printer"></i>
                    <span>Reporte Mensual</span>
                </a>
                <a href="#" class="action-btn">
                    <i class="bi bi-graph-up"></i>
                    <span>Estadísticas</span>
                </a>
            </div>
        </div>

        <!-- Botón de Sincronización -->
        <div class="btn-sync-container" data-aos="zoom-in" data-aos-delay="300">
            <a href="#" class="btn-sync">
                <i class="bi bi-arrow-repeat"></i>
                <span>SINCRONIZAR DATOS COMUNALES</span>
                <i class="bi bi-cloud-check"></i>
            </a>
        </div>
    </main>

    <footer class="admin-footer">
        <div class="footer-content">
            <div class="footer-logo">
                <img src="../../assets/img/logo_callqui.png" alt="Logo">
                <h4>Callqui Chico</h4>
            </div>
            <div class="footer-info">
                <p>Sistema de Gestión Administrativa Comunal</p>
                <p>Plataforma digital para la administración eficiente de la comunidad campesina</p>
            </div>
            <div class="footer-credits">
                <p>PROYECTO MADE DE:</p>
                <small>MAYCOL MATAMOROS & ANTONI QUINTO</small>
            </div>
        </div>
        <div class="copyright">
            <i class="bi bi-c-circle me-1"></i> 2026 Comunidad Campesina Callqui Chico - Todos los derechos reservados
        </div>
    </footer>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-out-cubic'
        });
    </script>
</body>
</html>

