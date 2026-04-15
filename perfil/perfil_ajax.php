<?php
session_start();
require_once '../config/conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: ../login.php");
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$buscar_id = isset($_GET['buscar_id']) ? (int)$_GET['buscar_id'] : null;
$mostrar_id = $buscar_id ?? $usuario_id;

if ($buscar_id !== null && $buscar_id != $usuario_id) {
    $stmt = $conn->prepare("SELECT id FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $buscar_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $mostrar_id = $usuario_id;
    }
}

$modo = $_GET['modo'] ?? 'ver';
$mensaje = '';
$tipo_mensaje = '';

if (isset($_GET['mensaje']) && $_GET['mensaje'] === 'ok') {
    $mensaje = "Perfil guardado correctamente";
    $tipo_mensaje = "success";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['perfil_submit']) && $mostrar_id == $usuario_id) {
    $nombres = trim($_POST['nombres'] ?? "");
    $fecha = $_POST['fecha_nacimiento'] ?? "";
    $origen = trim($_POST['lugar_origen'] ?? "");
    $promocion = trim($_POST['promocion'] ?? "");
    $telefono = trim($_POST['telefono'] ?? "");
    $correo = trim($_POST['correo'] ?? "");

    if (empty($nombres)) {
        $mensaje = "El nombre es obligatorio";
        $tipo_mensaje = "danger";
    } elseif (!empty($correo) && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $mensaje = "El correo electrónico no es válido";
        $tipo_mensaje = "danger";
    } else {
        $carpeta = "uploads/";
        if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

        $fotoNombre = null;
        $portadaNombre = null;

        if (isset($_FILES['foto']) && $_FILES['foto']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
            $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $extensiones)) {
                $stmt = $conn->prepare("SELECT foto FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $old_foto = $stmt->get_result()->fetch_assoc()['foto'] ?? '';
                if (!empty($old_foto) && file_exists($carpeta . $old_foto)) {
                    unlink($carpeta . $old_foto);
                }
                
                $fotoNombre = "user_" . $usuario_id . "_" . time() . "." . $ext;
                move_uploaded_file($_FILES["foto"]["tmp_name"], $carpeta . $fotoNombre);
            }
        }

        if (isset($_FILES['portada']) && $_FILES['portada']['error'] == 0) {
            $ext = strtolower(pathinfo($_FILES["portada"]["name"], PATHINFO_EXTENSION));
            $extensiones = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (in_array($ext, $extensiones)) {
                $stmt = $conn->prepare("SELECT portada FROM usuarios WHERE id = ?");
                $stmt->bind_param("i", $usuario_id);
                $stmt->execute();
                $old_portada = $stmt->get_result()->fetch_assoc()['portada'] ?? '';
                if (!empty($old_portada) && file_exists($carpeta . $old_portada)) {
                    unlink($carpeta . $old_portada);
                }
                
                $portadaNombre = "cover_" . $usuario_id . "_" . time() . "." . $ext;
                move_uploaded_file($_FILES["portada"]["tmp_name"], $carpeta . $portadaNombre);
            }
        }

        if ($fotoNombre && $portadaNombre) {
            $stmt = $conn->prepare("UPDATE usuarios SET nombres=?, fecha_nacimiento=?, lugar_origen=?, promocion=?, telefono=?, correo=?, foto=?, portada=? WHERE id=?");
            $stmt->bind_param("ssssssssi", $nombres, $fecha, $origen, $promocion, $telefono, $correo, $fotoNombre, $portadaNombre, $usuario_id);
        } elseif ($fotoNombre) {
            $stmt = $conn->prepare("UPDATE usuarios SET nombres=?, fecha_nacimiento=?, lugar_origen=?, promocion=?, telefono=?, correo=?, foto=? WHERE id=?");
            $stmt->bind_param("sssssssi", $nombres, $fecha, $origen, $promocion, $telefono, $correo, $fotoNombre, $usuario_id);
        } elseif ($portadaNombre) {
            $stmt = $conn->prepare("UPDATE usuarios SET nombres=?, fecha_nacimiento=?, lugar_origen=?, promocion=?, telefono=?, correo=?, portada=? WHERE id=?");
            $stmt->bind_param("sssssssi", $nombres, $fecha, $origen, $promocion, $telefono, $correo, $portadaNombre, $usuario_id);
        } else {
            $stmt = $conn->prepare("UPDATE usuarios SET nombres=?, fecha_nacimiento=?, lugar_origen=?, promocion=?, telefono=?, correo=? WHERE id=?");
            $stmt->bind_param("ssssssi", $nombres, $fecha, $origen, $promocion, $telefono, $correo, $usuario_id);
        }
        
        if ($stmt->execute()) {
            header("Location: perfil_ajax.php?mensaje=ok");
            exit();
        } else {
            $mensaje = "Error al guardar el perfil";
            $tipo_mensaje = "danger";
        }
    }
}

$buscarResultados = [];
$q = $_GET['q'] ?? '';
if (!empty($q)) {
    $stmt = $conn->prepare("SELECT id, nombres, apellidos, correo, telefono, foto FROM usuarios WHERE (nombres LIKE ? OR apellidos LIKE ?) AND id != ? LIMIT 10");
    $like = "%" . $q . "%";
    $stmt->bind_param("ssi", $like, $like, $_SESSION['usuario_id']);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $buscarResultados[] = $row;
    }
}

$stmt = $conn->prepare("SELECT dni, nombres, apellidos, foto, portada, fecha_nacimiento, lugar_origen, promocion, telefono, correo FROM usuarios WHERE id = ?");
$stmt->bind_param("i", $mostrar_id);
$stmt->execute();
$usuario = $stmt->get_result()->fetch_assoc();

if (!$usuario) {
    header("Location: perfil_ajax.php");
    exit();
}

$es_perfil_propio = ($mostrar_id == $usuario_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Perfil | Comunidad Callqui Chico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #0a2b3c;
            --primary-dark: #06212e;
            --primary-light: #1e4a6a;
            --accent: #c9a45c;
            --accent-light: #dbb67b;
            --accent-dark: #a88642;
            --bg-page: #0f172a;
            --bg-card: #1e293b;
            --text-dark: #f1f5f9;
            --text-light: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.3);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.5);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0f172a 0%, #1e293b 50%, #0f172a 100%);
            min-height: 100vh;
            color: var(--text-dark);
        }

        .alert-custom {
            border: none;
            border-radius: 12px;
            padding: 1rem 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-weight: 500;
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border-left: 4px solid #10b981;
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border-left: 4px solid #ef4444;
        }

        .nav-bar {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(10px);
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
            border-bottom: 3px solid var(--accent);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-area { display: flex; align-items: center; gap: 1rem; }

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
            color: #f8fafc;
        }

        .logo-text small { color: var(--text-light); font-size: 0.75rem; }

        .btn-nav {
            background: var(--accent);
            color: #0f172a;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            font-weight: 600;
        }

        .btn-nav:hover { background: var(--accent-light); transform: translateY(-2px); }

        .main-container { max-width: 900px; margin: 0 auto 2rem; padding: 0 1.5rem; }

        .action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .btn-back {
            background: rgba(30, 41, 59, 0.8);
            color: #f8fafc;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: 1px solid #334155;
            font-weight: 500;
        }

        .btn-back:hover { background: var(--accent); color: #0f172a; transform: translateX(-5px); }

        .btn-my-profile {
            background: var(--accent);
            color: #0f172a;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            font-weight: 600;
        }

        .btn-my-profile:hover { background: var(--accent-dark); color: white; transform: translateY(-2px); }

        .profile-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            margin-bottom: 2rem;
            border: 1px solid #334155;
        }

        .profile-cover { height: 280px; background-size: cover; background-position: center; position: relative; }

        .default-cover {
            background: linear-gradient(135deg, #1e4a6a 0%, #0a2b3c 50%, #06212e 100%);
            position: relative;
            overflow: hidden;
        }

        .default-cover::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(201,164,91,0.3) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .default-cover::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 100px;
            background: linear-gradient(to top, rgba(30,41,59,0.5), transparent);
        }

        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        .profile-photo-container { position: relative; width: 170px; height: 170px; margin: -85px auto 0; z-index: 10; }

        .profile-photo {
            width: 100%;
            height: 100%;
            border-radius: 50%;
            object-fit: cover;
            border: 5px solid #1e293b;
            box-shadow: var(--shadow-lg);
            transition: transform 0.3s ease;
        }

        .profile-photo:hover { transform: scale(1.05); }

        .photo-badge {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background: var(--accent);
            color: #0f172a;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 3px solid #1e293b;
            box-shadow: var(--shadow-md);
        }

        .profile-name { text-align: center; padding: 1rem 2rem 1.5rem; }

        .profile-name h3 { font-size: 2rem; font-weight: 700; color: #f8fafc; margin-bottom: 0.5rem; }

        .profile-badge {
            display: inline-block;
            background: rgba(201,164,91,0.2);
            color: var(--accent-light);
            padding: 0.3rem 1.2rem;
            border-radius: 30px;
            font-size: 0.9rem;
            font-weight: 600;
            border: 1px solid rgba(201,164,91,0.3);
        }

        .profile-badge-secondary { background: rgba(30, 58, 106, 0.5); color: #60a5fa; border-color: rgba(96, 165, 250, 0.3); }

        .profile-info { padding: 0 2rem 2rem; }

        .info-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.5rem; margin-bottom: 2rem; }

        .info-item {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 16px;
            padding: 1.2rem;
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }

        .info-item:hover { border-color: var(--accent); box-shadow: var(--shadow-sm); transform: translateY(-2px); }

        .info-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background: rgba(201,164,91,0.2);
            color: var(--accent-light);
            border-radius: 10px;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .info-label { font-size: 0.75rem; color: var(--text-light); text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 0.3rem; }

        .info-value { font-weight: 600; color: #f8fafc; font-size: 1.1rem; }

        .info-value.empty { color: var(--text-light); font-weight: 400; font-style: italic; }

        .profile-actions { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1rem; margin-top: 2rem; }

        .btn-action {
            padding: 0.8rem;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            font-size: 0.95rem;
        }

        .btn-edit { background: var(--accent); color: #0f172a; }
        .btn-edit:hover { background: var(--accent-light); transform: translateY(-2px); }

        .btn-facebook { background: #1877f2; color: white; }
        .btn-facebook:hover { background: #3b82f6; transform: translateY(-2px); }

        .btn-whatsapp { background: #25d366; color: white; }
        .btn-whatsapp:hover { background: #22c55e; transform: translateY(-2px); }

        .search-section {
            background: rgba(15, 23, 42, 0.6);
            border-radius: 20px;
            padding: 1.5rem;
            border: 1px solid #334155;
            margin-top: 2rem;
        }

        .search-title { display: flex; align-items: center; gap: 0.5rem; color: #f8fafc; font-weight: 600; margin-bottom: 1rem; }

        .search-form { display: flex; gap: 1rem; }

        .search-input {
            flex: 1;
            padding: 0.8rem 1rem;
            border: 2px solid #334155;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-family: inherit;
            background: rgba(30, 41, 59, 0.8);
            color: #f8fafc;
        }

        .search-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(201,164,91,0.2); outline: none; }
        .search-input::placeholder { color: var(--text-light); }

        .btn-search {
            background: var(--accent);
            color: #0f172a;
            border: none;
            padding: 0 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .btn-search:hover { background: var(--accent-light); }

        .spinner-search { width: 16px; height: 16px; border: 2px solid transparent; border-top-color: currentColor; border-radius: 50%; animation: spin 0.8s linear infinite; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .search-results { margin-top: 1.5rem; }
        .result-title { font-weight: 600; color: #f8fafc; margin-bottom: 1rem; }

        .result-card {
            background: rgba(30, 41, 59, 0.8);
            border-radius: 16px;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border: 1px solid #334155;
            transition: all 0.3s ease;
        }

        .result-card:hover { border-color: var(--accent); box-shadow: var(--shadow-sm); }
        .result-info { display: flex; align-items: center; gap: 1rem; }

        .result-foto { width: 50px; height: 50px; border-radius: 50%; object-fit: cover; border: 2px solid var(--accent); }
        .result-details { display: flex; flex-direction: column; }
        .result-nombre { font-weight: 600; color: #f8fafc; }
        .result-contacto { font-size: 0.85rem; color: var(--text-light); display: flex; gap: 1rem; }

        .btn-view {
            background: var(--accent);
            color: #0f172a;
            padding: 0.5rem 1.2rem;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            font-weight: 500;
        }

        .btn-view:hover { background: var(--accent-light); }

        .edit-card {
            background: rgba(30, 41, 59, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 24px;
            padding: 2rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid #334155;
        }

        .edit-title { display: flex; align-items: center; gap: 0.5rem; color: #f8fafc; font-weight: 700; margin-bottom: 2rem; font-size: 1.5rem; }

        .form-label { font-weight: 600; color: #f8fafc; margin-bottom: 0.5rem; font-size: 0.9rem; }

        .form-control {
            border: 2px solid #334155;
            border-radius: 10px;
            padding: 0.7rem 1rem;
            transition: all 0.3s ease;
            font-family: inherit;
            background: rgba(15, 23, 42, 0.8);
            color: #f8fafc;
        }

        .form-control:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(201,164,91,0.2); outline: none; background: rgba(15, 23, 42, 1); color: #f8fafc; }
        .form-control::placeholder { color: var(--text-light); }
        .form-control.is-invalid { border-color: var(--danger); }
        .invalid-feedback { color: #f87171; font-size: 0.85rem; margin-top: 0.25rem; }

        .file-upload {
            background: rgba(15, 23, 42, 0.6);
            border: 2px dashed #334155;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .file-upload:hover { border-color: var(--accent); background: rgba(201,164,91,0.1); }
        .file-upload i { font-size: 2rem; color: var(--accent); }
        .file-upload p { margin: 0.5rem 0 0; color: var(--text-light); font-size: 0.85rem; }
        .file-name { margin-top: 0.5rem; font-size: 0.8rem; color: var(--accent-light); font-weight: 500; }

        .file-preview { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; border-radius: 8px; display: none; }
        .file-preview.active { display: block; }

        .btn-save { background: var(--accent); color: #0f172a; border: none; padding: 1rem 2rem; border-radius: 10px; font-weight: 600; transition: all 0.3s ease; cursor: pointer; font-size: 1rem; }
        .btn-save:hover { background: var(--accent-light); transform: translateY(-2px); }
        .btn-save:disabled { background: #475569; cursor: not-allowed; transform: none; }

        .btn-cancel { background: #334155; color: #f8fafc; border: none; padding: 1rem 2rem; border-radius: 10px; font-weight: 600; transition: all 0.3s ease; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-cancel:hover { background: #475569; color: #f8fafc; }

        .empty-state { text-align: center; padding: 2rem; color: var(--text-light); }
        .empty-state i { font-size: 3rem; color: #334155; margin-bottom: 1rem; }

        @media (max-width: 768px) {
            .info-grid { grid-template-columns: 1fr; }
            .profile-actions { grid-template-columns: 1fr; }
            .search-form { flex-direction: column; }
            .result-card { flex-direction: column; gap: 1rem; text-align: center; }
            .result-info { flex-direction: column; }
            .result-contacto { flex-direction: column; gap: 0.3rem; }
            .profile-name h3 { font-size: 1.5rem; }
            .profile-cover { height: 200px; }
            .profile-photo-container { width: 130px; height: 130px; margin-top: -65px; }
            .action-bar { flex-direction: column; align-items: stretch; }
            .btn-back, .btn-my-profile { justify-content: center; }
        }
    </style>
</head>
<body>

    <div class="nav-bar">
        <div class="nav-container">
            <div class="logo-area">
                <div class="logo"><i class="bi bi-tree"></i></div>
                <div class="logo-text">
                    <h3>Callqui Chico</h3>
                    <small>Comunidad Rural</small>
                </div>
            </div>
            <a href="../dashboard/comunero/comunero.php" class="btn-nav">
                <i class="bi bi-house"></i> Inicio
            </a>
        </div>
    </div>

    <div class="main-container">

        <?php if (!empty($mensaje)): ?>
        <div class="alert-<?= $tipo_mensaje ?> alert-custom mb-4" id="alertMessage">
            <?php if ($tipo_mensaje === 'success'): ?>
                <i class="bi bi-check-circle-fill"></i>
            <?php else: ?>
                <i class="bi bi-exclamation-circle-fill"></i>
            <?php endif; ?>
            <?= htmlspecialchars($mensaje) ?>
        </div>
        <?php endif; ?>

        <div class="action-bar">
            <a href="../dashboard/comunero/comunero.php" class="btn-back">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            <?php if (!$es_perfil_propio): ?>
                <a href="perfil_ajax.php" class="btn-my-profile">
                    <i class="bi bi-person"></i> Mi perfil
                </a>
            <?php endif; ?>
        </div>

        <?php if ($modo == "ver"): ?>

        <div class="profile-card">
            <div class="profile-cover <?= empty($usuario['portada']) ? 'default-cover' : '' ?>" 
                 style="<?= !empty($usuario['portada']) ? 'background-image:url(\'uploads/'.$usuario['portada'].'\')' : '' ?>">
            </div>

            <div class="profile-photo-container">
                <img src="<?= !empty($usuario['foto']) ? 'uploads/'.$usuario['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($usuario['nombres'].' '.$usuario['apellidos']).'&background=c9a45c&color=0a2b3c&size=170&font-size=0.4' ?>" 
                     class="profile-photo" alt="Foto perfil">
                <?php if ($es_perfil_propio): ?>
                <div class="photo-badge"><i class="bi bi-camera-fill"></i></div>
                <?php endif; ?>
            </div>

            <div class="profile-name">
                <h3><?= htmlspecialchars($usuario['nombres'] . ' ' . $usuario['apellidos']) ?></h3>
                <span class="profile-badge"><i class="bi bi-person-badge me-1"></i> DNI: <?= htmlspecialchars($usuario['dni']) ?></span>
            </div>

            <div class="profile-info">
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-calendar"></i></div>
                        <div class="info-label">Fecha de nacimiento</div>
                        <div class="info-value <?= empty($usuario['fecha_nacimiento']) ? 'empty' : '' ?>">
                            <?= !empty($usuario['fecha_nacimiento']) ? date('d/m/Y', strtotime($usuario['fecha_nacimiento'])) : 'No registrado' ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-geo-alt"></i></div>
                        <div class="info-label">Lugar de origen</div>
                        <div class="info-value <?= empty($usuario['lugar_origen']) ? 'empty' : '' ?>">
                            <?= !empty($usuario['lugar_origen']) ? htmlspecialchars($usuario['lugar_origen']) : 'No registrado' ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-trophy"></i></div>
                        <div class="info-label">Promoción</div>
                        <div class="info-value <?= empty($usuario['promocion']) ? 'empty' : '' ?>">
                            <?= !empty($usuario['promocion']) ? htmlspecialchars($usuario['promocion']) : 'No registrado' ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-phone"></i></div>
                        <div class="info-label">Teléfono</div>
                        <div class="info-value <?= empty($usuario['telefono']) ? 'empty' : '' ?>">
                            <?= !empty($usuario['telefono']) ? htmlspecialchars($usuario['telefono']) : 'No registrado' ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-envelope"></i></div>
                        <div class="info-label">Correo electrónico</div>
                        <div class="info-value <?= empty($usuario['correo']) ? 'empty' : '' ?>">
                            <?= !empty($usuario['correo']) ? htmlspecialchars($usuario['correo']) : 'No registrado' ?>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon"><i class="bi bi-person-vcard"></i></div>
                        <div class="info-label">Documento</div>
                        <div class="info-value"><?= htmlspecialchars($usuario['dni']) ?></div>
                    </div>
                </div>

                <div class="profile-actions">
                    <?php if ($es_perfil_propio): ?>
                        <a href="?modo=editar" class="btn-action btn-edit"><i class="bi bi-pencil"></i> Editar perfil</a>
                    <?php endif; ?>
                    <a href="https://www.facebook.com/" target="_blank" class="btn-action btn-facebook"><i class="bi bi-facebook"></i> Facebook</a>
                    <?php if (!empty($usuario['telefono'])): ?>
                    <a href="https://wa.me/51<?= preg_replace('/[^0-9]/', '', $usuario['telefono']) ?>?text=Hola%20<?= urlencode($usuario['nombres']) ?>%2C%20te%20contacto%20desde%20el%20sistema%20comunal"
                       class="btn-action btn-whatsapp" target="_blank"><i class="bi bi-whatsapp"></i> WhatsApp</a>
                    <?php endif; ?>
                </div>

                <?php if ($es_perfil_propio): ?>
                <div class="search-section">
                    <div class="search-title"><i class="bi bi-search"></i> Buscar otros comuneros</div>
                    <form method="GET" class="search-form" id="searchForm">
                        <input type="hidden" name="modo" value="ver">
                        <input type="text" name="q" id="searchInput" class="search-input" placeholder="Nombre del comunero..." value="<?= htmlspecialchars($q) ?>">
                        <button class="btn-search" type="submit" id="searchBtn">
                            <span class="spinner-search" id="spinner"></span>
                            <i class="bi bi-search"></i><span>Buscar</span>
                        </button>
                    </form>

                    <div id="searchResults">
                        <?php if (!empty($buscarResultados)): ?>
                            <div class="search-results">
                                <div class="result-title"><i class="bi bi-people me-1"></i> Resultados encontrados (<?= count($buscarResultados) ?>):</div>
                                <?php foreach($buscarResultados as $com): ?>
                                    <div class="result-card mb-2">
                                        <div class="result-info">
                                            <img src="<?= !empty($com['foto']) ? 'uploads/'.$com['foto'] : 'https://ui-avatars.com/api/?name='.urlencode($com['nombres'].' '.$com['apellidos']).'&background=c9a45c&color=0a2b3c&size=50&font-size=0.4' ?>" class="result-foto">
                                            <div class="result-details">
                                                <span class="result-nombre"><?= htmlspecialchars($com['nombres'] . ' ' . $com['apellidos']) ?></span>
                                                <span class="result-contacto">
                                                    <i class="bi bi-envelope"></i> <?= htmlspecialchars($com['correo'] ?: 'Sin correo') ?>
                                                    <i class="bi bi-phone ms-2"></i> <?= htmlspecialchars($com['telefono'] ?: 'Sin teléfono') ?>
                                                </span>
                                            </div>
                                        </div>
                                        <a href="?buscar_id=<?= $com['id'] ?>" class="btn-view"><i class="bi bi-eye"></i> Ver perfil</a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php elseif (!empty($q) && empty($buscarResultados)): ?>
                            <div class="empty-state mt-3"><i class="bi bi-person-x"></i><p>No se encontraron comuneros con ese nombre</p></div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($modo == "editar" && $es_perfil_propio): ?>

        <div class="edit-card">
            <div class="edit-title"><i class="bi bi-pencil-square"></i> Editar mi perfil</div>

            <form method="POST" enctype="multipart/form-data" id="editForm" novalidate>
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label">Foto de perfil</label>
                        <div class="file-upload" onclick="document.getElementById('fotoInput').click()">
                            <img src="" class="file-preview" id="fotoPreview">
                            <i class="bi bi-camera" id="fotoIcon"></i>
                            <p id="fotoText">Haz clic para seleccionar imagen</p>
                            <div class="file-name" id="fotoName"></div>
                        </div>
                        <input type="file" id="fotoInput" name="foto" class="d-none" accept="image/*">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Foto de portada</label>
                        <div class="file-upload" onclick="document.getElementById('portadaInput').click()">
                            <img src="" class="file-preview" id="portadaPreview">
                            <i class="bi bi-image" id="portadaIcon"></i>
                            <p id="portadaText">Haz clic para seleccionar imagen</p>
                            <div class="file-name" id="portadaName"></div>
                        </div>
                        <input type="file" id="portadaInput" name="portada" class="d-none" accept="image/*">
                    </div>
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nombres *</label>
                        <input type="text" name="nombres" id="nombres" class="form-control" value="<?= htmlspecialchars($usuario['nombres'] ?? '') ?>" required>
                        <div class="invalid-feedback" id="nombresError">El nombre es obligatorio</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Fecha de nacimiento</label>
                        <input type="date" name="fecha_nacimiento" class="form-control" value="<?= $usuario['fecha_nacimiento'] ?? '' ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Lugar de origen</label>
                        <input type="text" name="lugar_origen" class="form-control" value="<?= htmlspecialchars($usuario['lugar_origen'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Promoción</label>
                        <input type="text" name="promocion" class="form-control" value="<?= htmlspecialchars($usuario['promocion'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($usuario['telefono'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Correo electrónico</label>
                        <input type="email" name="correo" id="correo" class="form-control" value="<?= htmlspecialchars($usuario['correo'] ?? '') ?>">
                        <div class="invalid-feedback" id="correoError">El correo no es válido</div>
                    </div>
                </div>

                <div class="d-flex gap-2 mt-4 flex-wrap">
                    <button type="submit" name="perfil_submit" class="btn-save" id="submitBtn">
                        <i class="bi bi-check-lg me-2"></i> Guardar cambios
                    </button>
                    <a href="perfil_ajax.php?modo=ver" class="btn-cancel"><i class="bi bi-x-lg"></i> Cancelar</a>
                </div>
            </form>
        </div>

        <?php else: ?>
        <div class="profile-card">
            <div class="empty-state">
                <i class="bi bi-exclamation-triangle"></i>
                <p>No tienes permiso para editar este perfil</p>
                <a href="perfil_ajax.php" class="btn-back mt-3" style="display: inline-flex;">
                    <i class="bi bi-arrow-left"></i> Volver a mi perfil
                </a>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        setTimeout(() => {
            const alert = document.getElementById('alertMessage');
            if (alert) { alert.style.opacity = '0'; setTimeout(() => alert.remove(), 300); }
        }, 5000);

        document.getElementById('fotoInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('fotoPreview');
            const name = document.getElementById('fotoName');
            const text = document.getElementById('fotoText');
            const icon = document.getElementById('fotoIcon');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { preview.src = e.target.result; preview.classList.add('active'); icon.style.display = 'none'; text.textContent = 'Cambiar imagen'; }
                reader.readAsDataURL(file);
                name.textContent = file.name;
            }
        });

        document.getElementById('portadaInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            const preview = document.getElementById('portadaPreview');
            const name = document.getElementById('portadaName');
            const text = document.getElementById('portadaText');
            const icon = document.getElementById('portadaIcon');
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) { preview.src = e.target.result; preview.classList.add('active'); icon.style.display = 'none'; text.textContent = 'Cambiar imagen'; }
                reader.readAsDataURL(file);
                name.textContent = file.name;
            }
        });

        document.getElementById('editForm')?.addEventListener('submit', function(e) {
            e.preventDefault();
            let isValid = true;
            const nombres = document.getElementById('nombres');
            const correo = document.getElementById('correo');
            nombres.classList.remove('is-invalid');
            correo.classList.remove('is-invalid');
            if (!nombres.value.trim()) { nombres.classList.add('is-invalid'); isValid = false; }
            if (correo.value.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.value)) { correo.classList.add('is-invalid'); isValid = false; }
            if (!isValid) return;
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Guardando...';
            this.submit();
        });

        document.getElementById('searchForm')?.addEventListener('submit', function() {
            const btn = document.getElementById('searchBtn');
            const spinner = document.getElementById('spinner');
            btn.disabled = true;
            spinner.style.display = 'inline-block';
        });
    </script>
</body>
</html>
