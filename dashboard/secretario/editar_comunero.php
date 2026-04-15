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

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dni = $_POST['dni'] ?? '';
    $nombres = $_POST['nombres'] ?? '';
    $apellidos = $_POST['apellidos'] ?? '';
    $rol = $_POST['rol'] ?? 'comunero';
    $telefono = $_POST['telefono'] ?? '';
    $correo = $_POST['correo'] ?? '';
    
    $stmt = $conn->prepare("UPDATE usuarios SET dni=?, nombres=?, apellidos=?, rol=?, telefono=?, correo=? WHERE id=?");
    $stmt->bind_param("ssssssi", $dni, $nombres, $apellidos, $rol, $telefono, $correo, $id);
    
    if ($stmt->execute()) {
        header("Location: comuneros.php?msg=actualizado");
        exit;
    } else {
        $error = "Error al actualizar";
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Comunero | Callqui Chico</title>
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
        
        .edit-card {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
            overflow: hidden;
            max-width: 700px;
            margin: 0 auto;
        }
        
        .card-header-edit {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 30px;
            text-align: center;
        }
        
        .card-header-edit h3 {
            color: white;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .card-header-edit .subtitle {
            color: rgba(255,255,255,0.7);
            margin-top: 5px;
        }
        
        .card-body-edit {
            padding: 30px;
        }
        
        .form-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 0.9rem;
        }
        
        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 16px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(201, 164, 91, 0.2);
        }
        
        .btn-save {
            background: var(--accent);
            color: var(--primary-dark);
            padding: 12px 40px;
            border-radius: 12px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
        }
        
        .btn-save:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(201, 164, 91, 0.3);
        }
        
        .btn-cancel {
            background: #f1f5f9;
            color: var(--text-light);
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            border: none;
        }
        
        .btn-cancel:hover {
            background: #e2e8f0;
            color: var(--text-dark);
        }
        
        .alert-custom {
            border-radius: 12px;
            padding: 15px 20px;
            border: none;
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }
    </style>
</head>
<body>
    <header class="page-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="header-title">
                <h2><i class="bi bi-person-edit"></i> Editar Comunero</h2>
                <p><i class="bi bi-tree-fill me-1" style="color: var(--accent);"></i> Comunidad Campesina Callqui Chico</p>
            </div>
            <a href="comuneros.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </header>

    <div class="container py-4">
        <div class="edit-card" data-aos="fade-up">
            <div class="card-header-edit">
                <h3><i class="bi bi-pencil-square"></i> Editar Información</h3>
                <p class="subtitle">Actualiza los datos del comunero</p>
            </div>
            
            <div class="card-body-edit">
                <?php if ($error): ?>
                    <div class="alert alert-danger alert-custom">
                        <i class="bi bi-exclamation-triangle me-2"></i><?= $error ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-credit-card me-2"></i>DNI</label>
                            <input type="text" name="dni" class="form-control" value="<?= htmlspecialchars($comunero['dni']) ?>" maxlength="8" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person-badge me-2"></i>Rol</label>
                            <select name="rol" class="form-select" required>
                                <option value="comunero" <?= $comunero['rol']=='comunero'?'selected':'' ?>>Comunero</option>
                                <option value="secretario" <?= $comunero['rol']=='secretario'?'selected':'' ?>>Secretario</option>
                                <option value="presidente" <?= $comunero['rol']=='presidente'?'selected':'' ?>>Presidente</option>
                                <option value="tesorero" <?= $comunero['rol']=='tesorero'?'selected':'' ?>>Tesorero</option>
                                <option value="fiscal" <?= $comunero['rol']=='fiscal'?'selected':'' ?>>Fiscal</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person me-2"></i>Nombres</label>
                            <input type="text" name="nombres" class="form-control" value="<?= htmlspecialchars($comunero['nombres']) ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-person me-2"></i>Apellidos</label>
                            <input type="text" name="apellidos" class="form-control" value="<?= htmlspecialchars($comunero['apellidos']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-telephone me-2"></i>Teléfono</label>
                            <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($comunero['telefono'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-4">
                            <label class="form-label"><i class="bi bi-envelope me-2"></i>Correo</label>
                            <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($comunero['correo'] ?? '') ?>">
                        </div>
                    </div>
                    
                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <a href="comuneros.php" class="btn-cancel">Cancelar</a>
                        <button type="submit" class="btn-save">
                            <i class="bi bi-check-lg me-2"></i>Guardar Cambios
                        </button>
                    </div>
                </form>
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
