<?php
require_once '../../includes/verificar_sesion.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;

$conn = getDB();
$stmt = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmt->bind_param("i",$usuario_id);
$stmt->execute();
$result = $stmt->get_result();
$usuario = $result->fetch_assoc();
$stmt->close();

$pageTitle = "Dashboard - Comunero";
$mostrarNavbar = true;
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comunero Profesional - Callqui Chico</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', 'Segoe UI', sans-serif;
      min-height: 100vh;
      background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
      position: relative;
      overflow-x: hidden;
    }

    /* Fondo con efecto de profundidad */
    body::before {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: radial-gradient(circle at 20% 30%, rgba(25, 85, 150, 0.15) 0%, transparent 50%),
                  radial-gradient(circle at 80% 70%, rgba(15, 65, 130, 0.15) 0%, transparent 50%);
      pointer-events: none;
      z-index: 0;
    }

    /* Partículas sutiles */
    body::after {
      content: "";
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" opacity="0.03"><circle cx="10" cy="10" r="1.5" fill="white"/><circle cx="30" cy="40" r="1" fill="white"/><circle cx="70" cy="20" r="1.2" fill="white"/><circle cx="90" cy="80" r="1" fill="white"/><circle cx="50" cy="90" r="1.5" fill="white"/><circle cx="20" cy="70" r="1" fill="white"/><circle cx="85" cy="35" r="1" fill="white"/><circle cx="15" cy="85" r="1.2" fill="white"/></svg>');
      background-size: 250px 250px;
      pointer-events: none;
      z-index: 0;
    }

    /* Header moderno con efecto vidrio oscuro */
    .navbar-modern {
      background: rgba(10, 25, 40, 0.85);
      backdrop-filter: blur(12px);
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
      padding: 1rem 2rem;
      position: sticky;
      top: 0;
      z-index: 1000;
      border-bottom: 1px solid rgba(52, 152, 219, 0.3);
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 1rem;
    }

    .logo-insignia {
      width: 50px;
      height: 50px;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.4);
      border: 2px solid rgba(52, 152, 219, 0.5);
      transition: transform 0.3s ease;
    }

    .logo-insignia:hover {
      transform: scale(1.05);
      border-color: #3498db;
    }

    .logo-insignia img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }

    .badge-comunidad {
      background: linear-gradient(145deg, #1e3a5f, #152b44);
      color: white;
      padding: 0.3rem 1rem;
      border-radius: 50px;
      font-size: 0.9rem;
      border: 1px solid #3498db;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    }

    .user-profile {
      display: flex;
      align-items: center;
      gap: 1.5rem;
    }

    .profile-link {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      background: linear-gradient(145deg, #1e3a5f, #152b44);
      color: white;
      padding: 0.5rem 1.2rem 0.5rem 0.5rem;
      border-radius: 50px;
      text-decoration: none;
      transition: all 0.3s ease;
      border: 1px solid #3498db;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    .profile-link:hover {
      transform: translateY(-2px);
      border-color: #5dade2;
      box-shadow: 0 6px 20px rgba(52, 152, 219, 0.3);
      color: white;
    }

    .profile-img {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #3498db;
    }

    .btn-logout {
      background: linear-gradient(145deg, #c0392b, #e74c3c);
      color: white;
      font-weight: 600;
      border-radius: 50px;
      padding: 0.6rem 1.5rem;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 0.5rem;
      transition: all 0.3s ease;
      border: 1px solid #e67e22;
      box-shadow: 0 4px 15px rgba(192, 57, 43, 0.3);
    }

    .btn-logout:hover {
      transform: translateY(-2px);
      background: linear-gradient(145deg, #a93226, #c0392b);
      border-color: #e74c3c;
      box-shadow: 0 6px 20px rgba(231, 76, 60, 0.4);
      color: white;
    }

    /* Contenido principal */
    .main-content {
      position: relative;
      z-index: 1;
      padding: 3rem 2rem;
    }

    .welcome-section {
      text-align: center;
      margin-bottom: 3rem;
    }

    .welcome-title {
      font-size: 2.5rem;
      font-weight: 700;
      color: white;
      text-shadow: 0 4px 15px rgba(0, 0, 0, 0.5);
      margin-bottom: 0.5rem;
    }

    .welcome-subtitle {
      font-size: 1.2rem;
      color: #aaccff;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
    }

    .comunidad-badge {
      display: inline-block;
      background: linear-gradient(145deg, #1e3a5f, #152b44);
      backdrop-filter: blur(5px);
      padding: 0.5rem 2rem;
      border-radius: 50px;
      color: white;
      border: 1px solid #3498db;
      margin-top: 1rem;
      font-size: 1.1rem;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    }

    /* Grid de cards */
    .cards-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 2rem;
      max-width: 1400px;
      margin: 0 auto;
    }

    .card-moderno {
      background: linear-gradient(145deg, #1a2f45, #14273a);
      backdrop-filter: blur(10px);
      border-radius: 24px;
      padding: 2rem 1.5rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
      transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
      border: 1px solid #2c3e50;
      position: relative;
      overflow: hidden;
      opacity: 0;
      transform: translateY(30px);
      animation: fadeInUp 0.6s forwards;
    }

    .card-moderno:nth-child(1) { animation-delay: 0.1s; }
    .card-moderno:nth-child(2) { animation-delay: 0.2s; }
    .card-moderno:nth-child(3) { animation-delay: 0.3s; }
    .card-moderno:nth-child(4) { animation-delay: 0.4s; }
    .card-moderno:nth-child(5) { animation-delay: 0.5s; }

    @keyframes fadeInUp {
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .card-moderno::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(52, 152, 219, 0.2) 0%, transparent 70%);
      opacity: 0;
      transition: opacity 0.3s ease;
      pointer-events: none;
    }

    .card-moderno:hover {
      transform: translateY(-10px) scale(1.02);
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.6);
      border-color: #3498db;
    }

    .card-moderno:hover::before {
      opacity: 1;
    }

    .card-moderno.destacado {
      background: linear-gradient(145deg, #1e3f5f, #1a334d);
      border: 2px solid #f39c12;
      position: relative;
    }

    .card-moderno.destacado::after {
      content: '🌟 DESTACADO';
      position: absolute;
      top: 1rem;
      right: 1rem;
      background: linear-gradient(145deg, #f39c12, #e67e22);
      color: white;
      font-size: 0.7rem;
      font-weight: 600;
      padding: 0.3rem 0.8rem;
      border-radius: 20px;
      letter-spacing: 1px;
      box-shadow: 0 2px 10px rgba(243, 156, 18, 0.3);
    }

    .card-icon {
      font-size: 3rem;
      margin-bottom: 1.5rem;
      color: #3498db;
      transition: all 0.3s ease;
      display: inline-block;
      text-shadow: 0 0 15px rgba(52, 152, 219, 0.5);
    }

    .card-moderno:hover .card-icon {
      transform: scale(1.1) rotate(5deg);
      color: #5dade2;
      text-shadow: 0 0 25px rgba(52, 152, 219, 0.8);
    }

    .card-moderno h3 {
      font-size: 1.5rem;
      font-weight: 700;
      color: white;
      margin-bottom: 0.75rem;
    }

    .card-moderno p {
      color: #b0c4de;
      margin-bottom: 1.5rem;
      line-height: 1.6;
    }

    .card-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: linear-gradient(145deg, #2980b9, #3498db);
      color: white;
      padding: 0.75rem 1.5rem;
      border-radius: 50px;
      text-decoration: none;
      font-weight: 600;
      transition: all 0.3s ease;
      border: none;
      cursor: pointer;
      border: 1px solid #5dade2;
      box-shadow: 0 4px 15px rgba(41, 128, 185, 0.3);
    }

    .card-btn:hover {
      transform: translateX(5px);
      background: linear-gradient(145deg, #2471a3, #2e86c1);
      box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
      color: white;
    }

    /* Footer */
    .watermark {
      text-align: center;
      margin-top: 4rem;
      color: rgba(176, 196, 222, 0.3);
      font-size: 0.9rem;
      position: relative;
      z-index: 1;
      padding: 1rem;
      border-top: 1px solid rgba(52, 152, 219, 0.2);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .navbar-modern {
        padding: 1rem;
      }

      .logo-container {
        gap: 0.5rem;
      }

      .profile-link span {
        display: none;
      }

      .welcome-title {
        font-size: 1.8rem;
      }

      .cards-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
      }
    }
  </style>
</head>

<body>

<!-- Header Moderno con azul oscuro -->
<nav class="navbar-modern">
  <div class="container-fluid d-flex justify-content-between align-items-center">
    <div class="logo-container">
      <div class="logo-insignia">
        <img src="../../assets/img/logo_callqui.png" alt="Logo Callqui Chico">
      </div>
      <span class="badge-comunidad d-none d-md-block">
        <i class="bi bi-shield-fill-check me-1"></i>Comunidad Campesina
      </span>
    </div>

    <div class="user-profile">
      <a href="../../perfil/perfil_ajax.php" class="profile-link">
        <img src="../../perfil/uploads/<?php echo !empty($usuario['foto']) ? $usuario['foto'].'?v='.time() : 'default.png'; ?>"
     class="profile-img" alt="Foto perfil">
        <span><?php echo htmlspecialchars($usuario['nombres'] ?? 'Comunero'); ?></span>
        <i class="bi bi-pencil-square"></i>
      </a>

      <a href="../../index.html" class="btn-logout">
        <i class="bi bi-box-arrow-right"></i>
        <span class="d-none d-md-inline">Cerrar Sesión</span>
      </a>
    </div>
  </div>
</nav>

<!-- Contenido Principal -->
<div class="main-content">
  <div class="welcome-section">
    <h1 class="welcome-title">
      <i class="bi bi-person-workspace me-2"></i>MÓDULO COMUNERO PROFESIONAL
    </h1>
    <p class="welcome-subtitle">Bienvenido, <?php echo htmlspecialchars($usuario['nombres'] ?? 'Comunero'); ?></p>
    <div class="comunidad-badge">
      <i class="bi bi-tree-fill me-2"></i>Comunidad Campesina Callqui Chico
      <i class="bi bi-droplet-half ms-2"></i>
    </div>
  </div>

  <div class="cards-grid">
    <!-- Mis Actas -->
    <div class="card-moderno">
      <div class="card-icon"><i class="bi bi-file-text-fill"></i></div>
      <h3>Mis Actas</h3>
      <p>Consulta y descarga todas las actas comunales en formato PDF o imagen.</p>
      <a href="actas.php" class="card-btn">
        <span>Ingresar</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <!-- Asistencias y Faltas -->
    <div class="card-moderno">
      <div class="card-icon"><i class="bi bi-calendar-check-fill"></i></div>
      <h3>Asistencias y Faltas</h3>
      <p>Revisa tu historial de participación y asistencia a las asambleas.</p>
      <a href="asistencia_comuneros.php" class="card-btn">
        <span>Ingresar</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <!-- Solicitud de Permisos -->
    <div class="card-moderno">
      <div class="card-icon"><i class="bi bi-envelope-paper-fill"></i></div>
      <h3>Solicitud de Permisos</h3>
      <p>Registra tus solicitudes de permiso y da seguimiento a su estado.</p>
      <a href="permisos.php" class="card-btn">
        <span>Solicitar</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <!-- Solicitud de Adjudicación (Destacado) -->
    <div class="card-moderno destacado">
      <div class="card-icon"><i class="bi bi-house-heart-fill"></i></div>
      <h3>Solicitud de Adjudicación</h3>
      <p>Inicia el trámite para la adjudicación de terrenos comunales.</p>
      <a href="adjudicaciones.php" class="card-btn">
        <span>Iniciar trámite</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <!-- Consultar Estado -->
    <div class="card-moderno">
      <div class="card-icon" style="background: rgba(16, 185, 129, 0.2); color: #10b981;">
        <i class="bi bi-search"></i>
      </div>
      <h3>Consultar Estado</h3>
      <p>Consulta el estado de tus solicitudes de permisos y adjudicaciones.</p>
      <a href="../../publico/tramite.php" class="card-btn">
        <span>Consultar</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </div>

    <!-- Mis lotes -->
    <div class="card-moderno">
      <div class="card-icon"><i class="bi bi-map-fill"></i></div>
      <h3>Mis lotes</h3>
      <p>Consulta el historial y estado de tus lotes asignados.</p>
      <a href="mis_lotes.php" class="card-btn">
        <span>Ingresar</span>
        <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>

  <div class="watermark">
    <i class="bi bi-calendar-event me-2"></i>Gestión 2025-2026
    <i class="bi bi-dot ms-2 me-2"></i>
    <i class="bi bi-shield-check"></i> Sistema de Gestión Comunal
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Animación adicional al hacer scroll
  document.addEventListener('DOMContentLoaded', () => {
    const cards = document.querySelectorAll('.card-moderno');
    
    const observer = new IntersectionObserver((entries) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.style.opacity = '1';
          entry.target.style.transform = 'translateY(0)';
        }
      });
    }, { threshold: 0.1 });

    cards.forEach(card => {
      card.style.opacity = '0';
      card.style.transform = 'translateY(30px)';
      observer.observe(card);
    });
  });
</script>

</body>
</html>