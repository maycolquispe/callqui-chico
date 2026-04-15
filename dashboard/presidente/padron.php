<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($_SESSION['rol'] !== 'presidente') {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

// Obtener todos los comuneros
$sql = "
    SELECT id, dni, nombres, apellidos, telefono, promocion, padron
    FROM usuarios
    WHERE rol = 'comunero' AND estado = 'activo'
    ORDER BY padron ASC
";
$result = $conn->query($sql);
$comuneros = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

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
    <title>Padrón de Comuneros - Callqui Chico</title>
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
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-box {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 1rem;
            text-align: center;
        }
        .stat-box .number { color: white; font-size: 1.5rem; font-weight: 700; }
        .stat-box .label { color: #94a3b8; font-size: 0.85rem; }
        
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
        
        .btn-pdf {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            padding: 0.8rem 1.5rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn-pdf:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(239,68,68,0.4);
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
        
        @media (max-width: 768px) {
            .stats-row { grid-template-columns: repeat(2, 1fr); }
            .page-header { flex-direction: column; gap: 1rem; text-align: center; }
            .table-custom { font-size: 0.8rem; }
        }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <div class="logo"><i class="bi bi-tree-fill"></i></div>
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Presidente</small>
            </div>
        </div>
        <a href="../../logout.php" class="btn btn-outline-light btn-sm">
            <i class="bi bi-box-arrow-right"></i>
        </a>
    </nav>

    <div class="main-container">
        
        <a href="presidente.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
        
        <div class="page-header">
            <div>
                <h2><i class="bi bi-person-lines-fill me-2"></i>Padrón de Comuneros</h2>
                <p>Listado completo de comuneros con lotes asignados</p>
            </div>
            <a href="generar_padron_pdf.php" class="btn-pdf" target="_blank">
                <i class="bi bi-file-pdf"></i> Descargar PDF
            </a>
        </div>

        <?php
        $total = count($comuneros);
        ?>
        
        <div class="stats-row">
            <div class="stat-box">
                <div class="number"><?= $total ?></div>
                <div class="label">Total Comuneros</div>
            </div>
        </div>

        <div class="table-container">
            <table class="table-custom">
                <thead>
                    <tr>
                        <th>Padrón</th>
                        <th>DNI</th>
                        <th>Apellidos y Nombres</th>
                        <th>Teléfono</th>
                        <th>Promoción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($comuneros as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['padron'] ?? '-') ?></strong></td>
                        <td><?= htmlspecialchars($c['dni']) ?></td>
                        <td><strong><?= htmlspecialchars($c['apellidos'] . ', ' . $c['nombres']) ?></strong></td>
                        <td><?= htmlspecialchars($c['telefono'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($c['promocion'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>