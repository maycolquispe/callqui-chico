<?php
/**
 * Gestión de Certificados Digitales
 * Comunidad Campesina Callqui Chico
 * 
 * Página para que el administrador suba certificados .p12/.pfx
 */

session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit;
}

$usuario_id = $_SESSION['usuario_id'];
$rol = $_SESSION['rol'];

// Solo roles autorizados pueden gestionar certificados
$roles_autorizados = ['secretario', 'presidente', 'comite_lotes', 'tesorero'];
if (!in_array($rol, $roles_autorizados)) {
    die("No tienes permiso para acceder a esta página");
}

$conn = getDB();
$mensaje = '';
$tipo_mensaje = '';

// Procesar upload de certificado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_certificado'])) {
    $target_usuario_id = intval($_POST['usuario_id']);
    $password_certificado = $_POST['password_certificado'] ?? '';
    
    if (empty($target_usuario_id)) {
        $mensaje = "Seleccione un usuario";
        $tipo_mensaje = "danger";
    } elseif (empty($password_certificado)) {
        $mensaje = "Ingrese el password del certificado";
        $tipo_mensaje = "danger";
    } elseif (!isset($_FILES['certificado']) || $_FILES['certificado']['error'] !== UPLOAD_ERR_OK) {
        $mensaje = "Seleccione un archivo de certificado (.p12 o .pfx)";
        $tipo_mensaje = "danger";
    } else {
        $file = $_FILES['certificado'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($ext, ['p12', 'pfx'])) {
            $mensaje = "El archivo debe ser .p12 o .pfx";
            $tipo_mensaje = "danger";
        } else {
            // Verificar que el usuario existe y tiene rol válido
            $stmt = $conn->prepare("SELECT id, rol, nombres, apellidos FROM usuarios WHERE id = ? AND rol IN ('secretario', 'fiscal', 'tesorero', 'presidente')");
            $stmt->bind_param("i", $target_usuario_id);
            $stmt->execute();
            $target_user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            
            if (!$target_user) {
                $mensaje = "Usuario no válido o no tiene rol para firmar";
                $tipo_mensaje = "danger";
            } else {
                // Crear directorio si no existe
                $cert_dir = '../certificados';
                if (!is_dir($cert_dir)) {
                    mkdir($cert_dir, 0777, true);
                }
                
                // Guardar certificado
                $filename = "cert_{$target_usuario_id}_{$target_user['rol']}_" . date('YmdHis') . ".{$ext}";
                $filepath = $cert_dir . '/' . $filename;
                
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Actualizar en BD
                    $stmt = $conn->prepare("UPDATE usuarios SET certificado_digital = ?, password_certificado = ? WHERE id = ?");
                    $ruta_db = 'certificados/' . $filename;
                    $stmt->bind_param("ssi", $ruta_db, $password_certificado, $target_usuario_id);
                    
                    if ($stmt->execute()) {
                        $mensaje = "Certificado guardado para {$target_user['nombres']} {$target_user['apellidos']} ({$target_user['rol']})";
                        $tipo_mensaje = "success";
                    } else {
                        $mensaje = "Error al guardar en base de datos";
                        $tipo_mensaje = "danger";
                    }
                    $stmt->close();
                } else {
                    $mensaje = "Error al mover el archivo";
                    $tipo_mensaje = "danger";
                }
            }
        }
    }
}

// Obtener usuarios con roles de firma
$stmt = $conn->prepare("SELECT id, dni, nombres, apellidos, rol, certificado_digital, password_certificado FROM usuarios WHERE rol IN ('secretario', 'fiscal', 'tesorero', 'presidente', 'comite_lotes') ORDER BY rol, nombres");
$stmt->execute();
$usuarios_firma = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Procesar upload de firma visual
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_firma_visual'])) {
    $firma_rol = $_POST['firma_rol'] ?? '';
    
    if (isset($_FILES['firma_imagen']) && !empty($_FILES['firma_imagen']['name'])) {
        $allowed_types = ['image/png', 'image/jpeg', 'image/gif'];
        if (in_array($_FILES['firma_imagen']['type'], $allowed_types)) {
            $upload_dir = __DIR__ . '/../publico/uploads/firmas/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $archivo = $firma_rol . '_firma.' . pathinfo($_FILES['firma_imagen']['name'], PATHINFO_EXTENSION);
            $ruta = $upload_dir . $archivo;
            
            if (move_uploaded_file($_FILES['firma_imagen']['tmp_name'], $ruta)) {
                $ruta_db = 'uploads/firmas/' . $archivo;
                
                $stmt = $conn->prepare("UPDATE config_firmas_visual SET firma_imagen = ?, actualizado_por = ?, fecha_actualizacion = NOW() WHERE rol = ?");
                $stmt->bind_param("sis", $ruta_db, $usuario_id, $firma_rol);
                
                if ($stmt->execute()) {
                    $mensaje = "Firma visual actualizada correctamente";
                    $tipo_mensaje = "success";
                } else {
                    $mensaje = "Error al guardar firma: " . $conn->error;
                    $tipo_mensaje = "danger";
                }
                $stmt->close();
            }
        } else {
            $mensaje = "El archivo debe ser PNG, JPG o GIF";
            $tipo_mensaje = "danger";
        }
    }
}

// Obtener firmas visuales actuales
$firmas_visuales = [];
$res = $conn->query("SELECT rol, firma_imagen FROM config_firmas_visual");
while ($row = $res->fetch_assoc()) {
    $firmas_visuales[$row['rol']] = $row['firma_imagen'];
}

// Obtener datos del usuario actual
$stmt = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();
$stmt->close();
$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Certificados y Firmas - Callqui Chico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark-bg: #0a1928; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
            min-height: 100vh;
        }
        body::before {
            content: ""; position: fixed; inset: 0;
            background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.1) 0%, transparent 50%);
            pointer-events: none; z-index: 0;
        }
        
        .navbar-modern {
            background: rgba(10, 25, 40, 0.95);
            backdrop-filter: blur(12px);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 1000;
            border-bottom: 1px solid rgba(201, 164, 92, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-area { display: flex; align-items: center; gap: 1rem; }
        .logo {
            width: 45px; height: 45px;
            background: linear-gradient(135deg, #c9a45c, #a88642);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #06212e;
            font-size: 1.3rem;
            font-weight: 800;
        }
        .logo-text h3 { color: white; font-weight: 700; font-size: 1.1rem; margin: 0; }
        .logo-text small { color: #dbb67b; font-size: 0.75rem; }
        
        .main-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .page-header {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .page-header h2 { color: white; font-weight: 700; margin-bottom: 0.5rem; }
        .page-header p { color: #94a3b8; margin: 0; }
        
        .card-cert {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .badge-rol {
            background: linear-gradient(135deg, #c9a45c, #a88642);
            color: #06212e;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .badge-tiene {
            background: rgba(16,185,129,0.2);
            color: #10b981;
        }
        .badge-no-tiene {
            background: rgba(239,68,68,0.2);
            color: #ef4444;
        }
        
        .btn-subir {
            background: linear-gradient(135deg, #c9a45c, #a88642);
            color: #06212e;
            border: none;
            font-weight: 600;
        }
        .btn-subir:hover {
            background: linear-gradient(135deg, #d4b06a, #b8964e);
            color: #06212e;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 1rem;
        }
        .back-btn:hover { color: white; }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <div class="logo"><i class="bi bi-tree-fill"></i></div>
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small><?= ucfirst($rol) ?></small>
            </div>
        </div>
        <a href="../logout.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </nav>

    <div class="main-container">
        
        <a href="../dashboard/<?= $rol ?>/<?= $rol ?>.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
        
        <div class="page-header">
            <h2><i class="bi bi-shield-lock me-2"></i>Gestión de Certificados Digitales</h2>
            <p>Suba certificados digitales (.p12/.pfx) para usuarios autorizados a firmar documentos</p>
        </div>
        
        <?php if ($mensaje): ?>
        <div class="alert alert-<?= $tipo_mensaje ?> bg-transparent border-<?= $tipo_mensaje ?> text-<?= $tipo_mensaje ?>">
            <?= $mensaje ?>
        </div>
        <?php endif; ?>
        
        <!-- Formulario para subir certificado -->
        <div class="card-cert mb-4">
            <h5 class="text-white mb-3"><i class="bi bi-upload me-2"></i>Subir Nuevo Certificado</h5>
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Usuario</label>
                        <select name="usuario_id" class="form-select bg-dark text-light border-secondary" required>
                            <option value="">Seleccionar usuario...</option>
                            <?php foreach ($usuarios_firma as $u): ?>
                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']) ?> (<?= ucfirst($u['rol']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Archivo Certificado (.p12/.pfx)</label>
                        <input type="file" name="certificado" class="form-control bg-dark text-light border-secondary" accept=".p12,.pfx" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Password del Certificado</label>
                        <input type="password" name="password_certificado" class="form-control bg-dark text-light border-secondary" placeholder="Password del archivo .p12" required>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <button type="submit" name="subir_certificado" class="btn btn-subir w-100">
                            <i class="bi bi-cloud-upload me-2"></i>Subir Certificado
                        </button>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Sección de Firmas Visuales -->
        <div class="card-cert">
            <h5 class="text-white mb-3"><i class="bi bi-pen me-2"></i>Firmas Visuales para Certificados</h5>
            <p class="text-white-50 mb-3">Suba la imagen de su firma manuscrita (PNG transparente recommended)</p>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Mi Rol</label>
                        <select name="firma_rol" class="form-select bg-dark text-light border-secondary" required>
                            <option value="">Seleccionar...</option>
                            <option value="tesorero" <?= $rol === 'tesorero' ? 'selected' : '' ?>>Tesorero</option>
                            <option value="comite_lotes" <?= $rol === 'comite_lotes' ? 'selected' : '' ?>>Comité de Lotes</option>
                            <option value="secretario" <?= $rol === 'secretario' ? 'selected' : '' ?>>Secretario</option>
                            <option value="presidente" <?= $rol === 'presidente' ? 'selected' : '' ?>>Presidente</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-light">Imagen de Firma (PNG)</label>
                        <input type="file" name="firma_imagen" class="form-control bg-dark text-light border-secondary" accept="image/png,image/jpeg" required>
                    </div>
                    <div class="col-md-6 mb-3 d-flex align-items-end">
                        <button type="submit" name="subir_firma_visual" class="btn btn-subir w-100">
                            <i class="bi bi-cloud-upload me-2"></i>Subir Firma Visual
                        </button>
                    </div>
                </div>
            </form>
            
            <!-- Firmas actuales -->
            <h6 class="text-white mt-4 mb-2">Firmas configuradas:</h6>
            <div class="row">
                <?php foreach (['tesorero', 'comite_lotes', 'secretario', 'presidente'] as $r): ?>
                <div class="col-md-3 mb-3 text-center">
                    <div class="p-2 bg-dark rounded">
                        <small class="text-white d-block"><?= ucfirst(str_replace('_', ' ', $r)) ?></small>
                        <?php if (!empty($firmas_visuales[$r])): ?>
                            <img src="../<?= $firmas_visuales[$r] ?>" style="max-width: 100px; max-height: 40px;" class="mt-2">
                        <?php else: ?>
                            <span class="text-muted d-block mt-2">Sin firma</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Lista de usuarios con certificados -->
        <div class="card-cert">
            <h5 class="text-white mb-3"><i class="bi bi-people me-2"></i>Usuarios con Roles de Firma</h5>
            <div class="table-responsive">
                <table class="table table-dark table-hover">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Certificado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios_firma as $u): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($u['nombres'] . ' ' . $u['apellidos']) ?><br>
                                <small class="text-muted">DNI: <?= htmlspecialchars($u['dni']) ?></small>
                            </td>
                            <td><span class="badge-rol"><?= ucfirst($u['rol']) ?></span></td>
                            <td>
                                <?php if (!empty($u['certificado_digital'])): ?>
                                <span class="badge badge-tiene"><i class="bi bi-check-circle me-1"></i>Configurado</span>
                                <?php else: ?>
                                <span class="badge badge-no-tiene"><i class="bi bi-x-circle me-1"></i>Sin certificado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($u['certificado_digital'])): ?>
                                <button class="btn btn-sm btn-outline-danger" onclick="eliminarCertificado(<?= $u['id'] ?>)">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function eliminarCertificado(usuarioId) {
        if (!confirm('¿Está seguro de eliminar el certificado digital de este usuario?')) {
            return;
        }
        
        fetch('eliminar_certificado.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({usuario_id: usuarioId})
        })
        .then(r => r.json())
        .then(data => {
            alert(data.message);
            if (data.success) {
                location.reload();
            }
        })
        .catch(e => {
            console.error(e);
            alert('Error al eliminar certificado');
        });
    }
    </script>
</body>
</html>