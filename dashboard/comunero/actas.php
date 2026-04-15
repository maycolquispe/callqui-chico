<?php
require_once "../../config/config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], ['comunero','secretario','presidente'])) {
    header("Location: ../login.php");
    exit;
}

$sql = "SELECT a.*, u.nombres, u.apellidos,
        (SELECT COUNT(*) FROM acta_likes l WHERE l.acta_id = a.id) as total_likes
        FROM actas a
        LEFT JOIN usuarios u ON a.creado_por = u.id
        ORDER BY a.fecha_registro DESC";

$result = $conn->query($sql);

$usuario_id = $_SESSION['usuario_id'] ?? 0;

$stmtUser = $conn->prepare("SELECT foto, nombres FROM usuarios WHERE id=?");
$stmtUser->bind_param("i", $usuario_id);
$stmtUser->execute();
$userData = $stmtUser->get_result()->fetch_assoc();

$fotoPerfil = !empty($userData['foto']) ? $userData['foto'] : 'default.png';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Actas Comunales - Callqui Chico</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&family=Space+Grotesk:wght@300..700&display=swap" rel="stylesheet">
<style>
    :root {
        --primary: #6366f1;
        --primary-hover: #4f46e5;
        --secondary: #10b981;
        --danger: #f43f5e;
        --warning: #f59e0b;
        --dark-bg: #0c0f1a;
        --dark-card: #131a2b;
        --dark-card-hover: #1a2235;
        --text-light: #f1f5f9;
        --text-muted: #94a3b8;
        --border-color: rgba(99, 102, 241, 0.15);
        --glass: rgba(255, 255, 255, 0.03);
        --glass-border: rgba(255, 255, 255, 0.08);
    }

    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
        font-family: 'DM Sans', sans-serif;
        background: var(--dark-bg);
        min-height: 100vh;
        color: var(--text-light);
    }

    body::before {
        content: "";
        position: fixed;
        top: 0; left: 0; right: 0; bottom: 0;
        background: 
            radial-gradient(ellipse 80% 50% at 20% -10%, rgba(99, 102, 241, 0.15) 0%, transparent 50%),
            radial-gradient(ellipse 60% 40% at 80% 110%, rgba(16, 185, 129, 0.1) 0%, transparent 50%);
        pointer-events: none;
        z-index: -1;
    }

    .navbar-custom {
        background: linear-gradient(180deg, rgba(12, 15, 26, 0.98) 0%, rgba(12, 15, 26, 0.85) 100%);
        backdrop-filter: blur(20px);
        border-bottom: 1px solid var(--border-color);
        padding: 0.875rem 0;
        position: sticky;
        top: 0;
        z-index: 100;
    }

    .navbar-custom .container {
        display: flex;
        align-items: center;
        justify-content: space-between;
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 1.5rem;
    }

    .brand {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .brand-icon {
        width: 42px;
        height: 42px;
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        box-shadow: 0 4px 15px rgba(99, 102, 241, 0.3);
    }

    .brand-text h1 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.125rem;
        font-weight: 600;
        color: white;
        margin: 0;
        line-height: 1.2;
    }

    .brand-text span {
        font-size: 0.75rem;
        color: var(--text-muted);
        letter-spacing: 0.5px;
    }

    .nav-links {
        display: flex;
        gap: 0.5rem;
    }

    .nav-btn {
        background: var(--glass);
        border: 1px solid var(--glass-border);
        color: var(--text-muted);
        padding: 0.625rem 1.25rem;
        border-radius: 12px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .nav-btn:hover {
        background: rgba(99, 102, 241, 0.15);
        border-color: var(--primary);
        color: white;
        transform: translateY(-1px);
    }

    .main-wrapper {
        max-width: 1400px;
        margin: 0 auto;
        padding: 2rem 1.5rem;
    }

    .page-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 2rem;
        flex-wrap: wrap;
        gap: 1rem;
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .page-title i {
        font-size: 1.75rem;
        color: var(--primary);
    }

    .page-title h2 {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.75rem;
        font-weight: 600;
        margin: 0;
    }

    .page-title p {
        color: var(--text-muted);
        margin: 0.25rem 0 0 0;
        font-size: 0.875rem;
    }

    .back-btn {
        background: var(--glass);
        border: 1px solid var(--glass-border);
        color: var(--text-light);
        padding: 0.75rem 1.5rem;
        border-radius: 14px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        font-weight: 500;
        transition: all 0.25s ease;
    }

    .back-btn:hover {
        background: var(--primary);
        color: white;
        transform: translateX(-4px);
    }

    .stats-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .stat-item {
        background: var(--dark-card);
        border: 1px solid var(--border-color);
        border-radius: 20px;
        padding: 1.25rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        transition: all 0.3s ease;
    }

    .stat-item:hover {
        border-color: var(--primary);
        transform: translateY(-2px);
    }

    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
    }

    .stat-icon.blue {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(99, 102, 241, 0.1));
        color: var(--primary);
    }

    .stat-icon.red {
        background: linear-gradient(135deg, rgba(244, 63, 94, 0.2), rgba(244, 63, 94, 0.1));
        color: var(--danger);
    }

    .stat-icon.green {
        background: linear-gradient(135deg, rgba(16, 185, 129, 0.2), rgba(16, 185, 129, 0.1));
        color: var(--secondary);
    }

    .stat-label {
        font-size: 0.8rem;
        color: var(--text-muted);
        margin-bottom: 0.125rem;
    }

    .stat-value {
        font-family: 'Space Grotesk', sans-serif;
        font-size: 1.5rem;
        font-weight: 600;
        color: white;
    }

    .cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
        gap: 1.5rem;
    }

    .card {
        background: var(--dark-card);
        border: 1px solid var(--border-color);
        border-radius: 24px;
        overflow: hidden;
        transition: all 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .card:hover {
        border-color: rgba(99, 102, 241, 0.4);
        transform: translateY(-6px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(99, 102, 241, 0.1);
    }

    .card-badge {
        position: absolute;
        top: 1rem;
        right: 1rem;
        z-index: 10;
        padding: 0.375rem 0.875rem;
        border-radius: 20px;
        font-size: 0.7rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        backdrop-filter: blur(8px);
    }

    .badge-pdf {
        background: rgba(244, 63, 94, 0.15);
        color: var(--danger);
        border: 1px solid rgba(244, 63, 94, 0.25);
    }

    .badge-image {
        background: rgba(99, 102, 241, 0.15);
        color: var(--primary);
        border: 1px solid rgba(99, 102, 241, 0.25);
    }

    .card-preview {
        height: 180px;
        background: #0a0f1a;
        position: relative;
        overflow: hidden;
    }

    .card-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .card:hover .card-preview img {
        transform: scale(1.05);
    }

    .card-preview iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .card-preview::after {
        content: "";
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 60%;
        background: linear-gradient(to top, var(--dark-card), transparent);
        pointer-events: none;
    }

    .card-body {
        padding: 1.25rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    .card-title {
        font-size: 1rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.5rem;
        line-height: 1.4;
    }

    .card-desc {
        color: var(--text-muted);
        font-size: 0.85rem;
        line-height: 1.6;
        margin-bottom: 1rem;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .card-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }

    .meta-tag {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        font-size: 0.75rem;
        color: var(--text-muted);
        background: rgba(255, 255, 255, 0.04);
        padding: 0.375rem 0.625rem;
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, 0.05);
    }

    .meta-tag i {
        color: var(--primary);
        font-size: 0.7rem;
    }

    .card-actions {
        margin-top: auto;
        display: flex;
        gap: 0.5rem;
        padding-top: 1rem;
        border-top: 1px solid var(--border-color);
    }

    .btn-card {
        flex: 1;
        padding: 0.625rem;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        font-size: 0.8rem;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.375rem;
        transition: all 0.25s ease;
        text-decoration: none;
    }

    .btn-view {
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
    }

    .btn-view:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(99, 102, 241, 0.35);
        color: white;
    }

    .btn-like {
        background: rgba(16, 185, 129, 0.12);
        color: var(--secondary);
    }

    .btn-like:hover {
        background: var(--secondary);
        color: white;
    }

    .btn-like.liked {
        background: var(--secondary);
        color: white;
    }

    .btn-comment {
        background: rgba(99, 102, 241, 0.12);
        color: var(--primary);
        flex: 0 0 44px;
    }

    .btn-comment:hover {
        background: var(--primary);
        color: white;
    }

    .comment-box {
        display: none;
        margin-top: 0.75rem;
    }

    .comment-box.active {
        display: block;
        animation: slideDown 0.3s ease;
    }

    @keyframes slideDown {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .comment-input {
        width: 100%;
        background: rgba(0, 0, 0, 0.2);
        border: 1px solid var(--border-color);
        border-radius: 10px;
        padding: 0.625rem;
        color: var(--text-light);
        font-size: 0.85rem;
        resize: none;
    }

    .comment-input::placeholder {
        color: var(--text-muted);
    }

    .comment-input:focus {
        outline: none;
        border-color: var(--primary);
    }

    .btn-send {
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        padding: 0.5rem 1rem;
        font-size: 0.8rem;
        font-weight: 500;
        cursor: pointer;
        margin-top: 0.5rem;
        transition: all 0.25s ease;
    }

    .btn-send:hover {
        background: var(--primary-hover);
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        background: var(--dark-card);
        border: 1px dashed var(--border-color);
        border-radius: 24px;
    }

    .empty-icon {
        width: 80px;
        height: 80px;
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.15), rgba(99, 102, 241, 0.05));
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        color: var(--primary);
        margin: 0 auto 1.5rem;
    }

    .empty-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: white;
        margin-bottom: 0.5rem;
    }

    .empty-text {
        color: var(--text-muted);
    }

    .fab {
        position: fixed;
        bottom: 2rem;
        right: 2rem;
        z-index: 50;
    }

    .fab-btn {
        width: 60px;
        height: 60px;
        border-radius: 18px;
        background: linear-gradient(135deg, var(--primary), var(--primary-hover));
        color: white;
        border: none;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        box-shadow: 0 8px 25px rgba(99, 102, 241, 0.4);
        transition: all 0.3s ease;
        text-decoration: none;
    }

    .fab-btn:hover {
        transform: scale(1.1) rotate(90deg);
        box-shadow: 0 12px 35px rgba(99, 102, 241, 0.5);
    }

    .fab-tooltip {
        position: absolute;
        right: 75px;
        top: 50%;
        transform: translateY(-50%);
        background: white;
        color: var(--dark-bg);
        padding: 0.5rem 1rem;
        border-radius: 10px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s ease;
    }

    .fab:hover .fab-tooltip {
        opacity: 1;
        right: 85px;
    }

    @media (max-width: 768px) {
        .navbar-custom .container {
            flex-direction: column;
            gap: 1rem;
        }
        .page-header {
            flex-direction: column;
            align-items: flex-start;
        }
        .cards-grid {
            grid-template-columns: 1fr;
        }
        .fab-btn {
            width: 54px;
            height: 54px;
            font-size: 1.25rem;
        }
    }
</style>
</head>

<body>

<nav class="navbar-custom">
    <div class="container">
        <div class="brand">
            <div class="brand-icon">
                <i class="bi bi-tree-fill"></i>
            </div>
            <div class="brand-text">
                <h1>Comunidad Callqui Chico</h1>
                <span>Sistema de Gestión Comunal</span>
            </div>
        </div>
        <div class="nav-links">
            <a href="../perfil_ajax.php" class="nav-btn" style="display:flex;align-items:center;gap:8px;">
    <img src="../../perfil/uploads/<?php echo $fotoPerfil; ?>" 
         style="width:32px;height:32px;border-radius:50%;object-fit:cover;">
    <span>Mi Perfil</span>
</a>
            <a href="../../index.html" class="nav-btn">
                <i class="bi bi-box-arrow-right"></i>
                <span>Salir</span>
            </a>
        </div>
    </div>
</nav>

<main class="main-wrapper">
    <div class="page-header">
        <div class="page-title">
            <i class="bi bi-file-earmark-text-fill"></i>
            <div>
                <h2>Actas Comunales</h2>
                <p>Documentos oficiales de la comunidad</p>
            </div>
        </div>
        <a href="comunero.php" class="back-btn">
            <i class="bi bi-arrow-left"></i>
            Volver
        </a>
    </div>

    <?php 
    $total_actas = $result ? $result->num_rows : 0;
    $pdf_count = 0;
    $img_count = 0;
    if ($result) {
        $result->data_seek(0);
        while($a = $result->fetch_assoc()) {
            $ext = strtolower(pathinfo($a['archivo'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg','jpeg','png','webp','gif'])) {
                $img_count++;
            } elseif ($ext === 'pdf') {
                $pdf_count++;
            }
        }
        $result->data_seek(0);
    }
    ?>

    <div class="stats-row">
        <div class="stat-item">
            <div class="stat-icon blue">
                <i class="bi bi-file-earmark-text"></i>
            </div>
            <div>
                <div class="stat-value"><?= $total_actas ?></div>
                <div class="stat-label">Total Actas</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon red">
                <i class="bi bi-file-pdf"></i>
            </div>
            <div>
                <div class="stat-value"><?= $pdf_count ?></div>
                <div class="stat-label">Documentos PDF</div>
            </div>
        </div>
        <div class="stat-item">
            <div class="stat-icon green">
                <i class="bi bi-image"></i>
            </div>
            <div>
                <div class="stat-value"><?= $img_count ?></div>
                <div class="stat-label">Imágenes</div>
            </div>
        </div>
    </div>

    <?php if ($result && $result->num_rows > 0): ?>
        <div class="cards-grid">
            <?php while($a = $result->fetch_assoc()): ?>
            <?php
            $archivo = $a['archivo'];
            $ext = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
            $esImagen = in_array($ext, ['jpg','jpeg','png','webp','gif']);
            $esPdf = ($ext === 'pdf');
            $badgeClass = $esPdf ? 'badge-pdf' : 'badge-image';
            $badgeText = $esPdf ? 'PDF' : strtoupper($ext);
            ?>
            <div class="card">
                <span class="card-badge <?= $badgeClass ?>">
                    <i class="bi bi-<?= $esPdf ? 'file-pdf' : 'file-image' ?>"></i>
                    <?= $badgeText ?>
                </span>
                
                <div class="card-preview">
                    <?php if ($esPdf): ?>
                        <iframe src="../../dashboard/uploads/<?= htmlspecialchars($archivo) ?>#toolbar=0&navpanes=0&scrollbar=0"></iframe>
                    <?php elseif($esImagen): ?>
                        <img src="../../dashboard/uploads/<?= htmlspecialchars($archivo) ?>" alt="Preview">
                    <?php else: ?>
                        <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--text-muted);">
                            <i class="bi bi-file-earmark" style="font-size:3rem;"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-body">
                    <h6 class="card-title"><?= htmlspecialchars($a['titulo']) ?></h6>
                    <p class="card-desc"><?= nl2br(htmlspecialchars($a['descripcion'] ?? 'Sin descripción')) ?></p>

                    <div class="card-meta">
                        <span class="meta-tag">
                            <i class="bi bi-calendar-event"></i>
                            <?= date("d/m/Y", strtotime($a['fecha'])) ?>
                        </span>
                        <span class="meta-tag">
                            <i class="bi bi-person-circle"></i>
                            <?= $a['nombres'] ? htmlspecialchars($a['nombres']) : 'Sistema' ?>
                        </span>
                    </div>

                    <div class="card-actions">
                        <a href="../../dashboard/uploads/<?= htmlspecialchars($archivo) ?>" target="_blank" class="btn-card btn-view">
                            <i class="bi bi-eye-fill"></i>
                            Ver
                        </a>
                        <button class="btn-card btn-like" onclick="darLike(<?= $a['id'] ?>)">
                            <i class="bi bi-hand-thumbs-up"></i>
                            <span id="likes-<?= $a['id'] ?>"><?= $a['total_likes'] ?></span>
                        </button>
                        <button class="btn-card btn-comment" onclick="toggleComment(<?= $a['id'] ?>)">
                            <i class="bi bi-chat-dots"></i>
                        </button>
                    </div>

                    <div class="comment-box" id="comment-box-<?= $a['id'] ?>">
                        <textarea class="comment-input" id="comment-text-<?= $a['id'] ?>" rows="2" placeholder="Escribe tu observación..."></textarea>
                        <button class="btn-send" onclick="enviarComentario(<?= $a['id'] ?>)">Enviar</button>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="bi bi-folder2-open"></i>
            </div>
            <h5 class="empty-title">No hay actas registradas</h5>
            <p class="empty-text">Aún no se han subido documentos al sistema.</p>
        </div>
    <?php endif; ?>
</main>

<?php if ($_SESSION['rol'] === 'secretario'): ?>
<div class="fab">
    <a href="../secretario/subir_acta.php" class="fab-btn">
        <i class="bi bi-plus-lg"></i>
    </a>
    <span class="fab-tooltip">Subir nueva acta</span>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function darLike(acta_id) {
    fetch('like_acta.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'acta_id=' + acta_id
    })
    .then(res => res.json())
    .then(data => {
        const btn = document.querySelector(`button[onclick="darLike(${acta_id})"]`);
        const count = document.getElementById('likes-' + acta_id);
        count.textContent = data.likes;
        btn.classList.toggle('liked');
    })
    .catch(() => {
        alert('Error al dar like');
    });
}

function toggleComment(id) {
    const box = document.getElementById('comment-box-' + id);
    box.classList.toggle('active');
}

function enviarComentario(acta_id) {
    const texto = document.getElementById('comment-text-' + acta_id).value;
    if (!texto.trim()) {
        alert('Escribe un comentario');
        return;
    }
    
    fetch('comentario_acta.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'acta_id=' + acta_id + '&comentario=' + encodeURIComponent(texto)
    })
    .then(res => res.json())
    .then(data => {
        alert('Comentario guardado');
        document.getElementById('comment-text-' + acta_id).value = '';
        document.getElementById('comment-box-' + acta_id).classList.remove('active');
    })
    .catch(() => {
        alert('Error al enviar comentario');
    });
}
</script>
</body>
</html>
