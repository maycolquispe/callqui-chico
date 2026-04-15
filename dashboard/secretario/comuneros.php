<?php
require_once '../../includes/verificar_sesion.php';

$conn = getDB();
$result = $conn->query("SELECT * FROM usuarios ORDER BY padron ASC");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Comuneros | Callqui Chico</title>

    <!-- Bootstrap 5.3 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- DataTables -->
    <link href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- AOS Animations -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #0a2b3c;
            --primary-dark: #06212e;
            --primary-light: #1e4a6a;
            --accent: #c9a45b;
            --accent-light: #dbb67b;
            --accent-dark: #a88642;
            --bg-page: #f2efe6;
            --text-dark: #1e2b37;
            --text-light: #5a6b7a;
            --white: #ffffff;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
        }

        body {
            background-color: var(--bg-page);
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            min-height: 100vh;
            position: relative;
        }

        /* Fondo con textura */
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

        /* Header mejorado */
        .page-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 20px 0;
            margin-bottom: 30px;
            border-bottom: 3px solid var(--accent);
            box-shadow: var(--shadow-lg);
        }

        .header-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header-title h2 {
            color: white;
            font-weight: 700;
            font-size: 1.8rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .header-title h2 i {
            color: var(--accent);
        }

        .header-title p {
            color: rgba(255,255,255,0.7);
            margin: 0;
            font-size: 0.95rem;
        }

        .header-actions {
            display: flex;
            gap: 15px;
        }

        /* Botones */
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

        .btn-new {
            background: var(--accent);
            color: var(--primary-dark);
            padding: 10px 25px;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.2);
        }

        .btn-new:hover {
            background: var(--accent-light);
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(201, 164, 91, 0.3);
        }

        /* Contenedor principal */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 30px 40px;
        }

        /* Stats cards */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 20px;
            box-shadow: var(--shadow-md);
            display: flex;
            align-items: center;
            gap: 15px;
            transition: all 0.3s ease;
            border: 1px solid rgba(201, 164, 91, 0.1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
            border-color: var(--accent);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
            color: var(--primary-dark);
        }

        .stat-info h4 {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
            line-height: 1;
        }

        .stat-info p {
            color: var(--text-light);
            margin: 5px 0 0;
            font-size: 0.9rem;
            font-weight: 500;
        }

        /* Tarjeta principal */
        .card-admin {
            background: white;
            border: none;
            border-radius: 24px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .card-header-custom {
            padding: 25px 30px;
            background: linear-gradient(135deg, #f8f9fa, white);
            border-bottom: 2px solid var(--accent);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-header-custom h5 {
            font-weight: 700;
            color: var(--primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header-custom h5 i {
            color: var(--accent);
            font-size: 1.5rem;
        }

        .search-wrapper {
            position: relative;
            width: 300px;
        }

        .search-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
        }

        .search-wrapper input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e9ecef;
            border-radius: 12px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .search-wrapper input:focus {
            border-color: var(--accent);
            outline: none;
            box-shadow: 0 0 0 3px rgba(201, 164, 91, 0.2);
        }

        /* Tabla */
        .table {
            margin: 0;
        }

        .table thead th {
            background: #f8f9fa;
            color: var(--text-light);
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 18px 20px;
            border-bottom: 2px solid #e9ecef;
        }

        .table tbody td {
            padding: 18px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #e9ecef;
        }

        .table tbody tr {
            transition: all 0.3s ease;
        }

        .table tbody tr:hover {
            background: #f8f9fa;
            transform: scale(1.01);
            box-shadow: var(--shadow-sm);
        }

        /* Avatar mejorado */
        .avatar-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .avatar {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.2rem;
            box-shadow: 0 5px 10px rgba(10, 43, 60, 0.2);
        }

        .avatar-img {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            object-fit: cover;
            border: 2px solid var(--accent);
        }

        .user-info {
            display: flex;
            flex-direction: column;
        }

        .user-name {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 3px;
        }

        .user-meta {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        /* Badges */
        .badge-role {
            background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
            color: var(--primary-dark);
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .badge-role i {
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .status-active {
            background: linear-gradient(135deg, #dcfce7, #bbf7d0);
            color: #166534;
        }

        .status-inactive {
            background: linear-gradient(135deg, #fee2e2, #fecaca);
            color: #991b1b;
        }

        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }

        .btn-action {
            width: 38px;
            height: 38px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            border: none;
            font-size: 1rem;
        }

        .btn-edit {
            background: #eef2ff;
            color: #4f46e5;
        }

        .btn-edit:hover {
            background: #4f46e5;
            color: white;
            transform: translateY(-3px);
        }

        .btn-view {
            background: #e0f2fe;
            color: #0284c7;
        }

        .btn-view:hover {
            background: #0284c7;
            color: white;
            transform: translateY(-3px);
        }

        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }

        .btn-delete:hover {
            background: #dc2626;
            color: white;
            transform: translateY(-3px);
        }

        /* Paginación */
        .pagination-info {
            padding: 20px 30px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: var(--text-light);
            font-size: 0.9rem;
        }

        /* Responsive */
        @media (max-width: 992px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .card-header-custom {
                flex-direction: column;
                gap: 15px;
            }

            .search-wrapper {
                width: 100%;
            }

            .action-buttons {
                justify-content: center;
            }
        }

        /* Loading spinner */
        .loading-spinner {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
        }

        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Tooltips */
        [data-tooltip] {
            position: relative;
            cursor: pointer;
        }

        [data-tooltip]:before {
            content: attr(data-tooltip);
            position: absolute;
            bottom: 100%;
            left: 50%;
            transform: translateX(-50%);
            padding: 8px 12px;
            background: var(--primary);
            color: white;
            border-radius: 8px;
            font-size: 0.75rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
            z-index: 100;
        }

        [data-tooltip]:hover:before {
            opacity: 1;
            visibility: visible;
            bottom: 120%;
        }
    </style>
</head>

<body>

    <!-- Loading Spinner -->
    <div class="loading-spinner" id="loadingSpinner">
        <div class="spinner"></div>
    </div>

    <!-- Header mejorado -->
    <header class="page-header" data-aos="fade-down">
        <div class="header-container">
            <div class="header-title">
                <h2>
                    <i class="bi bi-people-fill"></i>
                    Gestión de Comuneros
                </h2>
                <p><i class="bi bi-tree-fill me-1" style="color: var(--accent);"></i> Comunidad Campesina Callqui Chico</p>
            </div>
            <div class="header-actions">
                <a href="secretario.php" class="btn-back" data-tooltip="Volver al panel">
                    <i class="bi bi-arrow-left"></i>
                    <span>Volver</span>
                </a>
                <a href="nuevo_comunero.php" class="btn-new" data-tooltip="Registrar nuevo comunero">
                    <i class="bi bi-person-plus"></i>
                    <span>Nuevo Comunero</span>
                </a>
            </div>
        </div>
    </header>

    <div class="main-container">

        <!-- Estadísticas -->
        <?php
        $total = $conn->query("SELECT COUNT(*) as total FROM usuarios")->fetch_assoc()['total'];
        $activos = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE estado='activo'")->fetch_assoc()['total'];
        $comuneros = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol='comunero'")->fetch_assoc()['total'];
        $directivos = $conn->query("SELECT COUNT(*) as total FROM usuarios WHERE rol!='comunero'")->fetch_assoc()['total'];
        ?>

        <div class="stats-row" data-aos="fade-up" data-aos-delay="100">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-people"></i>
                </div>
                <div class="stat-info">
                    <h4><?= $total ?></h4>
                    <p>Total Comuneros</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h4><?= $activos ?></h4>
                    <p>Comuneros Activos</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-person-badge"></i>
                </div>
                <div class="stat-info">
                    <h4><?= $comuneros ?></h4>
                    <p>Comuneros Base</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="bi bi-star"></i>
                </div>
                <div class="stat-info">
                    <h4><?= $directivos ?></h4>
                    <p>Directivos</p>
                </div>
            </div>
        </div>

        <!-- Tarjeta principal -->
        <div class="card-admin" data-aos="fade-up" data-aos-delay="200">
            <div class="card-header-custom">
                <h5>
                    <i class="bi bi-grid-1x2-fill"></i>
                    Listado General de Comuneros
                </h5>
                <div class="search-wrapper">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchInput" placeholder="Buscar por nombre, DNI o cargo...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table" id="comunerosTable">
                    <thead>
                        <tr>
                            <th>Padrón</th>
                            <th>DNI</th>
                            <th>Comunero</th>
                            <th>Cargo</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php 
                        $result->data_seek(0);
                        while($c = $result->fetch_assoc()): 
                        ?>
                        <tr>
                            <td class="fw-semibold text-center"><?= $c['padron'] ?? '-' ?></td>
                            <td class="fw-semibold"><?= htmlspecialchars($c['dni']) ?></td>

                            <td>
                                <div class="avatar-wrapper">
                                    <?php if(!empty($c['foto'])): ?>
                                        <?php 
                                        $ruta_foto = '';
                                        if (file_exists(__DIR__ . '/../../perfil/uploads/' . $c['foto'])) {
                                            $ruta_foto = '../../perfil/uploads/' . $c['foto'];
                                        } elseif (file_exists(__DIR__ . '/../../storage/uploads/' . $c['foto'])) {
                                            $ruta_foto = '../../storage/uploads/' . $c['foto'];
                                        } elseif (file_exists(__DIR__ . '/../uploads/' . $c['foto'])) {
                                            $ruta_foto = '../uploads/' . $c['foto'];
                                        }
                                        ?>
                                        <?php if($ruta_foto): ?>
                                            <img src="<?= $ruta_foto ?>" class="avatar-img" alt="Avatar">
                                        <?php else: ?>
                                            <div class="avatar">
                                                <?= strtoupper(substr($c['nombres'],0,1).substr($c['apellidos'],0,1)) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <div class="avatar">
                                            <?= strtoupper(substr($c['nombres'],0,1).substr($c['apellidos'],0,1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="user-info">
                                        <span class="user-name"><?= htmlspecialchars($c['nombres'].' '.$c['apellidos']) ?></span>
                                        <span class="user-meta">
                                            <i class="bi bi-envelope"></i> <?= htmlspecialchars($c['correo'] ?? 'Sin correo') ?>
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="badge-role">
                                    <?php 
                                    $iconos = [
                                        'presidente' => 'bi-star-fill',
                                        'secretario' => 'bi-file-text-fill',
                                        'tesorero' => 'bi-cash-stack',
                                        'vocal' => 'bi-mic',
                                        'comunero' => 'bi-person'
                                    ];
                                    $icono = $iconos[$c['rol']] ?? 'bi-person';
                                    ?>
                                    <i class="bi <?= $icono ?>"></i>
                                    <?= strtoupper(htmlspecialchars($c['rol'])) ?>
                                </span>
                            </td>

                            <td>
                                <?php if($c['estado'] == 'activo'): ?>
                                    <span class="status-badge status-active">
                                        <i class="bi bi-check-circle-fill"></i>
                                        ACTIVO
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge status-inactive">
                                        <i class="bi bi-x-circle-fill"></i>
                                        INACTIVO
                                    </span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div class="action-buttons">
                                    <a href="ver_comunero.php?id=<?= $c['id'] ?>" 
                                       class="btn-action btn-view" 
                                       data-tooltip="Ver detalles">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="editar_comunero.php?id=<?= $c['id'] ?>" 
                                       class="btn-action btn-edit" 
                                       data-tooltip="Editar comunero">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn-action btn-delete" 
                                            data-tooltip="Eliminar"
                                            onclick="confirmarEliminacion(<?= $c['id'] ?>, '<?= htmlspecialchars($c['nombres'].' '.$c['apellidos']) ?>')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>

            <div class="pagination-info">
                <div>
                    <i class="bi bi-people me-1"></i>
                    Total: <strong><?= $total ?></strong> comuneros registrados
                </div>
                <div>
                    <span class="me-3"><i class="bi bi-check-circle-fill text-success"></i> Activos: <?= $activos ?></span>
                    <span><i class="bi bi-x-circle-fill text-danger"></i> Inactivos: <?= $total - $activos ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación -->
    <div class="modal fade" id="confirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Estás seguro de eliminar al comunero <strong id="comuneroName"></strong>?
                    <p class="text-danger mt-2"><small>Esta acción no se puede deshacer.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <a href="#" id="confirmDeleteBtn" class="btn btn-danger">Eliminar</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Inicializar AOS
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-out-cubic'
        });

        // Inicializar DataTable
        $(document).ready(function() {
            $('#comunerosTable').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                },
                pageLength: 10,
                order: [[1, 'asc']],
                dom: 'rtip'
            });
        });

        // Búsqueda personalizada
        document.getElementById('searchInput').addEventListener('keyup', function() {
            $('#comunerosTable').DataTable().search(this.value).draw();
        });

        // Función de confirmación
        function confirmarEliminacion(id, nombre) {
            document.getElementById('comuneroName').textContent = nombre;
            document.getElementById('confirmDeleteBtn').href = 'eliminar_comunero.php?id=' + id;
            new bootstrap.Modal(document.getElementById('confirmModal')).show();
        }

        // Mostrar loading
        window.addEventListener('beforeunload', function() {
            document.getElementById('loadingSpinner').style.display = 'block';
        });

        window.addEventListener('load', function() {
            document.getElementById('loadingSpinner').style.display = 'none';
        });

        // Tooltips personalizados (los data-tooltip ya funcionan con CSS)
    </script>

</body>
</html>