<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$usuario_id = $_SESSION['usuario_id'] ?? 0;

if ($_SESSION['rol'] !== 'presidente') {
    header("Location: ../../login.php?error=sin_permiso");
    exit;
}

// Obtener actas
$actas = $conn->query("SELECT a.*, u.nombres as nombre_creador 
                       FROM actas a 
                       LEFT JOIN usuarios u ON a.creado_por = u.id 
                       ORDER BY a.fecha DESC")->fetch_all(MYSQLI_ASSOC);

// Obtener datos del usuario
$stmtUser = $conn->prepare("SELECT foto, nombres, apellidos FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$usuario = $stmtUser->get_result()->fetch_assoc();
$stmtUser->close();
$nombreCompleto = $usuario['nombres'] . ' ' . $usuario['apellidos'];

function getTipoLabel($tipo) {
    $labels = [
        'asamble' => 'Asamblea',
        'faena' => 'Faena',
        'extraordinaria' => 'Extraordinaria'
    ];
    return $labels[$tipo] ?? $tipo;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actas - Callqui Chico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root { 
            --primary: #2563eb; 
            --primary-dark: #1e40af;
            --dark-bg: #0a1928; 
            --accent: #c9a45c;
            --accent-light: #dbb67b;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 50%, #0a1a28 100%);
            min-height: 100vh;
            position: relative;
        }
        body::before {
            content: ""; position: fixed; inset: 0;
            background: radial-gradient(circle at 20% 30%, rgba(37,99,235,0.08) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(201,164,92,0.08) 0%, transparent 50%);
            pointer-events: none; z-index: 0;
        }
        
        .navbar-modern {
            background: rgba(10, 25, 40, 0.95);
            backdrop-filter: blur(12px);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 50;
            border-bottom: 1px solid rgba(201, 164, 92, 0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .logo-area { display: flex; align-items: center; gap: 1rem; }
        .logo-text h3 { color: white; font-weight: 700; font-size: 1.1rem; margin: 0; }
        .logo-text small { color: var(--accent-light); font-size: 0.75rem; }
        
        .user-menu { display: flex; align-items: center; gap: 1rem; }
        .user-info { text-align: right; }
        .user-info div { color: white; font-weight: 500; }
        .user-info small { color: #94a3b8; }
        .user-avatar {
            width: 40px; height: 40px;
            background: linear-gradient(135deg, var(--accent), #a88642);
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            color: #0a1928; font-weight: 600; font-size: 0.85rem;
        }
        .btn-logout {
            background: rgba(255,255,255,0.1);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            border: none;
            cursor: pointer;
        }
        .btn-logout:hover { background: rgba(255,255,255,0.2); }
        
        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 1;
        }
        
        .page-header {
            background: rgba(15, 39, 64, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .page-header h2 { 
            color: white; 
            font-weight: 700; 
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .page-header p { color: #94a3b8; margin: 0; }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: rgba(15, 39, 64, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.25rem;
            text-align: center;
            transition: all 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
            border-color: var(--accent);
        }
        .stat-number { color: white; font-size: 2rem; font-weight: 700; }
        .stat-label { color: #94a3b8; font-size: 0.85rem; margin-top: 0.25rem; }
        
        .actas-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .acta-card {
            background: rgba(15, 39, 64, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s;
        }
        .acta-card:hover {
            transform: translateY(-5px);
            border-color: var(--accent);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .acta-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        .acta-title {
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            flex: 1;
        }
        .acta-fecha {
            color: #94a3b8;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        .acta-tipo {
            padding: 0.3rem 0.75rem;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            margin-bottom: 0.75rem;
        }
        .tipo-asamble { background: rgba(37,99,235,0.15); color: #60a5fa; border: 1px solid rgba(37,99,235,0.3); }
        .tipo-faena { background: rgba(245,158,11,0.15); color: #fbbf24; border: 1px solid rgba(245,158,11,0.3); }
        .tipo-extraordinaria { background: rgba(139,92,246,0.15); color: #a78bfa; border: 1px solid rgba(139,92,246,0.3); }
        
        .acta-desc {
            color: #94a3b8;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.6;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .acta-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 1rem;
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        .acta-autor {
            color: #64748b;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }
        
        .btn-descargar {
            background: linear-gradient(135deg, #2563eb, #1e40af);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.85rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        .btn-descargar:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37,99,235,0.4);
            color: white;
        }
        
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .back-btn:hover { color: white; transform: translateX(-3px); }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #94a3b8;
        }
        .empty-state i { font-size: 4rem; opacity: 0.3; margin-bottom: 1rem; }
        .empty-state h4 { color: white; margin-bottom: 0.5rem; }
        
        @media (max-width: 768px) {
            .actas-grid { grid-template-columns: 1fr; }
            .stats-row { grid-template-columns: repeat(2, 1fr); }
        }
    </style>
</head>
<body>

    <nav class="navbar-modern">
        <div class="logo-area">
            <img src="../../assets/img/logo_callqui.png" alt="Logo" style="width: 45px; height: 45px; object-fit: contain; border-radius: 8px;">
            <div class="logo-text">
                <h3>Comunidad Callqui Chico</h3>
                <small>Panel del Presidente</small>
            </div>
        </div>
        <div class="user-menu">
            <div class="user-info">
                <div><?= htmlspecialchars($nombreCompleto) ?></div>
                <small>Presidente</small>
            </div>
            <div class="user-avatar">
                <?= substr($usuario['nombres'], 0, 1) . substr($usuario['apellidos'], 0, 1) ?>
            </div>
            <a href="../../logout.php" class="btn-logout">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>

    <div class="main-container">
        
        <a href="presidente.php" class="back-btn">
            <i class="bi bi-arrow-left"></i> Volver al Dashboard
        </a>
        
        <div class="page-header">
            <h2><i class="bi bi-file-text-fill me-2"></i>Actas Registradas</h2>
            <p>Lista de todas las actas de la comunidad</p>
        </div>

        <?php
        $total_actas = count($actas);
        ?>
        
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-number"><?= $total_actas ?></div>
                <div class="stat-label">Total de Actas</div>
            </div>
        </div>

        <?php if ($total_actas > 0): ?>
        <div class="actas-grid">
            <?php foreach ($actas as $a): ?>
            <div class="acta-card">
                <div class="acta-header">
                    <div>
                        <div class="acta-title"><?= htmlspecialchars($a['titulo']) ?></div>
                        <div class="acta-fecha">
                            <i class="bi bi-calendar me-1"></i>
                            <?= date('d/m/Y', strtotime($a['fecha'])) ?>
                        </div>
                    </div>
                    <span class="acta-tipo tipo-<?= $a['tipo'] ?>">
                        <?= getTipoLabel($a['tipo']) ?>
                    </span>
                </div>
                
                <?php if ($a['descripcion']): ?>
                <p class="acta-desc"><?= htmlspecialchars($a['descripcion']) ?></p>
                <?php endif; ?>
                
                <div class="acta-meta">
                    <span class="acta-autor">
                        <i class="bi bi-person me-1"></i>
                        <?= htmlspecialchars($a['nombre_creador'] ?? 'Sistema') ?>
                    </span>
                    
                    <?php if ($a['archivo']): ?>
                    <a href="../../dashboard/uploads/<?= htmlspecialchars($a['archivo']) ?>" class="btn-descargar" target="_blank">
                        <i class="bi bi-download"></i> PDF
                    </a>
                    <?php else: ?>
                    <span class="text-muted">Sin PDF</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if (count($actas) === 0): ?>
        <div class="empty-state">
            <i class="bi bi-file-text"></i>
            <h4>No hay actas registradas</h4>
            <p>Las actas de asambleas y faenas aparecerán aquí</p>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>