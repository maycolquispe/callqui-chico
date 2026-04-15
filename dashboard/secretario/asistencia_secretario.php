<?php
session_start();

require_once "../../config/conexion.php";

$acta_id = null;

if(isset($_POST['guardar'])){

    $titulo = $_POST['titulo'];
    $fecha = $_POST['fecha'];
    $creado_por = $_SESSION['usuario_id'];

    $stmt = $conn->prepare("INSERT INTO actas(titulo,fecha,creado_por) VALUES(?,?,?)");
    $stmt->bind_param("ssi", $titulo, $fecha, $creado_por);
    $stmt->execute();

    $acta_id = $stmt->insert_id;

    foreach($_POST['estado'] as $usuario_id => $estado){

        $check = $conn->prepare("
        SELECT id FROM asistencias
        WHERE usuario_id=? AND acta_id=?
        ");

        $check->bind_param("ii",$usuario_id,$acta_id);
        $check->execute();
        $res = $check->get_result();

        if($res->num_rows > 0){

            $update = $conn->prepare("
            UPDATE asistencias
            SET estado=?
            WHERE usuario_id=? AND acta_id=?
            ");

            $update->bind_param("sii",$estado,$usuario_id,$acta_id);
            $update->execute();

        }else{

            $insert = $conn->prepare("
            INSERT INTO asistencias(usuario_id,acta_id,estado)
            VALUES(?,?,?)
            ");

            $insert->bind_param("iis",$usuario_id,$acta_id,$estado);
            $insert->execute();
        }
    }

    echo "<script>alert('Acta y asistencia guardadas correctamente');</script>";
}

// Traer comuneros con DNI agregado
$comuneros = $conn->query("
SELECT id,nombres,apellidos,dni,padron
FROM usuarios
WHERE rol='comunero'
AND estado='activo'
ORDER BY padron ASC
");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Control de Asistencia | Comunidad Callqui Chico</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark-bg: #0a1928;
            --dark-card: #0f2740;
            --text-light: #f0f5fa;
            --text-muted: #94a3b8;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 100%);
            min-height: 100vh;
            color: var(--text-light);
            position: relative;
            overflow-x: hidden;
        }

        /* Efecto de fondo */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 30%, rgba(201,164,91,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(16,185,129,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Partículas animadas */
        .particle {
            position: fixed;
            width: 3px;
            height: 3px;
            background: rgba(201, 164, 91, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float-particle 15s infinite linear;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }

        /* Contenedor principal */
        .main-container {
            max-width: 1400px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 10;
        }

        /* Header de navegación */
        .nav-bar {
            background: rgba(10, 25, 40, 0.95);
            backdrop-filter: blur(12px);
            padding: 1rem 0;
            box-shadow: var(--shadow-xl);
            border-bottom: 3px solid var(--accent);
            margin-bottom: 2rem;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-area {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .logo {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-dark);
            font-size: 1.8rem;
            font-weight: 800;
            box-shadow: 0 4px 15px rgba(201, 164, 91, 0.3);
        }

        .logo-text h3 {
            color: white;
            font-weight: 700;
            font-size: 1.4rem;
            margin: 0;
            line-height: 1.2;
        }

        .logo-text small {
            color: var(--accent-light);
            font-size: 0.8rem;
            letter-spacing: 1px;
        }

        .nav-actions {
            display: flex;
            gap: 1rem;
        }

        .btn-nav {
            background: rgba(255,255,255,0.05);
            color: white;
            padding: 0.7rem 1.8rem;
            border-radius: 50px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.7rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
            font-weight: 500;
        }

        .btn-nav:hover {
            background: var(--accent);
            color: var(--primary-dark);
            transform: translateY(-2px);
            border-color: var(--accent);
        }

        /* Tarjeta principal */
        .asistencia-card {
            background: rgba(15, 39, 64, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            animation: slideUp 0.6s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 1.5rem 2rem;
            border-bottom: 3px solid var(--accent);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .logo-app {
            height: 60px;
            background: white;
            padding: 8px;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
        }

        .btn-volver {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-volver:hover {
            background: var(--accent);
            color: var(--primary-dark);
            transform: translateX(-5px);
        }

        .btn-pdf {
            background: var(--danger);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-pdf:hover {
            background: #b91c1c;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(220,38,38,0.3);
        }

        .card-body {
            padding: 2rem;
        }

        /* Título */
        .page-title {
            text-align: center;
            margin-bottom: 2rem;
        }

        .page-title h2 {
            font-size: 2.2rem;
            font-weight: 800;
            color: white;
            margin-bottom: 0.5rem;
        }

        .page-title p {
            color: var(--text-muted);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        /* Formulario del acta */
        .form-acta {
            background: rgba(255,255,255,0.03);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .form-label {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-label i {
            color: var(--accent);
        }

        .form-control {
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 0.8rem 1.2rem;
            color: white;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(201,164,91,0.2);
            outline: none;
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        /* Buscador mejorado */
        .search-section {
            background: rgba(255,255,255,0.03);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .search-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 10;
        }

        .input-buscar {
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1rem 1rem 1rem 3rem;
            color: white;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .input-buscar:focus {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(201,164,91,0.2);
            outline: none;
        }

        .input-buscar::placeholder {
            color: var(--text-muted);
        }

        .search-stats {
            margin-top: 0.8rem;
            color: var(--text-muted);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .search-stats span {
            color: var(--accent);
            font-weight: 600;
        }

        /* Tabla mejorada */
        .table-responsive {
            border-radius: 20px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .table {
            margin: 0;
        }

        .table thead th {
            background: var(--primary);
            color: white;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 1.2rem 1rem;
            border: none;
        }

        .table tbody td {
            padding: 1rem;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid rgba(255,255,255,0.05);
            color: white;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: rgba(255,255,255,0.05);
        }

        .table tbody tr.highlight {
            background: rgba(201,164,91,0.1);
            border-left: 4px solid var(--accent);
        }

        /* Información del comunero */
        .comunero-info {
            display: flex;
            flex-direction: column;
        }

        .comunero-nombre {
            font-weight: 600;
            color: white;
            margin-bottom: 0.2rem;
        }

        .comunero-dni {
            font-size: 0.8rem;
            color: var(--text-muted);
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .comunero-dni i {
            color: var(--accent);
        }

        /* Botones de estado mejorados */
        .grupo-botones {
            display: flex;
            gap: 0.5rem;
        }

        .grupo-botones input {
            display: none;
        }

        .btn-estado {
            padding: 0.6rem 1rem;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s ease;
            border: 2px solid transparent;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .btn-asistio {
            background: rgba(16,185,129,0.15);
            color: #10b981;
            border-color: rgba(16,185,129,0.3);
        }

        .btn-falto {
            background: rgba(239,68,68,0.15);
            color: #ef4444;
            border-color: rgba(239,68,68,0.3);
        }

        .btn-justificado {
            background: rgba(245,158,11,0.15);
            color: #f59e0b;
            border-color: rgba(245,158,11,0.3);
        }

        .btn-estado:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .grupo-botones input:checked + .btn-estado {
            background: linear-gradient(135deg, var(--success), #0ea371);
            color: white;
            border-color: var(--success);
            box-shadow: 0 5px 15px rgba(16,185,129,0.3);
        }

        .grupo-botones input:checked + .btn-falto {
            background: linear-gradient(135deg, var(--danger), #b91c1c);
            color: white;
            border-color: var(--danger);
            box-shadow: 0 5px 15px rgba(239,68,68,0.3);
        }

        .grupo-botones input:checked + .btn-justificado {
            background: linear-gradient(135deg, var(--warning), #d97706);
            color: white;
            border-color: var(--warning);
            box-shadow: 0 5px 15px rgba(245,158,11,0.3);
        }

        /* Botón guardar mejorado */
        .btn-guardar {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: var(--primary-dark);
            border: none;
            padding: 1.2rem;
            border-radius: 16px;
            font-weight: 800;
            font-size: 1.2rem;
            letter-spacing: 1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            transition: all 0.3s ease;
            box-shadow: 0 10px 25px rgba(201,164,91,0.3);
            width: 100%;
            margin-top: 1.5rem;
        }

        .btn-guardar:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(201,164,91,0.4);
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
        }

        /* Botón flotante para volver al último */
        .btn-flotante {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            background: var(--accent);
            color: var(--primary-dark);
            border: none;
            border-radius: 60px;
            padding: 1rem 2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            box-shadow: var(--shadow-xl);
            border: 2px solid rgba(255,255,255,0.2);
            transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            z-index: 1000;
            animation: pulse 2s infinite;
        }

        .btn-flotante:hover {
            transform: scale(1.05);
            background: white;
            color: var(--primary-dark);
        }

        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(201, 164, 91, 0.7);
            }
            70% {
                box-shadow: 0 0 0 20px rgba(201, 164, 91, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(201, 164, 91, 0);
            }
        }

        /* Contador de seleccionados */
        .selected-counter {
            background: rgba(255,255,255,0.05);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid rgba(255,255,255,0.1);
        }

        .counter-badge {
            background: var(--accent);
            color: var(--primary-dark);
            padding: 0.3rem 1rem;
            border-radius: 30px;
            font-weight: 700;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .card-header-custom {
                flex-direction: column;
                gap: 1rem;
            }

            .header-left {
                flex-direction: column;
            }

            .grupo-botones {
                flex-direction: column;
            }

            .btn-flotante {
                bottom: 1rem;
                right: 1rem;
                padding: 0.8rem 1.5rem;
            }
        }

        /* Animaciones */
        .fila-comunero {
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateX(-10px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
</head>
<body>

    <!-- Partículas animadas -->
    <div id="particles"></div>

    <!-- Barra de navegación -->
    <div class="nav-bar">
        <div class="nav-container">
            <div class="logo-area">
                <div class="logo">
                    <i class="bi bi-tree-fill"></i>
                </div>
                <div class="logo-text">
                    <h3>Comunidad Callqui Chico</h3>
                    <small>Control de Asistencia</small>
                </div>
            </div>
            <div class="nav-actions">
                <a href="../perfil_ajax.php" class="btn-nav">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-md-inline">Mi Perfil</span>
                </a>
                <a href="secretario.php" class="btn-nav">
                    <i class="bi bi-grid"></i>
                    <span class="d-none d-md-inline">VOLVER</span>
                </a>
            </div>
        </div>
    </div>

    <div class="main-container">

        <!-- Tarjeta principal -->
        <div class="asistencia-card">

            <div class="card-header-custom">
                

                <?php if($acta_id){ ?>
                <a href="reporte_asistencias.php?acta_id=<?= $acta_id ?>&pdf=1" target="_blank" class="btn-pdf">
                    <i class="bi bi-file-pdf"></i>
                    Descargar PDF
                </a>
                <?php } ?>
            </div>

            <div class="card-body">

                <div class="page-title">
                    <h2>
                        <i class="bi bi-calendar-check-fill me-2" style="color: var(--accent);"></i>
                        Registro de Asistencia
                    </h2>
                    <p>
                        <i class="bi bi-people-fill"></i>
                        Control de participación en reuniones comunales
                    </p>
                </div>

                <form method="POST">

                    <!-- Datos del acta -->
                    <div class="form-acta">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-file-text"></i>
                                    Descripción del Acta
                                </label>
                                <input type="text" name="titulo" class="form-control" 
                                       placeholder="Ej: Asamblea General Ordinaria" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">
                                    <i class="bi bi-calendar"></i>
                                    Fecha
                                </label>
                                <input type="date" name="fecha" class="form-control" 
                                       value="<?= date('Y-m-d') ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Buscador mejorado -->
                    <div class="search-section">
                        <div class="search-wrapper">
                            <i class="bi bi-search search-icon"></i>
                            <input type="text" id="inputBuscar" class="input-buscar" 
                                   placeholder="Buscar por nombre, apellido o DNI...">
                        </div>
                        <div class="search-stats">
                            <i class="bi bi-people"></i>
                            <span id="totalComuneros"><?= $comuneros->num_rows ?></span> comuneros registrados
                            <i class="bi bi-dot ms-2"></i>
                            <span id="visibles"><?= $comuneros->num_rows ?></span> visibles
                        </div>
                    </div>

                    <!-- Tabla de comuneros -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th width="80">Padrón</th>
                                    <th>Comunero</th>
                                    <th width="350">Estado de Asistencia</th>
                                </tr>
                            </thead>
                            <tbody id="tablaComuneros">
                                <?php while($c=$comuneros->fetch_assoc()){ ?>
                                <tr class="fila-comunero" data-id="<?= $c['id'] ?>">
                                    <td class="text-center fw-bold"><?= $c['padron'] ?? '-' ?></td>
                                    <td>
                                        <div class="comunero-info">
                                            <span class="comunero-nombre"><?= htmlspecialchars($c['nombres']." ".$c['apellidos']) ?></span>
                                            <span class="comunero-dni">
                                                <i class="bi bi-person-badge"></i>
                                                DNI: <?= htmlspecialchars($c['dni']) ?>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="grupo-botones">
                                            <input type="radio" id="asistio<?= $c['id'] ?>" 
                                                   name="estado[<?= $c['id'] ?>]" value="asistio" checked>
                                            <label class="btn-estado btn-asistio" for="asistio<?= $c['id'] ?>">
                                                <i class="bi bi-check-circle"></i> Asistió
                                            </label>

                                            <input type="radio" id="falto<?= $c['id'] ?>" 
                                                   name="estado[<?= $c['id'] ?>]" value="falto">
                                            <label class="btn-estado btn-falto" for="falto<?= $c['id'] ?>">
                                                <i class="bi bi-x-circle"></i> Faltó
                                            </label>

                                            <input type="radio" id="justificado<?= $c['id'] ?>" 
                                                   name="estado[<?= $c['id'] ?>]" value="justificado">
                                            <label class="btn-estado btn-justificado" for="justificado<?= $c['id'] ?>">
                                                <i class="bi bi-exclamation-circle"></i> Justificado
                                            </label>
                                        </div>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Contador de seleccionados -->
                    <div class="selected-counter">
                        <div>
                            <i class="bi bi-check-circle-fill text-success"></i>
                            <span id="asistioCount">0</span> Asistieron
                            <i class="bi bi-x-circle-fill text-danger ms-3"></i>
                            <span id="faltoCount">0</span> Faltaron
                            <i class="bi bi-exclamation-circle-fill text-warning ms-3"></i>
                            <span id="justificadoCount">0</span> Justificados
                        </div>
                        <span class="counter-badge" id="totalSeleccionados">
                            <?= $comuneros->num_rows ?> seleccionados
                        </span>
                    </div>

                    <!-- Botón guardar -->
                    <button name="guardar" class="btn-guardar">
                        <i class="bi bi-save"></i>
                        GUARDAR REGISTRO DE ASISTENCIA
                        <i class="bi bi-arrow-right"></i>
                    </button>

                </form>

            </div>

        </div>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Inicializar AOS
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-out-cubic'
        });

        // Crear partículas
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = Math.random() * 10 + 10 + 's';
                particlesContainer.appendChild(particle);
            }
        }
        createParticles();

        // Buscador mejorado
        const inputBuscar = document.getElementById("inputBuscar");
        const filas = document.querySelectorAll(".fila-comunero");
        const totalSpan = document.getElementById("visibles");
        const totalOriginal = filas.length;

        function actualizarContadores() {
            // Contar filas visibles
            let visibles = 0;
            filas.forEach(f => {
                if (f.style.display !== 'none') visibles++;
            });
            totalSpan.textContent = visibles;

            // Actualizar contadores de selección
            let asistio = 0, falto = 0, justificado = 0;
            document.querySelectorAll('.grupo-botones input:checked').forEach(r => {
                if (r.value === 'asistio') asistio++;
                else if (r.value === 'falto') falto++;
                else if (r.value === 'justificado') justificado++;
            });
            
            document.getElementById('asistioCount').textContent = asistio;
            document.getElementById('faltoCount').textContent = falto;
            document.getElementById('justificadoCount').textContent = justificado;
            document.getElementById('totalSeleccionados').textContent = 
                (asistio + falto + justificado) + ' seleccionados';
        }

        inputBuscar.addEventListener("input", function() {
            const valor = this.value.toLowerCase().trim();

            filas.forEach(function(fila) {
                const nombre = fila.querySelector(".comunero-nombre").textContent.toLowerCase();
                const dni = fila.querySelector(".comunero-dni").textContent.toLowerCase();

                if (nombre.includes(valor) || dni.includes(valor)) {
                    fila.style.display = "";
                } else {
                    fila.style.display = "none";
                }
            });

            actualizarContadores();
        });

        // Actualizar contadores cuando cambie una selección
        document.querySelectorAll('.grupo-botones input').forEach(radio => {
            radio.addEventListener('change', actualizarContadores);
        });

        // Inicializar contadores
        actualizarContadores();

        // Función mejorada para auto-avance
        let ultimaFila = null;
        let botonFlotante = null;

        document.querySelectorAll(".grupo-botones input").forEach(function(radio) {
            radio.addEventListener("click", function() {
                const fila = this.closest("tr");
                ultimaFila = fila;

                // Resaltar fila actual
                filas.forEach(f => f.classList.remove('highlight'));
                fila.classList.add('highlight');

                // Ocultar fila actual
                fila.style.display = "none";

                // Buscar siguiente fila visible
                let siguiente = fila.nextElementSibling;
                while(siguiente && (siguiente.style.display === "none" || siguiente.classList.contains('d-none'))) {
                    siguiente = siguiente.nextElementSibling;
                }

                // Si no hay siguiente, buscar desde el principio
                if (!siguiente) {
                    siguiente = document.querySelector('.fila-comunero:not([style*="display: none"])');
                }

                if (siguiente) {
                    siguiente.scrollIntoView({
                        behavior: "smooth",
                        block: "center"
                    });
                }

                // Crear botón flotante si no existe
                if (!botonFlotante) {
                    botonFlotante = document.createElement("button");
                    botonFlotante.innerHTML = '<i class="bi bi-arrow-return-left"></i> Volver al último';
                    botonFlotante.className = "btn-flotante";
                    botonFlotante.onclick = function() {
                        if (ultimaFila) {
                            ultimaFila.style.display = "";
                            ultimaFila.classList.remove('highlight');
                            ultimaFila.scrollIntoView({
                                behavior: "smooth",
                                block: "center"
                            });
                        }
                    };
                    document.body.appendChild(botonFlotante);
                }

                actualizarContadores();
            });
        });

        // Validación antes de enviar
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!confirm('¿Está seguro de guardar el registro de asistencia?')) {
                e.preventDefault();
            }
        });

        // Atajo de teclado: Ctrl+F para enfocar buscador
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                inputBuscar.focus();
            }
        });
    </script>

</body>
</html>