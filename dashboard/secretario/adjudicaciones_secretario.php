<?php
require_once "../../config/database.php";
require_once "../../includes/verificar_sesion.php";

$conn = getDB();
$mensaje = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['evaluar_id'])) {
    $id = intval($_POST['evaluar_id']);
    $estado = $_POST['estado'];
    $observaciones = $_POST['observaciones'];
    $aprobado_comite = isset($_POST['aprobado_comite']) ? 1 : 0;
    
    $stmt = $conn->prepare("UPDATE adjudicaciones SET estado=?, observaciones=?, aprobado_comite=? WHERE id=?");
    $stmt->bind_param("ssii", $estado, $observaciones, $aprobado_comite, $id);
    $stmt->execute();
    $stmt->close();
    $mensaje = "Registro #$id actualizado con éxito.";
}

$result = $conn->query("SELECT * FROM adjudicaciones ORDER BY fecha_solicitud DESC");
$solicitudes = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Adjudicaciones | Comunidad Callqui Chico</title>
    
    <!-- Bootstrap 5 & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #0a2b3c;
            --primary-dark: #06212e;
            --primary-light: #1e4a6a;
            --accent: #c9a45b;
            --accent-light: #dbb67b;
            --accent-dark: #a88642;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;
            --dark-bg: #0a1928;
            --card-bg: #ffffff;
            --text-dark: #1e293b;
            --text-light: #64748b;
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
            background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            position: relative;
        }

        /* Barra de navegación */
        .nav-bar {
            background: white;
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
            border-bottom: 3px solid var(--accent);
            margin-bottom: 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
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
            width: 45px;
            height: 45px;
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
        }

        .logo-text h3 {
            font-weight: 700;
            font-size: 1.2rem;
            margin: 0;
            color: var(--primary);
        }

        .logo-text small {
            color: var(--text-light);
            font-size: 0.75rem;
        }

        .btn-nav {
            background: var(--primary);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }

        .btn-nav:hover {
            background: var(--accent);
            color: var(--primary);
            transform: translateY(-2px);
        }

        /* Contenedor principal */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem 2rem;
        }

        /* Header de página */
        .page-header {
            margin-bottom: 2rem;
        }

        .page-header h1 {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .page-header h1 i {
            color: var(--accent);
        }

        .page-header p {
            color: var(--text-light);
            font-size: 1rem;
        }

        /* Alertas */
        .alert-custom {
            background: white;
            border-left: 4px solid var(--accent);
            border-radius: 10px;
            padding: 1rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            box-shadow: var(--shadow-sm);
        }

        /* Tarjetas de solicitud */
        .solicitud-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 1.8rem;
            margin-bottom: 1.8rem;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .solicitud-card:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent);
        }

        .solicitud-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .solicitud-numero {
            background: rgba(201,164,91,0.1);
            color: var(--accent-dark);
            padding: 0.4rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Badges de estado */
        .status-badge {
            padding: 0.5rem 1.2rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-pendiente {
            background: rgba(245,158,11,0.15);
            color: #f59e0b;
            border: 1px solid rgba(245,158,11,0.3);
        }

        .status-aprobado {
            background: rgba(16,185,129,0.15);
            color: #10b981;
            border: 1px solid rgba(16,185,129,0.3);
        }

        .status-rechazado {
            background: rgba(239,68,68,0.15);
            color: #ef4444;
            border: 1px solid rgba(239,68,68,0.3);
        }

        /* Información del solicitante */
        .info-label {
            color: var(--text-light);
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.3rem;
        }

        .info-value {
            font-weight: 600;
            color: var(--text-dark);
            font-size: 1rem;
        }

        .detalle-lote {
            background: #f8fafc;
            border-radius: 12px;
            padding: 1rem;
            margin: 1rem 0;
            border: 1px solid #e2e8f0;
        }

        /* Documentos */
        .docs-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.8rem;
            margin-top: 1rem;
        }

        .doc-item {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.8rem;
            width: 85px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .doc-item:hover {
            background: var(--accent);
            border-color: var(--accent);
            transform: translateY(-2px);
        }

        .doc-item:hover .doc-icon,
        .doc-item:hover .doc-label {
            color: white;
        }

        .doc-icon {
            font-size: 1.8rem;
            color: var(--accent);
            margin-bottom: 0.3rem;
            transition: color 0.3s ease;
        }

        .doc-label {
            font-size: 0.7rem;
            font-weight: 600;
            color: var(--text-dark);
            transition: color 0.3s ease;
        }

        /* Formulario de evaluación */
        .form-label-pro {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-light);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .form-select-pro, .form-control-pro {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            color: var(--text-dark);
            width: 100%;
            transition: all 0.3s ease;
        }

        .form-select-pro:focus, .form-control-pro:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(201,164,91,0.2);
            outline: none;
            background: white;
        }

        /* Switch personalizado */
        .form-check-input:checked {
            background-color: var(--accent);
            border-color: var(--accent);
        }

        .form-check-input:focus {
            box-shadow: 0 0 0 3px rgba(201,164,91,0.2);
            border-color: var(--accent);
        }

        /* Botones */
        .btn-save {
            background: var(--primary);
            color: white;
            border: none;
            padding: 0.7rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-save:hover {
            background: var(--accent);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .btn-certificado {
            background: #f8fafc;
            color: var(--primary);
            border: 1px solid #e2e8f0;
            padding: 0.7rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .btn-certificado:hover {
            background: var(--accent);
            color: white;
            border-color: var(--accent);
        }

        /* Modal */
        .modal-content-pro {
            background: white;
            border: none;
            border-radius: 20px;
        }

        .modal-header {
            border-bottom: 1px solid #e2e8f0;
            padding: 1.5rem;
        }

        .modal-title {
            color: var(--primary);
            font-weight: 700;
        }

        .btn-close {
            filter: none;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .solicitud-card {
                padding: 1.2rem;
            }

            .docs-grid {
                justify-content: center;
            }
        }
        
        /* Firmas Digitales */
        .firma-panel {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .firma-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .firma-item:last-child { border-bottom: none; }
        .firma-rol { font-weight: 500; color: white; }
        .firma-estado-firmado {
            background: rgba(16,185,129,0.2);
            color: #10b981;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .firma-estado-pendiente {
            background: rgba(255,193,7,0.2);
            color: #ffc107;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .firma-estado-bloqueado {
            background: rgba(107,114,128,0.2);
            color: #6b7280;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .btn-firmar {
            background: linear-gradient(135deg, #c9a45c, #a88642);
            color: #06212e;
            border: none;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 8px;
        }
        .btn-firmar:hover {
            background: linear-gradient(135deg, #d4b06a, #b8964e);
            color: #06212e;
        }
        .btn-firmar:disabled {
            background: rgba(107,114,128,0.3);
            color: #6b7280;
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
                    <h3>Callqui Chico</h3>
                    <small>Adjudicaciones</small>
                </div>
            </div>
            <a href="secretario.php" class="btn-nav">
                <i class="bi bi-grid"></i>
                <span>volver</span>
            </a>
        </div>
    </div>

    <div class="main-container">

        <!-- Header de página -->
        <div class="page-header">
            <h1>
                <i class="bi bi-house-gear-fill"></i>
                Gestión de Adjudicaciones
            </h1>
            <p>Revisa y evalúa las solicitudes de adjudicación de terrenos</p>
        </div>

        <!-- Mensaje de alerta -->
        <?php if($mensaje): ?>
            <div class="alert-custom">
                <i class="bi bi-check-circle-fill fs-4" style="color: var(--accent);"></i>
                <span><?= $mensaje ?></span>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Listado de solicitudes -->
        <?php foreach($solicitudes as $sol): ?>
            <div class="solicitud-card">
                
                <!-- Header de la solicitud -->
                <div class="solicitud-header">
                    <span class="solicitud-numero">
                        <i class="bi bi-hash"></i>
                        Solicitud #<?= $sol['id'] ?>
                    </span>
                    <span class="status-badge status-<?= $sol['estado'] ?>">
                        <i class="bi bi-<?= $sol['estado'] == 'aprobado' ? 'check-circle' : ($sol['estado'] == 'rechazado' ? 'x-circle' : 'hourglass-split') ?>"></i>
                        <?= strtoupper($sol['estado']) ?>
                    </span>
                </div>

                <div class="row g-4">
                    <!-- Columna 1: Información del solicitante -->
                    <div class="col-lg-4">
                        <div class="info-label">Solicitante</div>
                        <div class="info-value mb-3"><?= htmlspecialchars($sol['nombre'] ?? 'Sin nombre') ?></div>
                        
                        <div class="detalle-lote">
                            <div class="row g-2">
                                <div class="col-6">
                                    <div class="info-label">DNI</div>
                                    <div class="info-value"><?= htmlspecialchars($sol['dni']) ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Lote</div>
                                    <div class="info-value"><?= htmlspecialchars($sol['lote']) ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Manzana</div>
                                    <div class="info-value"><?= htmlspecialchars($sol['manzana']) ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Sector</div>
                                    <div class="info-value"><?= htmlspecialchars($sol['sector']) ?></div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Área</div>
                                    <div class="info-value"><?= number_format($sol['area_m2'], 2) ?> m²</div>
                                </div>
                                <div class="col-6">
                                    <div class="info-label">Expediente</div>
                                    <div class="info-value"><?= htmlspecialchars($sol['expediente'] ?: '-') ?></div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="info-label">Fecha de solicitud</div>
                            <div class="info-value">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('d/m/Y H:i', strtotime($sol['fecha_solicitud'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- Columna 2: Documentos - CORREGIDA -->
                    <div class="col-lg-4">
                        <div class="info-label mb-2">
                            <i class="bi bi-files"></i>
                            Documentos adjuntos
                        </div>
                        
                        <div class="docs-grid">
                            <?php 
                            $doc_fields = [
                                'archivo_dni' => 'DNI',
                                'archivo_constancia' => 'Constancia',
                                'archivo_plano' => 'Plano',
                                'archivo_recibo' => 'Recibo',
                                'archivo_memoria' => 'Memoria',
                                'archivo_jurada' => 'D. Jurada',
                                'archivo_contrato' => 'Contrato'
                            ];
                            
                            foreach($doc_fields as $campo => $nombre_doc):
                                if(empty($sol[$campo])) continue;
                                
                                $filename = basename($sol[$campo]);
                                $fileFound = false;
                                $url_final = '';
                                
                                $searchDir = __DIR__ . '/../../publico/uploads/';
                                if(file_exists($searchDir . $filename)) {
                                    $url_final = '../../publico/uploads/' . $filename;
                                    $fileFound = true;
                                }
                                
                                if($fileFound):
                            ?>
                            <div class="doc-item" onclick="showPDF('<?= $url_final ?>', '<?= $nombre_doc ?>')">
                                <i class="bi bi-file-earmark-text doc-icon"></i>
                                <span class="doc-label"><?= $nombre_doc ?></span>
                            </div>
                            <?php else: ?>
                            <div class="doc-item" onclick="alert('Documento no encontrado: <?= $filename ?>')" style="opacity: 0.5;">
                                <i class="bi bi-file-earmark-x doc-icon" style="color: #999;"></i>
                                <span class="doc-label" style="color: #999;"><?= $nombre_doc ?></span>
                            </div>
                            <?php endif; endforeach; ?>
                        </div>

                        <?php if($sol['observaciones']): ?>
                        <div class="mt-3 p-3 bg-light rounded-3">
                            <div class="info-label">Observaciones</div>
                            <p class="small mb-0"><?= nl2br(htmlspecialchars($sol['observaciones'])) ?></p>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Panel de Firmas Digitales -->
                        <div class="mt-4 p-3" style="background: rgba(0,0,0,0.1); border-radius: 12px;">
                            <h6 class="text-dark mb-3"><i class="bi bi-pen me-2"></i>Firmas Digitales</h6>
                            <div class="firma-panel" id="firmaStatus<?= $sol['id'] ?>">
                                <div class="text-center"><div class="spinner-border spinner-border-sm text-light" role="status"></div></div>
                            </div>
                            <div class="mt-3 text-center">
                                <button class="btn btn-firmar" id="btnFirmar<?= $sol['id'] ?>" onclick="firmarDocumento(<?= $sol['id'] ?>, 'adjudicacion')" style="display: none;">
                                    <i class="bi bi-pen-fill me-2"></i>Firmar Digitalmente
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Columna 3: Formulario de evaluación -->
                    <div class="col-lg-4">
                        <form method="post">
                            <input type="hidden" name="evaluar_id" value="<?= $sol['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label-pro">
                                    <i class="bi bi-tag"></i>
                                    Estado de la solicitud
                                </label>
                                <select name="estado" class="form-select-pro">
                                    <option value="pendiente" <?= $sol['estado']=='pendiente'?'selected':'' ?>>⏳ Pendiente</option>
                                    <option value="aprobado" <?= $sol['estado']=='aprobado'?'selected':'' ?>>✅ Aprobar</option>
                                    <option value="rechazado" <?= $sol['estado']=='rechazado'?'selected':'' ?>>❌ Rechazar</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label-pro">
                                    <i class="bi bi-chat-text"></i>
                                    Notas internas
                                </label>
                                <textarea name="observaciones" class="form-control-pro" rows="3" 
                                          placeholder="Añadir comentarios sobre la evaluación..."><?= htmlspecialchars($sol['observaciones'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3 d-flex align-items-center">
                                <div class="form-check form-switch">
                                    <input type="checkbox" 
                                           name="aprobado_comite" 
                                           class="form-check-input" 
                                           id="c<?= $sol['id'] ?>" 
                                           <?= $sol['aprobado_comite'] ? 'checked' : '' ?>>
                                    <label class="form-check-label small" for="c<?= $sol['id'] ?>">
                                        Aprobado por comité
                                    </label>
                                </div>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn-save flex-grow-1">
                                    <i class="bi bi-save me-1"></i>
                                    Guardar cambios
                                </button>
                                
                                <?php if($sol['estado'] == 'aprobado' && $sol['aprobado_comite']): ?>
                                    <a href="generar_certificado.php?id=<?= $sol['id'] ?>" 
                                       class="btn-certificado" 
                                       title="Generar certificado">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

    </div>

    <!-- Modal para visualizar documentos -->
    <div class="modal fade" id="pdfModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content modal-content-pro">
                <div class="modal-header">
                    <h5 class="modal-title" id="pdfTitle">Documento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0" style="height: 80vh;">
                    <iframe id="pdfFrame" src="" width="100%" height="100%" style="border: none; border-radius: 0 0 20px 20px;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Inicializar modal
        const modal = new bootstrap.Modal(document.getElementById('pdfModal'));

        // Función para mostrar documentos
        function showPDF(url, title) {
            document.getElementById('pdfFrame').src = url;
            document.getElementById('pdfTitle').innerText = 'Visualizando: ' + title;
            modal.show();
        }

        // Auto-cerrar alerta después de 5 segundos
        setTimeout(() => {
            document.querySelectorAll('.alert-custom').forEach(alert => {
                alert.style.transition = 'opacity 0.5s ease';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);
        
        // Cargar estado de firmas al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            cargarEstadosFirma();
        });
        
        // Obtener rol del usuario desde PHP
        const miRol = '<?php echo $_SESSION["rol"] ?? ""; ?>';
        
        function cargarEstadosFirma() {
            const ids = [<?php echo implode(',', array_column($solicitudes, 'id')); ?>];
            ids.forEach(function(id) {
                cargarEstadoFirma(id, 'adjudicacion');
            });
        }
        
        function cargarEstadoFirma(idSolicitud, tipoDocumento) {
            fetch('../../api/estado_firmas.php?id_solicitud=' + idSolicitud + '&tipo_documento=' + tipoDocumento, {
                credentials: 'include'
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    renderFirmaStatus(idSolicitud, data.data);
                } else {
                    document.getElementById('firmaStatus' + idSolicitud).innerHTML = 
                        '<div class="text-danger text-center"><i class="bi bi-exclamation-triangle"></i> ' + (data.message || 'Error al cargar') + '</div>';
                }
            })
            .catch(e => {
                console.error('Error:', e);
                document.getElementById('firmaStatus' + idSolicitud).innerHTML = 
                    '<div class="text-danger text-center"><i class="bi bi-exclamation-triangle"></i> Error de conexión</div>';
            });
        }
        
        function renderFirmaStatus(idSolicitud, data) {
            const container = document.getElementById('firmaStatus' + idSolicitud);
            const btnFirmar = document.getElementById('btnFirmar' + idSolicitud);
            
            let html = '';
            Object.entries(data.firmas).forEach(([rol, info]) => {
                let estadoClass = '';
                let estadoText = '';
                let icon = '';
                
                if (info.firmado) {
                    estadoClass = 'firma-estado-firmado';
                    estadoText = 'Firmado';
                    icon = '<i class="bi bi-check-circle-fill me-1"></i>';
                } else if (info.es_su_turno && info.puede_firmar) {
                    estadoClass = 'firma-estado-pendiente';
                    estadoText = 'Pendiente - Tu turno';
                    icon = '<i class="bi bi-clock me-1"></i>';
                } else if (!info.puede_firmar) {
                    estadoClass = 'firma-estado-bloqueado';
                    estadoText = 'Sin certificado';
                    icon = '<i class="bi bi-x-circle me-1"></i>';
                } else {
                    estadoClass = 'firma-estado-bloqueado';
                    estadoText = 'Esperando turno';
                    icon = '<i class="bi bi-hourglass-split me-1"></i>';
                }
                
                html += '<div class="firma-item">';
                html += '<span class="firma-rol">' + capitalize(rol) + '</span>';
                html += '<span class="' + estadoClass + '">' + icon + estadoText + '</span>';
                html += '</div>';
            });
            
            container.innerHTML = html || '<div class="text-muted text-center"><i class="bi bi-inbox"></i> Sin firmas registradas</div>';
            
            // Mostrar botón de firmar si es mi turno
            const miInfo = data.firmas[miRol];
            if (miInfo && miInfo.es_su_turno && miInfo.puede_firmar && btnFirmar) {
                btnFirmar.style.display = 'inline-block';
            } else if (btnFirmar) {
                btnFirmar.style.display = 'none';
            }
        }
        
        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
        
        function firmarDocumento(idSolicitud, tipoDocumento) {
            if (!confirm('¿Está seguro de firmar digitalmente este documento?\n\nEsta acción no se puede deshacer.')) {
                return;
            }
            
            const btn = document.getElementById('btnFirmar' + idSolicitud);
            const originalText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Firmando...';
            
            fetch('../../api/firmar_documento.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                credentials: 'include',
                body: JSON.stringify({
                    id_solicitud: idSolicitud,
                    tipo_documento: tipoDocumento
                })
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    alert('Documento firmado exitosamente!\n\nFirmante: ' + data.data.firmante);
                    cargarEstadoFirma(idSolicitud, tipoDocumento);
                } else {
                    alert('Error: ' + data.message);
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(e => {
                console.error('Error:', e);
                alert('Error al procesar la firma digital');
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        }
    </script>

</body>
</html>