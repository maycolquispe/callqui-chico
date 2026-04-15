<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if (!in_array($_SESSION['rol'], ['presidente', 'comite_lotes', 'secretario'])) {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

$rol = $_SESSION['rol'];
$esComite = $rol === 'comite_lotes';
$esPresidente = $rol === 'presidente';

$tituloRol = $esComite ? 'Comité de Lotes' : ($esPresidente ? 'Presidente' : 'Secretario');
$dashboardUrl = $esComite ? '../comite/comite.php' : 'presidente.php';

// Obtener historial de transferencias
$sql = "SELECT t.*, 
               l.lote, l.manzana, l.sector,
               u1.nombres as nombre_anterior, u1.apellidos as apellido_anterior,
               u2.nombres as nombre_nuevo, u2.apellidos as apellido_nuevo,
               u3.nombres as nombre_registro
        FROM transferencias_lote t
        LEFT JOIN lotes l ON t.lote_id = l.id
        LEFT JOIN usuarios u1 ON t.propietario_anterior = u1.id
        LEFT JOIN usuarios u2 ON t.propietario_nuevo = u2.id
        LEFT JOIN usuarios u3 ON t.usuario_registro = u3.id
        ORDER BY t.fecha_transferencia DESC
        LIMIT 50";

$result = $conn->query($sql);
$transferencias = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Obtener datos del usuario
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
    <title>Historial de Transferencias - Callqui Chico</title>
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
            max-width: 1400px;
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
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .page-header h2 { color: white; font-weight: 700; margin-bottom: 0.5rem; }
        .page-header p { color: #94a3b8; margin: 0; }
        
        .table-container {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            overflow: hidden;
        }
        .table-custom {
            width: 100%;
            color: white;
        }
        .table-custom thead {
            background: rgba(0,0,0,0.3);
            color: #94a3b8;
        }
        .table-custom th, .table-custom td {
            padding: 0.8rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .table-custom tbody tr:hover {
            background: rgba(255,255,255,0.05);
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
                <small><?= $tituloRol ?></small>
            </div>
        </div>
        <a href="../../logout.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </nav>

    <div class="main-container">
        
        <a href="<?= $dashboardUrl ?>" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
        
        <div class="page-header">
            <div>
                <h2><i class="bi bi-arrow-left-right me-2"></i>Historial de Transferencias</h2>
                <p>Registro de cambios de propietario de lotes</p>
            </div>
        </div>

        <div class="table-container">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Fecha</th>
                        <th>Lote</th>
                        <th>Manzana</th>
                        <th>Sector</th>
                        <th>Propietario Anterior</th>
                        <th>Nuevo Propietario</th>
                        <th>Registrado por</th>
                        <th>Documento</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($transferencias) > 0): ?>
                        <?php foreach ($transferencias as $t): ?>
                        <tr>
                            <td><small><?= date('d/m/Y H:i', strtotime($t['fecha_transferencia'])) ?></small></td>
                            <td><strong><?= htmlspecialchars($t['lote']) ?></strong></td>
                            <td><?= htmlspecialchars($t['manzana']) ?></td>
                            <td><?= htmlspecialchars($t['sector']) ?></td>
                            <td>
                                <?php if ($t['nombre_anterior']): ?>
                                    <?= htmlspecialchars($t['nombre_anterior'] . ' ' . $t['apellido_anterior']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Sin propietario</span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($t['nombre_nuevo'] . ' ' . $t['apellido_nuevo']) ?></strong></td>
                            <td><small><?= htmlspecialchars($t['nombre_registro']) ?></small></td>
                            <td>
                                <?php if ($t['documento']): ?>
                                    <a href="#" class="btn btn-sm btn-outline-light">
                                        <i class="bi bi-file-earmark-pdf"></i> Ver
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-5">
                                <i class="bi bi-clock-history" style="font-size: 3rem;"></i>
                                <p class="mt-3">No hay transferencias registradas</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>