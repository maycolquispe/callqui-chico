<?php
require_once "../../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===============================
   SEGURIDAD
================================ */
if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['comunero','secretario','presidente'])) {
    header("Location: ../login.php");
    exit;
}

/* ===============================
   CONSULTA ACTAS
================================ */
$sql = "SELECT a.*, u.nombres, u.apellidos
        FROM actas a
        LEFT JOIN usuarios u ON a.creado_por = u.id
        ORDER BY a.fecha_registro DESC";

$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Actas Comunales - Callqui Chico</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    :root {
        --primary: #2563eb;
        --primary-dark: #1e40af;
        --primary-light: #60a5fa;
        --secondary: #10b981;
        --danger: #ef4444;
        --warning: #f59e0b;
        --dark-bg: #0a1928;
        --dark-card: #0f2740;
        --text-light: #f0f5fa;
        --text-muted: #94a3b8;
    }

    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: 'Inter', sans-serif;
        background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
        min-height: 100vh;
        color: var(--text-light);
        position: relative;
    }

    /* Efecto de fondo */
    body::before {
        content: "";
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.1) 0%, transparent 50%),
                    radial-gradient(circle at 80% 70%, rgba(16,185,129,0.1) 0%, transparent 50%);
        pointer-events: none;
    }

    /* Barra de navegación */
    .nav-bar {
        background: rgba(10, 25, 40, 0.95);
        backdrop-filter: blur(12px);
        padding: 1rem 0;
        box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid rgba(37,99,235,0.3);
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
        gap: 1rem;
    }

    .logo {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #2563eb, #1e40af);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.5rem;
        font-weight: 800;
        box-shadow: 0 4px 15px rgba(37,99,235,0.3);
    }

    .logo-text h3 {
        color: white;
        font-weight: 700;
        font-size: 1.3rem;
        margin: 0;
        line-height: 1.2;
    }

    .logo-text small {
        color: #94a3b8;
        font-size: 0.8rem;
    }

    .nav-actions {
        display: flex;
        gap: 1rem;
    }

    .btn-nav {
        background: rgba(255,255,255,0.05);
        color: white;
        padding: 0.6rem 1.5rem;
        border-radius: 50px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .btn-nav:hover {
        background: #2563eb;
        color: white;
        transform: translateY(-2px);
    }

    /* Contenedor principal */
    .main-container {
        max-width: 1400px;
        margin: 2rem auto;
        padding: 0 2rem;
        position: relative;
        z-index: 1;
    }

    /* Panel principal */
    .panel {
        background: rgba(15, 39, 64, 0.7);
        backdrop-filter: blur(12px);
        border-radius: 32px;
        padding: 2rem;
        border: 1px solid rgba(255,255,255,0.1);
        box-shadow: 0 25px 50px rgba(0,0,0,0.5);
    }

    /* Header del panel */
    .panel-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
        padding-bottom: 1.5rem;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }

    .header-title h2 {
        color: white;
        font-weight: 700;
        font-size: 2rem;
        margin-bottom: 0.3rem;
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .header-title p {
        color: #94a3b8;
        margin: 0;
        font-size: 0.95rem;
    }

    .btn-back {
        background: rgba(255,255,255,0.05);
        color: white;
        padding: 0.8rem 1.8rem;
        border-radius: 50px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .btn-back:hover {
        background: #2563eb;
        color: white;
        transform: translateX(-5px);
    }

    /* Estadísticas */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2rem;
    }

    .stat-card {
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(12px);
        border-radius: 20px;
        padding: 1.2rem;
        border: 1px solid rgba(255,255,255,0.1);
        display: flex;
        align-items: center;
        gap: 1rem;
    }

    .stat-icon {
        width: 50px;
        height: 50px;
        background: linear-gradient(135deg, #2563eb, #1e40af);
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        color: white;
    }

    .stat-info h4 {
        color: white;
        font-weight: 700;
        margin: 0;
        font-size: 1.3rem;
    }

    .stat-info p {
        color: #94a3b8;
        margin: 0;
        font-size: 0.85rem;
    }

    /* Grid de actas */
    .actas-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
        gap: 1.5rem;
        margin-top: 1.5rem;
    }

    /* Card de acta */
    .acta-card {
        background: rgba(255,255,255,0.05);
        backdrop-filter: blur(12px);
        border-radius: 24px;
        border: 1px solid rgba(255,255,255,0.1);
        overflow: hidden;
        transition: all 0.3s ease;
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .acta-card:hover {
        transform: translateY(-5px);
        border-color: #2563eb;
        box-shadow: 0 20px 40px rgba(37,99,235,0.2);
    }

    /* Badge de tipo */
    .tipo-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        z-index: 10;
        padding: 0.4rem 1rem;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        backdrop-filter: blur(4px);
    }

    .tipo-pdf {
        background: rgba(239,68,68,0.2);
        color: #ef4444;
        border: 1px solid rgba(239,68,68,0.3);
    }

    .tipo-imagen {
        background: rgba(37,99,235,0.2);
        color: #60a5fa;
        border: 1px solid rgba(37,99,235,0.3);
    }

    /* Preview */
    .acta-preview {
        height: 200px;
        background: rgba(0,0,0,0.3);
        position: relative;
        overflow: hidden;
    }

    .preview-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(to top, rgba(0,0,0,0.5), transparent);
        opacity: 0;
        transition: opacity 0.3s ease;
        display: flex;
        align-items: flex-end;
        padding: 1rem;
    }

    .acta-card:hover .preview-overlay {
        opacity: 1;
    }

    .preview-icon {
        color: white;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.9rem;
    }

    .preview-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .preview-iframe {
        width: 100%;
        height: 100%;
        border: none;
        background: #1e293b;
    }

    /* Contenido del acta */
    .acta-content {
        padding: 1.5rem;
        flex: 1;
    }

    .acta-titulo {
        font-size: 1.1rem;
        font-weight: 700;
        color: white;
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }

    .acta-descripcion {
        color: #94a3b8;
        font-size: 0.9rem;
        margin-bottom: 1rem;
        line-height: 1.5;
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    /* Metadatos */
    .acta-metadata {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        margin-bottom: 1rem;
    }

    .metadata-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        color: #94a3b8;
        font-size: 0.85rem;
    }

    .metadata-item i {
        color: #2563eb;
        width: 16px;
    }

    .metadata-item strong {
        color: white;
        font-weight: 600;
    }

    /* Footer de la card */
    .acta-footer {
        padding: 1rem 1.5rem 1.5rem;
        border-top: 1px solid rgba(255,255,255,0.1);
    }

    .btn-ver {
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 0.8rem;
        width: 100%;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .btn-ver:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(37,99,235,0.4);
        color: white;
    }

    /* Botón flotante para subir actas */
    .fab-container {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 1000;
    }

    .fab-button {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background: linear-gradient(135deg, #2563eb, #1e40af);
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        box-shadow: 0 10px 30px rgba(37,99,235,0.4);
        border: 2px solid rgba(255,255,255,0.2);
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .fab-button:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 15px 40px rgba(37,99,235,0.6);
        color: white;
    }

    .fab-tooltip {
        position: absolute;
        right: 80px;
        top: 50%;
        transform: translateY(-50%);
        background: rgba(255,255,255,0.9);
        color: #0a1928;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 600;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }

    .fab-container:hover .fab-tooltip {
        opacity: 1;
        right: 90px;
    }

    /* Estado vacío */
    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: rgba(255,255,255,0.05);
        border-radius: 24px;
        border: 1px solid rgba(255,255,255,0.1);
    }

    .empty-icon {
        font-size: 5rem;
        color: #2d4a6e;
        margin-bottom: 1.5rem;
    }

    .empty-title {
        color: white;
        font-weight: 600;
        font-size: 1.3rem;
        margin-bottom: 0.5rem;
    }

    .empty-text {
        color: #64748b;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-container {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .panel-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }

        .header-title h2 {
            font-size: 1.5rem;
        }

        .actas-grid {
            grid-template-columns: 1fr;
        }

        .fab-button {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
        }
    }
</style>
</head>

<body>

<!-- Barra de navegación -->
<div class="nav-bar">
    <div class="nav-container">
        <div class="logo-area">
            <div class="logo">
                <i class="bi bi-tree-fill"></i>
            </div>
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Sistema de Gestión Comunal</small>
            </div>
        </div>
        <div class="nav-actions">
            <a href="../perfil_ajax.php" class="btn-nav">
                <i class="bi bi-person-circle"></i>
                <span class="d-none d-md-inline">Mi Perfil</span>
            </a>
            <a href="../login.php" class="btn-nav">
                <i class="bi bi-box-arrow-right"></i>
                <span class="d-none d-md-inline">Salir</span>
            </a>
        </div>
    </div>
</div>

<!-- Contenedor principal -->
<div class="main-container">

    <!-- Panel principal -->
    <div class="panel">

        <!-- Header del panel -->
        <div class="panel-header">
            <div class="header-title">
                <h2>
                    <i class="bi bi-file-earmark-text-fill"></i>
                    Actas Comunales
                </h2>
                <p>Documentos oficiales de la comunidad</p>
            </div>
            <a href="secretario.php" class="btn-back">
                <i class="bi bi-arrow-left"></i>
                <span>Volver</span>
            </a>
        </div>

        <!-- Estadísticas rápidas -->
        <?php 
        $total_actas = $result ? $result->num_rows : 0;
        $pdf_count = 0;
        $img_count = 0;
        if ($result) {
            $result->data_seek(0);
            while($a = $result->fetch_assoc()) {
                $ext = strtolower(pathinfo($a['archivo'], PATHINFO_EXTENSION));
                if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                    $img_count++;
                } elseif ($ext === 'pdf') {
                    $pdf_count++;
                }
            }
            $result->data_seek(0);
        }
        ?>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-file-earmark-text"></i>
                </div>
                <div class="stat-info">
                    <h4><?= $total_actas ?></h4>
                    <p>Total Actas</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-file-pdf"></i>
                </div>
                <div class="stat-info">
                    <h4><?= $pdf_count ?></h4>
                    <p>Documentos PDF</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-image"></i>
                </div>
                <div class="stat-info">
                    <h4><?= $img_count ?></h4>
                    <p>Imágenes</p>
                </div>
            </div>
        </div>

        <!-- Listado de actas -->
        <?php if ($result && $result->num_rows > 0): ?>
            <div class="actas-grid">
                <?php while($a = $result->fetch_assoc()): ?>

                <?php
                $archivo = $a['archivo'];
                $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
                $esImagen = in_array($ext, ['jpg','jpeg','png','webp']);
                $esPdf = ($ext === 'pdf');
                $tipo_class = $esPdf ? 'tipo-pdf' : 'tipo-imagen';
                $tipo_texto = $esPdf ? 'PDF' : strtoupper($ext);
                ?>

                <div class="acta-card">
                    <!-- Badge de tipo -->
                    <span class="tipo-badge <?= $tipo_class ?>">
                        <i class="bi bi-<?= $esPdf ? 'file-pdf' : 'file-image' ?> me-1"></i>
                        <?= $tipo_texto ?>
                    </span>

                    <!-- Preview -->
                    <div class="acta-preview">
                        <?php if ($esPdf): ?>
                            <iframe src="../uploads/actas/<?= htmlspecialchars($archivo) ?>#toolbar=0&navpanes=0&scrollbar=0" class="preview-iframe"></iframe>
                        <?php else: ?>
                            <img src="../uploads/actas/<?= htmlspecialchars($archivo) ?>" class="preview-img" alt="Preview">
                        <?php endif; ?>
                        <div class="preview-overlay">
                            <span class="preview-icon">
                                <i class="bi bi-eye-fill"></i>
                                Vista previa
                            </span>
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="acta-content">
                        <h6 class="acta-titulo"><?= htmlspecialchars($a['titulo']) ?></h6>
                        <p class="acta-descripcion"><?= nl2br(htmlspecialchars($a['descripcion'])) ?></p>

                        <div class="acta-metadata">
                            <div class="metadata-item">
                                <i class="bi bi-calendar-event"></i>
                                <span>Fecha del acta: <strong><?= date("d/m/Y", strtotime($a['fecha'])) ?></strong></span>
                            </div>
                            <div class="metadata-item">
                                <i class="bi bi-clock"></i>
                                <span>Registro: <strong><?= date("d/m/Y H:i", strtotime($a['fecha_registro'])) ?></strong></span>
                            </div>
                            <div class="metadata-item">
                                <i class="bi bi-person-circle"></i>
                                <span>Subido por: <strong><?= $a['nombres'] ? htmlspecialchars($a['nombres']." ".$a['apellidos']) : 'Sistema Comunal' ?></strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer con botón -->
                    <div class="acta-footer">
                        <a href="../uploads/actas/<?= htmlspecialchars($archivo) ?>" target="_blank" class="btn-ver">
                            <i class="bi bi-eye-fill"></i>
                            Ver Documento
                            <i class="bi bi-box-arrow-up-right ms-auto"></i>
                        </a>
                    </div>
                </div>

                <?php endwhile; ?>
            </div>
        <?php else: ?>

            <!-- Estado vacío -->
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-file-earmark-x"></i>
                </div>
                <h5 class="empty-title">No hay actas registradas</h5>
                <p class="empty-text">Aún no se han subido documentos al sistema.</p>
            </div>

        <?php endif; ?>

    </div> <!-- Fin panel -->

</div> <!-- Fin main-container -->

<!-- Botón flotante para secretarios -->
<?php if ($_SESSION['rol'] == 'secretario'): ?>
<div class="fab-container">
    <a href="../secretario/subir_acta.php" class="fab-button">
        <i class="bi bi-plus-lg"></i>
    </a>
    <span class="fab-tooltip">Subir nueva acta</span>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>