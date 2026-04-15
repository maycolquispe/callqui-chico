<?php
require_once '../../includes/verificar_sesion.php';

$rol_actual = $_SESSION['rol'] ?? '';
if (!in_array($rol_actual, ['secretario', 'presidente'])) {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

$conn = getDB();

/* Mensajes */
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

/* Estadísticas */
$stats = $conn->query("SELECT 
    COUNT(CASE WHEN estado='Pendiente' THEN 1 END) as p,
    COUNT(CASE WHEN estado='Aprobado' THEN 1 END) as a,
    COUNT(CASE WHEN estado='Rechazado' THEN 1 END) as r 
    FROM permisos")->fetch_assoc();

/* Listado de permisos */
$permisos = $conn->query("SELECT p.*, u.nombres, u.apellidos, u.dni, u.foto, u.padron 
                          FROM permisos p 
                          JOIN usuarios u ON u.id = p.usuario_id 
                          ORDER BY u.padron ASC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Permisos | Callqui Chico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="../../assets/css/main.css" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0a2b3c;
            --primary-dark: #06212e;
            --accent: #c9a45c;
            --accent-light: #dbb67b;
            --bg-page: #f2efe6;
            --text-dark: #1e2b37;
            --text-light: #5a6b7a;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            background-color: var(--bg-page);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
        }

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

        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--accent);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h2 {
            color: white;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title h2 i { color: var(--accent); }

        .header-title p {
            color: rgba(255,255,255,0.7);
            margin: 0;
            font-size: 0.95rem;
        }

        .btn-back {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 10px 20px;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-back:hover {
            background: var(--accent);
            color: var(--primary-dark);
            transform: translateX(-5px);
        }

        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px 40px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(201, 164, 91, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
            border-color: var(--accent);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .stat-icon.pending { background: linear-gradient(135deg, #fef3c7, #fcd34d); color: #92400e; }
        .stat-icon.approved { background: linear-gradient(135deg, #dcfce7, #86efac); color: #166534; }
        .stat-icon.rejected { background: linear-gradient(135deg, #fee2e2, #fca5a5); color: #991b1b; }

        .stat-info h4 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
        }

        .stat-info p {
            color: var(--text-light);
            margin: 5px 0 0;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .card-admin {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }

        .card-header-custom {
            padding: 25px 30px;
            background: linear-gradient(135deg, #f8f9fa, white);
            border-bottom: 2px solid var(--accent);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header-custom h5 {
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-custom h5 i { color: var(--accent); font-size: 1.5rem; }

        .avatar-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), #1e4a6a);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .avatar-img {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 700;
            color: var(--primary);
        }

        .user-dni {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .badge-status {
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-pendiente {
            background: linear-gradient(135deg, #fef3c7, #fcd34d);
            color: #92400e;
        }

        .badge-aprobado {
            background: linear-gradient(135deg, #dcfce7, #86efac);
            color: #166534;
        }

        .badge-rechazado {
            background: linear-gradient(135deg, #fee2e2, #fca5a5);
            color: #991b1b;
        }

        .btn-action {
            padding: 8px 16px;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.85rem;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn-ver {
            background: #e0f2fe;
            color: #0284c7;
        }
        .btn-ver:hover { background: #0284c7; color: white; }

        .btn-aceptar {
            background: #dcfce7;
            color: #16a34a;
        }
        .btn-aceptar:hover { background: #16a34a; color: white; }

        .btn-rechazar {
            background: #fee2e2;
            color: #dc2626;
        }
        .btn-rechazar:hover { background: #dc2626; color: white; }

        .fechas-info {
            display: flex;
            flex-direction: column;
            font-size: 0.9rem;
        }

        .fecha-range { font-weight: 600; color: var(--primary); }
        .fecha-small { font-size: 0.8rem; color: var(--text-light); }

        .doc-preview {
            width: 100%;
            height: 500px;
            border: none;
            border-radius: 10px;
        }

        @media (max-width: 992px) {
            .stats-row { grid-template-columns: 1fr; }
            .header-container { flex-direction: column; gap: 15px; text-align: center; }
            .card-header-custom { flex-direction: column; gap: 15px; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <header class="page-header" data-aos="fade-down">
        <div class="header-container">
            <?php
$pagina_volver = ($rol_actual == 'presidente') ? '../presidente/presidente.php' : 'secretario.php';
?>

            <div class="header-title">
                <h2><i class="bi bi-file-earmark-check"></i> Gestión de Permisos</h2>
                <p><i class="bi bi-tree-fill me-1" style="color: var(--accent);"></i> Comunidad Campesina Callqui Chico</p>
            </div>
            <a href="<?= $pagina_volver ?>" class="btn-back">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </header>

    <div class="main-container">
        <!-- Stats -->
        <div class="stats-row" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-card">
                <div class="stat-icon pending"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-info">
                    <h4><?= $stats['p'] ?? 0 ?></h4>
                    <p>Pendientes</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon approved"><i class="bi bi-check-circle"></i></div>
                <div class="stat-info">
                    <h4><?= $stats['a'] ?? 0 ?></h4>
                    <p>Aprobados</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon rejected"><i class="bi bi-x-circle"></i></div>
                <div class="stat-info">
                    <h4><?= $stats['r'] ?? 0 ?></h4>
                    <p>Rechazados</p>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="card-admin" data-aos="fade-up" data-aos-delay="200">
            <div class="card-header-custom">
                <h5><i class="bi bi-list-ul"></i> Listado de Solicitudes de Permisos</h5>
            </div>
            
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Padrón</th>
                            <th>Comunero</th>
                            <th>Tipo de Permiso</th>
                            <th>Fechas</th>
                            <th>Documento</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($p = $permisos->fetch_assoc()): 
                            $filename = $p['archivo'];
                            $fileFound = false;
                            $fileUrl = '';
                            
                            // Buscar archivo en múltiples ubicaciones
                            $searchDirs = [
                                __DIR__ . '/../comunero/uploads/' => '../comunero/uploads/',
                                __DIR__ . '/../../storage/uploads/' => '../../storage/uploads/',
                                __DIR__ . '/../../uploads/' => '../../uploads/',
                                __DIR__ . '/../../publico/uploads/' => '../../publico/uploads/'
                            ];
                            
                            foreach($searchDirs as $dir => $url) {
                                if(!empty($filename) && file_exists($dir . $filename)) {
                                    $fileFound = true;
                                    $fileUrl = $url . $filename;
                                    break;
                                }
                            }
                            
                            $ext = !empty($filename) ? strtolower(pathinfo($filename, PATHINFO_EXTENSION)) : '';
                            
                            // Foto
                            $ruta_foto = '';
                            if (!empty($p['foto'])) {
                                if (file_exists(__DIR__ . '/../../perfil/uploads/' . $p['foto'])) {
                                    $ruta_foto = '../../perfil/uploads/' . $p['foto'];
                                } elseif (file_exists(__DIR__ . '/../../storage/uploads/' . $p['foto'])) {
                                    $ruta_foto = '../../storage/uploads/' . $p['foto'];
                                }
                            }
                        ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $p['padron'] ?? '-' ?></td>
                            <td>
                                <div class="avatar-wrapper">
                                    <?php if($ruta_foto): ?>
                                        <img src="<?= $ruta_foto ?>" class="avatar-img" alt="Avatar">
                                    <?php else: ?>
                                        <div class="avatar"><?= strtoupper(substr($p['nombres'],0,1).substr($p['apellidos'],0,1)) ?></div>
                                    <?php endif; ?>
                                    <div class="user-info">
                                        <span class="user-name"><?= htmlspecialchars($p['apellidos'].', '.$p['nombres']) ?></span>
                                        <span class="user-dni">DNI: <?= htmlspecialchars($p['dni']) ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?= htmlspecialchars($p['tipo_permiso']) ?></strong>
                                <div class="text-muted small"><?= substr(htmlspecialchars($p['motivo']), 0, 40) ?>...</div>
                            </td>
                            <td>
                                <div class="fechas-info">
                                    <span class="fecha-range"><?= date('d/m/Y', strtotime($p['fecha_inicio'])) ?></span>
                                    <span class="fecha-small">al <?= date('d/m/Y', strtotime($p['fecha_fin'])) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if(!empty($p['archivo']) && $fileFound): ?>
                                    <button class="btn-action btn-ver" data-bs-toggle="modal" data-bs-target="#verDoc<?= $p['id'] ?>">
                                        <i class="bi bi-eye"></i> Ver
                                    </button>
                                <?php else: ?>
                                    <span class="text-muted small">Sin documento</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $badge_class = $p['estado'] == 'Pendiente' ? 'badge-pendiente' : ($p['estado'] == 'Aprobado' ? 'badge-aprobado' : 'badge-rechazado');
                                $icon = $p['estado'] == 'Pendiente' ? 'hourglass-split' : ($p['estado'] == 'Aprobado' ? 'check-circle' : 'x-circle');
                                ?>
                                <span class="badge-status <?= $badge_class ?>">
                                    <i class="bi bi-<?= $icon ?>"></i> <?= $p['estado'] ?>
                                </span>
                            </td>
                            <td class="text-end">
                                <?php if($p['estado'] == 'Pendiente'): ?>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <button class="btn-action btn-aceptar" data-bs-toggle="modal" data-bs-target="#aprobar<?= $p['id'] ?>">
                                            <i class="bi bi-check-lg"></i> Aprobar
                                        </button>
                                        <button class="btn-action btn-rechazar" data-bs-toggle="modal" data-bs-target="#rechazar<?= $p['id'] ?>">
                                            <i class="bi bi-x-lg"></i> Rechazar
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">Procesado</span>
                                <?php endif; ?>
                            </td>
                        </tr>

                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modales Fuera de la Tabla -->
        <?php 
        $permisos->data_seek(0);
        while($p = $permisos->fetch_assoc()): 
            $filename = $p['archivo'];
            $fileFound = false;
            $fileUrl = '';
            
            $searchDirs = [
                __DIR__ . '/../comunero/uploads/' => '../comunero/uploads/',
                __DIR__ . '/../../storage/uploads/' => '../../storage/uploads/',
                __DIR__ . '/../../uploads/' => '../../uploads/',
                __DIR__ . '/../../publico/uploads/' => '../../publico/uploads/'
            ];
            
            foreach($searchDirs as $dir => $url) {
                if(!empty($filename) && file_exists($dir . $filename)) {
                    $fileFound = true;
                    $fileUrl = $url . $filename;
                    break;
                }
            }
            
            $ext = !empty($filename) ? strtolower(pathinfo($filename, PATHINFO_EXTENSION)) : '';
        ?>
        <!-- Modal Ver Documento -->
        <?php if(!empty($p['archivo']) && $fileFound): ?>
        <div class="modal fade" id="verDoc<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-file-earmark-pdf me-2"></i>
                            Documento de <?= htmlspecialchars($p['apellidos'].', '.$p['nombres']) ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <?php if($ext === 'pdf'): ?>
                            <iframe src="<?= $fileUrl ?>#toolbar=0&navpanes=0" class="doc-preview"></iframe>
                        <?php elseif(in_array($ext, ['jpg','jpeg','png','webp'])): ?>
                            <img src="<?= $fileUrl ?>" class="img-fluid w-100" style="max-height: 70vh; object-fit: contain;">
                        <?php else: ?>
                            <div class="text-center p-5">
                                <i class="bi bi-file-earmark" style="font-size: 4rem; color: var(--accent);"></i>
                                <p class="mt-3">No se puede previsualizar</p>
                                <a href="<?= $fileUrl ?>" class="btn btn-success" download>
                                    <i class="bi bi-download"></i> Descargar
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Modal Aprobar -->
        <div class="modal fade" id="aprobar<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-success">
                            <i class="bi bi-check-circle me-2"></i>Confirmar Aprobación
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="procesar_permiso.php">
                        <div class="modal-body">
                            <p>¿Está seguro de <strong>APROBAR</strong> la solicitud de permiso de:</p>
                            <p class="fw-bold fs-5"><?= htmlspecialchars($p['apellidos'].', '.$p['nombres']) ?></p>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="estado" value="Aprobado">
                            <div class="mb-3">
                                <label class="form-label">Observación (opcional)</label>
                                <textarea name="observacion" class="form-control" rows="3" placeholder="Agregar una observación..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg me-1"></i> Aprobar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Rechazar -->
        <div class="modal fade" id="rechazar<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-danger">
                            <i class="bi bi-x-circle me-2"></i>Confirmar Rechazo
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="procesar_permiso.php">
                        <div class="modal-body">
                            <p>¿Está seguro de <strong>RECHAZAR</strong> la solicitud de permiso de:</p>
                            <p class="fw-bold fs-5"><?= htmlspecialchars($p['apellidos'].', '.$p['nombres']) ?></p>
                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                            <input type="hidden" name="estado" value="Rechazado">
                            <div class="mb-3">
                                <label class="form-label">Motivo del rechazo <span class="text-danger">*</span></label>
                                <textarea name="observacion" class="form-control" rows="3" placeholder="Especifique el motivo del rechazo..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-danger">
                                <i class="bi bi-x-lg me-1"></i> Rechazar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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