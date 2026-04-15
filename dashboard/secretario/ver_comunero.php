<?php
require_once '../../includes/verificar_sesion.php';

$id = intval($_GET['id'] ?? 0);
$conn = getDB();

$stmt = $conn->prepare("SELECT * FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$comunero = $result->fetch_assoc();
$stmt->close();

if (!$comunero) {
    header("Location: comuneros.php?error=no_encontrado");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ver Comunero | Callqui Chico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    
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
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-page);
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
            padding: 25px 0;
            border-bottom: 3px solid var(--accent);
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }
        
        .header-title h2 {
            color: white;
            font-weight: 700;
            font-size: 1.8rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .header-title p {
            color: rgba(255,255,255,0.7);
            margin: 5px 0 0;
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
        
        .detail-card {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        
        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 40px;
            text-align: center;
            position: relative;
        }
        
        .profile-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            background: white;
            clip-path: ellipse(60% 100% at 50% 100%);
        }
        
        .avatar-large {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 3rem;
            font-weight: 700;
            color: var(--primary-dark);
            border: 4px solid white;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .profile-name {
            color: white;
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 5px;
        }
        
        .profile-role {
            display: inline-block;
            background: var(--accent);
            color: var(--primary-dark);
            padding: 8px 20px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .info-section {
            padding: 30px;
        }
        
        .info-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 1.1rem;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .info-item {
            background: #f8f9fa;
            padding: 15px 20px;
            border-radius: 12px;
            border-left: 3px solid var(--accent);
        }
        
        .info-label {
            font-size: 0.8rem;
            color: var(--text-light);
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .info-value {
            font-weight: 600;
            color: var(--primary);
            font-size: 1rem;
        }
        
        .btn-edit {
            background: var(--accent);
            color: var(--primary-dark);
            padding: 12px 30px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }
        
        .btn-edit:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(201, 164, 91, 0.3);
        }
        
        .status-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .status-active {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #166534;
        }
        
        .status-inactive {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
        
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="header-title">
                <h2><i class="bi bi-person-fill"></i> Detalles del Comunero</h2>
                <p><i class="bi bi-tree-fill me-1" style="color: var(--accent);"></i> Comunidad Campesina Callqui Chico</p>
            </div>
            <a href="comuneros.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </header>

    <div class="container py-4">
        <div class="detail-card" data-aos="fade-up">
            <div class="profile-header">
                <div class="avatar-large">
                    <?php 
                    $ruta_foto = '';
                    if (!empty($comunero['foto'])) {
                        if (file_exists(__DIR__ . '/../../perfil/uploads/' . $comunero['foto'])) {
                            $ruta_foto = '../../perfil/uploads/' . $comunero['foto'];
                        } elseif (file_exists(__DIR__ . '/../../storage/uploads/' . $comunero['foto'])) {
                            $ruta_foto = '../../storage/uploads/' . $comunero['foto'];
                        }
                    }
                    ?>
                    <?php if($ruta_foto): ?>
                        <img src="<?= $ruta_foto ?>" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:4px solid white;" alt="Avatar">
                    <?php else: ?>
                        <?= strtoupper(substr($comunero['nombres'],0,1).substr($comunero['apellidos'],0,1)) ?>
                    <?php endif; ?>
                </div>
                <h3 class="profile-name"><?= htmlspecialchars($comunero['nombres'] . ' ' . $comunero['apellidos']) ?></h3>
                <span class="profile-role">
                    <i class="bi bi-<?= $comunero['rol'] == 'comunero' ? 'person' : ($comunero['rol'] == 'secretario' ? 'file-text' : ($comunero['rol'] == 'tesorero' ? 'cash-stack' : 'star')) ?>"></i>
                    <?= ucfirst($comunero['rol']) ?>
                </span>
            </div>
            
            <div class="info-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="info-title m-0"><i class="bi bi-info-circle"></i> Información Personal</h5>
                    <span class="status-badge <?= $comunero['estado'] == 'activo' ? 'status-active' : 'status-inactive' ?>">
                        <i class="bi bi-<?= $comunero['estado'] == 'activo' ? 'check-circle-fill' : 'x-circle-fill' ?>"></i>
                        <?= strtoupper($comunero['estado']) ?>
                    </span>
                </div>
                
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-credit-card me-2"></i>DNI</div>
                        <div class="info-value"><?= htmlspecialchars($comunero['dni']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-person me-2"></i>Nombres</div>
                        <div class="info-value"><?= htmlspecialchars($comunero['nombres']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-person-badge me-2"></i>Apellidos</div>
                        <div class="info-value"><?= htmlspecialchars($comunero['apellidos']) ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-telephone me-2"></i>Teléfono</div>
                        <div class="info-value"><?= $comunero['telefono'] ?: '<span class="text-muted">Sin teléfono</span>' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-envelope me-2"></i>Correo</div>
                        <div class="info-value"><?= $comunero['correo'] ?: '<span class="text-muted">Sin correo</span>' ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label"><i class="bi bi-calendar me-2"></i>Fecha de Registro</div>
                        <div class="info-value"><?= date('d/m/Y', strtotime($comunero['fecha_registro'])) ?></div>
                    </div>
                </div>
                
                <div class="text-center mt-4">
                    <a href="editar_comunero.php?id=<?= $id ?>" class="btn-edit">
                        <i class="bi bi-pencil"></i> Editar Información
                    </a>
                </div>
            </div>
        </div>
    </div>
    
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
