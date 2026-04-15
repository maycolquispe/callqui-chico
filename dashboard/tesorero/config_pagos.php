<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($_SESSION['rol'] !== 'tesorero') {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

$msg = "";
$msg_type = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre_banco = $_POST['nombre_banco'] ?? '';
    $cuenta_banco = $_POST['cuenta_banco'] ?? '';
    $titular_cuenta = $_POST['titular_cuenta'] ?? '';
    $numero_yape = $_POST['numero_yape'] ?? '';
    $instrucciones = $_POST['instrucciones'] ?? '';
    
    $qr_yape = null;
    if (isset($_FILES['qr_yape']) && !empty($_FILES['qr_yape']['name'])) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($_FILES['qr_yape']['type'], $allowed_types)) {
            $upload_dir = __DIR__ . '/../../publico/uploads/qr/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $qr_yape = time() . '_qr_yape.' . pathinfo($_FILES['qr_yape']['name'], PATHINFO_EXTENSION);
            move_uploaded_file($_FILES['qr_yape']['tmp_name'], $upload_dir . $qr_yape);
        }
    }
    
    if ($qr_yape) {
        $stmt = $conn->prepare("UPDATE config_pagos SET nombre_banco = ?, cuenta_banco = ?, titular_cuenta = ?, numero_yape = ?, qr_yape = ?, instrucciones = ?, actualizado_por = ?, fecha_actualizacion = NOW() WHERE id = 1");
        $stmt->bind_param("ssssssi", $nombre_banco, $cuenta_banco, $titular_cuenta, $numero_yape, $qr_yape, $instrucciones, $usuario_id);
    } else {
        $stmt = $conn->prepare("UPDATE config_pagos SET nombre_banco = ?, cuenta_banco = ?, titular_cuenta = ?, numero_yape = ?, instrucciones = ?, actualizado_por = ?, fecha_actualizacion = NOW() WHERE id = 1");
        $stmt->bind_param("sssssi", $nombre_banco, $cuenta_banco, $titular_cuenta, $numero_yape, $instrucciones, $usuario_id);
    }
    
    if ($stmt->execute()) {
        $msg = "Datos de pago actualizados correctamente";
        $msg_type = "success";
    } else {
        $msg = "Error al actualizar: " . $conn->error;
        $msg_type = "danger";
    }
    $stmt->close();
}

$config = $conn->query("SELECT * FROM config_pagos WHERE id = 1")->fetch_assoc();

$stmtUser = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();
$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurar Pagos - Tesorero</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #2563eb; --dark-bg: #0a1928; --accent: #c9a45c; }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%); min-height: 100vh; }
        
        .navbar-modern { background: rgba(10, 25, 40, 0.95); backdrop-filter: blur(12px); padding: 1rem 2rem; position: sticky; top: 0; z-index: 1000; border-bottom: 1px solid rgba(201, 164, 92, 0.3); display: flex; justify-content: space-between; align-items: center; }
        .logo-area { display: flex; align-items: center; gap: 1rem; }
        .logo { width: 45px; height: 45px; background: linear-gradient(135deg, #c9a45c, #a88642); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: #06212e; font-size: 1.3rem; font-weight: 800; }
        .logo-text h3 { color: white; font-weight: 700; font-size: 1.1rem; margin: 0; }
        .logo-text small { color: #dbb67b; font-size: 0.75rem; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { text-align: right; }
        .user-info div { color: white; font-weight: 500; }
        .user-info small { color: #94a3b8; }
        .user-avatar { width: 45px; height: 45px; background: linear-gradient(135deg, #10b981, #059669); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; }
        
        .main-container { max-width: 900px; margin: 2rem auto; padding: 0 1.5rem; }
        
        .page-header { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .page-header h2 { color: white; font-weight: 700; margin-bottom: 0.5rem; }
        .page-header p { color: #94a3b8; margin: 0; }
        
        .form-card { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 20px; padding: 2rem; }
        .form-card label { color: #94a3b8; font-weight: 500; }
        .form-card .form-control, .form-card .form-select { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.2); color: white; }
        .form-card .form-control:focus, .form-card .form-select:focus { background: rgba(255,255,255,0.1); border-color: #c9a45c; color: white; }
        
        .file-upload { border: 2px dashed rgba(255,255,255,0.2); border-radius: 12px; padding: 2rem; text-align: center; cursor: pointer; transition: all 0.3s; }
        .file-upload:hover { border-color: #c9a45c; }
        .file-upload input { display: none; }
        .file-upload i { font-size: 2rem; color: #c9a45c; margin-bottom: 0.5rem; }
        
        .qr-preview { max-width: 200px; border-radius: 12px; margin-top: 1rem; }
        
        .datos-actuales { background: rgba(16,185,129,0.1); border: 1px solid rgba(16,185,129,0.3); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .datos-actuales h5 { color: #10b981; margin-bottom: 1rem; }
        .dato-item { display: flex; justify-content: space-between; padding: 0.5rem 0; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .dato-item:last-child { border-bottom: none; }
        .dato-label { color: #94a3b8; }
        .dato-valor { color: white; font-weight: 500; }
        
        .btn-guardar { background: linear-gradient(135deg, #c9a45c, #a88642); color: #06212e; border: none; font-weight: 600; padding: 0.75rem 2rem; border-radius: 8px; }
        .btn-guardar:hover { background: linear-gradient(135deg, #d4b06a, #b8964e); }
        
        .back-btn { display: inline-flex; align-items: center; gap: 0.5rem; color: #94a3b8; text-decoration: none; margin-bottom: 1rem; }
        .back-btn:hover { color: white; }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <div class="logo"><i class="bi bi-tree-fill"></i></div>
            <div class="logo-text"><h3>Comunidad Callqui Chico</h3><small>Tesorero</small></div>
        </div>
        <div class="user-menu">
            <div class="user-info"><div><?= htmlspecialchars($nombreCompleto) ?></div><small>Tesorero</small></div>
            <div class="user-avatar"><?= substr($usuario['nombres'], 0, 1) . substr($usuario['apellidos'], 0, 1) ?></div>
            <a href="../../logout.php" class="btn btn-outline-light btn-sm"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </nav>

    <div class="main-container">
        
        <a href="tesorero.php" class="back-btn"><i class="bi bi-arrow-left"></i> Volver al Dashboard</a>
        
        <div class="page-header">
            <h2><i class="bi bi-bank me-2 text-warning"></i>Configurar Datos de Pago</h2>
            <p>Configure los datos bancarios y Yape que看到的用户看到的看到到的用户看到</p>
        </div>
        
        <?php if ($msg): ?>
        <div class="alert alert-<?= $msg_type ?> bg-<?= $msg_type ?> bg-opacity-25 border-0 rounded-3">
            <?= $msg ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($config['cuenta_banco']) || !empty($config['numero_yape'])): ?>
        <div class="datos-actuales">
            <h5><i class="bi bi-info-circle me-2"></i>Datos Actuales</h5>
            <?php if (!empty($config['nombre_banco'])): ?>
            <div class="dato-item">
                <span class="dato-label">Banco:</span>
                <span class="dato-valor"><?= htmlspecialchars($config['nombre_banco']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($config['cuenta_banco'])): ?>
            <div class="dato-item">
                <span class="dato-label">Cuenta:</span>
                <span class="dato-valor"><?= htmlspecialchars($config['cuenta_banco']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($config['titular_cuenta'])): ?>
            <div class="dato-item">
                <span class="dato-label">Titular:</span>
                <span class="dato-valor"><?= htmlspecialchars($config['titular_cuenta']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($config['numero_yape'])): ?>
            <div class="dato-item">
                <span class="dato-label">Yape:</span>
                <span class="dato-valor"><?= htmlspecialchars($config['numero_yape']) ?></span>
            </div>
            <?php endif; ?>
            <?php if (!empty($config['qr_yape'])): ?>
            <div class="dato-item">
                <span class="dato-label">QR Yape:</span>
                <span class="dato-valor text-success"><i class="bi bi-check-circle"></i> Configurado</span>
            </div>
            <?php endif; ?>
            <?php if (!empty($config['fecha_actualizacion'])): ?>
            <div class="dato-item">
                <span class="dato-label">Última actualización:</span>
                <span class="dato-valor"><?= date('d/m/Y H:i', strtotime($config['fecha_actualizacion'])) ?></span>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST" enctype="multipart/form-data">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Nombre del Banco</label>
                        <input type="text" name="nombre_banco" class="form-control" value="<?= htmlspecialchars($config['nombre_banco'] ?? '') ?>" placeholder="Banco de Crédito del Perú">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número de Cuenta</label>
                        <input type="text" name="cuenta_banco" class="form-control" value="<?= htmlspecialchars($config['cuenta_banco'] ?? '') ?>" placeholder="123-45678901-02">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Titular de la Cuenta</label>
                        <input type="text" name="titular_cuenta" class="form-control" value="<?= htmlspecialchars($config['titular_cuenta'] ?? '') ?>" placeholder="Comunidad Campesina Callqui Chico">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Número Yape</label>
                        <input type="text" name="numero_yape" class="form-control" value="<?= htmlspecialchars($config['numero_yape'] ?? '') ?>" placeholder="987 654 321">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Imagen QR de Yape</label>
                        <div class="file-upload" onclick="document.getElementById('qr_yape_input').click()">
                            <input type="file" id="qr_yape_input" name="qr_yape" accept="image/*" onchange="previewQR(this)">
                            <i class="bi bi-qr-code"></i>
                            <p id="qr_label">Haz clic para subir el QR de Yape</p>
                        </div>
                        <?php if (!empty($config['qr_yape'])): ?>
                        <img src="../../publico/uploads/qr/<?= htmlspecialchars($config['qr_yape']) ?>" alt="QR Yape" class="qr-preview">
                        <p class="text-muted small mt-1">QR actual - Subir nuevo para reemplazar</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Instrucciones Adicionales (opcional)</label>
                        <textarea name="instrucciones" class="form-control" rows="3" placeholder="Ej: Transferir indicando código de pago en descripción"><?= htmlspecialchars($config['instrucciones'] ?? '') ?></textarea>
                    </div>
                </div>
                
                <div class="text-end mt-4">
                    <button type="submit" class="btn btn-guardar">
                        <i class="bi bi-save me-2"></i>Guardar Datos de Pago
                    </button>
                </div>
            </form>
        </div>
        
    </div>
    
    <script>
    function previewQR(input) {
        var label = document.getElementById('qr_label');
        if (input.files && input.files[0]) {
            label.innerHTML = '<i class="bi bi-check-circle text-success"></i> ' + input.files[0].name;
        }
    }
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>