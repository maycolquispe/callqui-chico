<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if (!in_array($_SESSION['rol'], ['presidente', 'comite_lotes', 'secretario'])) {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

$busqueda = $_GET['busqueda'] ?? '';
$resultados = [];

if ($busqueda) {
    $busqueda_like = "%$busqueda%";
    $stmt = $conn->prepare("
        SELECT l.*, u.nombres as nombre_propietario, u.apellidos as apellido_propietario, u.dni as dni_propietario
        FROM lotes l
        LEFT JOIN usuarios u ON l.usuario_id = u.id
        WHERE l.lote LIKE ? 
           OR l.manzana LIKE ?
           OR l.sector LIKE ?
           OR u.dni LIKE ?
           OR u.nombres LIKE ?
           OR u.apellidos LIKE ?
        ORDER BY l.sector, l.manzana, l.lote
        LIMIT 50
    ");
    $stmt->bind_param("ssssss", $busqueda_like, $busqueda_like, $busqueda_like, $busqueda_like, $busqueda_like, $busqueda_like);
    $stmt->execute();
    $result = $stmt->get_result();
    $resultados = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Obtener datos del usuario
$stmtUser = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();
$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];
$rol = $_SESSION['rol'];
$tituloRol = $rol === 'presidente' ? 'Presidente' : ($rol === 'comite_lotes' ? 'Comité de Lotes' : 'Secretario');
$dashboardUrl = $rol === 'presidente' ? 'presidente.php' : ($rol === 'comite_lotes' ? '../comite/comite.php' : '../secretario/secretario.php');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta de Lotes - Callqui Chico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2563eb;
            --dark-bg: #0a1928;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
            min-height: 100vh;
        }
        body::before {
            content: "";
            position: fixed;
            inset: 0;
            background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.1) 0%, transparent 50%);
            pointer-events: none;
            z-index: 0;
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
            max-width: 1200px;
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
        
        .search-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .form-control-custom {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            color: white;
        }
        .form-control-custom:focus {
            background: rgba(0,0,0,0.4);
            border-color: #2563eb;
            color: white;
            box-shadow: 0 0 0 3px rgba(37,99,235,0.2);
        }
        .form-control-custom::placeholder { color: #64748b; }
        
        .results-table {
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
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .table-custom tbody tr:hover {
            background: rgba(255,255,255,0.05);
        }
        .badge-status {
            padding: 0.3rem 0.8rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge-activo { background: rgba(16,185,129,0.2); color: #10b981; }
        .badge-inactivo { background: rgba(239,68,68,0.2); color: #ef4444; }
        .badge-libre { background: rgba(245,158,11,0.2); color: #f59e0b; }
        
        .btn-action {
            padding: 0.4rem 0.8rem;
            border-radius: 8px;
            font-size: 0.85rem;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 1rem;
            transition: color 0.3s;
        }
        .back-btn:hover { color: white; }
        
        @media (max-width: 768px) {
            .table-custom { font-size: 0.85rem; }
            .table-custom th, .table-custom td { padding: 0.5rem; }
        }
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
            <h2><i class="bi bi-map-fill me-2"></i>Consulta de Lotes</h2>
            <p>Busque por número de lote, manzana, sector, DNI o nombre del comunero</p>
        </div>

        <div class="search-box">
            <form method="GET" class="row g-3">
                <div class="col-md-10">
                    <input type="text" name="busqueda" class="form-control form-control-custom" 
                           placeholder="Ingrese: número de lote, manzana, sector, DNI o nombre..." 
                           value="<?= htmlspecialchars($busqueda) ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search me-1"></i> Buscar
                    </button>
                </div>
            </form>
        </div>

        <?php if ($busqueda): ?>
            <div class="results-table">
                <table class="table-custom">
                    <thead>
                        <tr>
                            <th>Lote</th>
                            <th>Manzana</th>
                            <th>Sector</th>
                            <th>Área (m²)</th>
                            <th>Propietario</th>
                            <th>DNI</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($resultados) > 0): ?>
                            <?php foreach ($resultados as $lote): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($lote['lote']) ?></strong></td>
                                <td><?= htmlspecialchars($lote['manzana'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($lote['sector'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($lote['area_m2'] ?? $lote['area']) ?></td>
                                <td>
                                    <?php if ($lote['nombre_propietario']): ?>
                                        <?= htmlspecialchars($lote['nombre_propietario'] . ' ' . $lote['apellido_propietario']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin propietario</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($lote['dni_propietario'] ?? '-') ?></td>
                                <td>
                                    <span class="badge-status <?= $lote['estado'] === 'activo' ? 'badge-activo' : 'badge-inactivo' ?>">
                                        <?= htmlspecialchars(ucfirst($lote['estado'] ?? 'libre')) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">
                                    <i class="bi bi-search" style="font-size: 2rem;"></i>
                                    <p class="mt-2">No se encontraron lotes con ese criterio de búsqueda</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($resultados) > 0): ?>
                <p class="text-white-50 mt-2"><small>Se encontraron <?= count($resultados) ?> resultado(s)</small></p>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-map" style="font-size: 4rem;"></i>
                <p class="mt-3">Ingrese un criterio de búsqueda para encontrar lotes</p>
            </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>