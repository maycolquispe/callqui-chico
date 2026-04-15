<?php 
require_once "../../config/config.php"; 

// Verificar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar permisos
if (!isset($_SESSION['rol']) || $_SESSION['rol'] != 'secretario') {
    header("Location: ../login.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subir Acta | Comunidad Callqui Chico</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --dark-bg: #0a1928;
            --dark-card: #0f2740;
            --text-light: #f0f5fa;
            --text-muted: #94a3b8;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.03);
            --shadow-md: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 20px 40px -10px rgba(0, 0, 0, 0.15);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(145deg, #0a1928 0%, #0b1e2f 100%);
            min-height: 100vh;
            color: var(--text-light);
            position: relative;
            overflow-x: hidden;
            display: flex;
            align-items: center;
        }

        /* Efecto de fondo */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 20% 30%, rgba(201,164,91,0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 70%, rgba(16,185,129,0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        /* Partículas animadas */
        .particle {
            position: fixed;
            width: 3px;
            height: 3px;
            background: rgba(201, 164, 91, 0.3);
            border-radius: 50%;
            pointer-events: none;
            animation: float-particle 15s infinite linear;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }

        /* Contenedor principal */
        .main-container {
            width: 100%;
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1.5rem;
            position: relative;
            z-index: 10;
        }

        /* Barra de navegación superior */
        .top-bar {
            background: rgba(10, 25, 40, 0.95);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 0.8rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: var(--shadow-lg);
        }

        .logo-mini {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo-mini i {
            font-size: 1.8rem;
            color: var(--accent);
        }

        .logo-mini span {
            font-weight: 600;
            color: white;
        }

        .user-badge {
            background: rgba(255,255,255,0.05);
            padding: 0.5rem 1.2rem;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--accent-light);
            border: 1px solid rgba(255,255,255,0.1);
        }

        /* Tarjeta principal */
        .upload-card {
            background: rgba(15, 39, 64, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            border: 1px solid rgba(255,255,255,0.1);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            animation: slideUp 0.6s ease;
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
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            padding: 2rem;
            border-bottom: 3px solid var(--accent);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .card-header-custom::before {
            content: "";
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(201,164,91,0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .header-icon {
            width: 80px;
            height: 80px;
            background: var(--accent);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--primary-dark);
            font-size: 2.5rem;
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 10;
        }

        .card-header-custom h2 {
            color: white;
            font-weight: 800;
            font-size: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 10;
        }

        .card-header-custom p {
            color: var(--text-muted);
            margin: 0;
            font-size: 1rem;
            position: relative;
            z-index: 10;
        }

        .card-body {
            padding: 2.5rem;
        }

        /* Formulario */
        .form-label {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }

        .form-label i {
            color: var(--accent);
            font-size: 1rem;
        }

        .form-control, .form-select {
            background: rgba(255,255,255,0.05);
            border: 2px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 0.8rem 1.2rem;
            color: white;
            transition: all 0.3s ease;
            font-size: 0.95rem;
        }

        .form-control:focus, .form-select:focus {
            background: rgba(255,255,255,0.1);
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(201,164,91,0.2);
            outline: none;
            color: white;
        }

        .form-control::placeholder {
            color: var(--text-muted);
        }

        /* Área de texto */
        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        /* Input file personalizado */
        .file-upload-area {
            background: rgba(255,255,255,0.03);
            border: 2px dashed rgba(201,164,91,0.3);
            border-radius: 20px;
            padding: 2rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 1.5rem;
        }

        .file-upload-area:hover {
            border-color: var(--accent);
            background: rgba(201,164,91,0.05);
        }

        .file-upload-area i {
            font-size: 3rem;
            color: var(--accent);
            margin-bottom: 1rem;
        }

        .file-upload-area h5 {
            color: white;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .file-upload-area p {
            color: var(--text-muted);
            margin-bottom: 0;
            font-size: 0.9rem;
        }

        .file-info {
            display: none;
            background: rgba(16,185,129,0.1);
            border: 1px solid rgba(16,185,129,0.3);
            border-radius: 12px;
            padding: 1rem;
            margin-top: 1rem;
            color: #10b981;
            align-items: center;
            gap: 0.8rem;
        }

        .file-info i {
            font-size: 1.5rem;
        }

        /* Botones */
        .btn-volver {
            background: rgba(255,255,255,0.05);
            color: white;
            padding: 0.8rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.3s ease;
            border: 1px solid rgba(255,255,255,0.1);
            font-weight: 500;
        }

        .btn-volver:hover {
            background: var(--accent);
            color: var(--primary-dark);
            transform: translateX(-5px);
        }

        .btn-subir {
            background: linear-gradient(135deg, var(--accent), var(--accent-dark));
            color: var(--primary-dark);
            border: none;
            padding: 0.8rem 2.5rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 0.8rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(201,164,91,0.3);
        }

        .btn-subir:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(201,164,91,0.4);
            background: linear-gradient(135deg, var(--accent-light), var(--accent));
        }

        /* Información adicional */
        .info-box {
            background: rgba(255,255,255,0.03);
            border-radius: 16px;
            padding: 1rem;
            margin-top: 2rem;
            border: 1px solid rgba(255,255,255,0.1);
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .info-box i {
            font-size: 1.5rem;
            color: var(--accent);
        }

        .info-box p {
            margin: 0;
            color: var(--text-muted);
            font-size: 0.9rem;
        }

        .info-box strong {
            color: white;
        }

        /* Requisitos */
        .requirements {
            display: flex;
            gap: 1rem;
            margin-top: 1rem;
            flex-wrap: wrap;
        }

        .requirement {
            background: rgba(255,255,255,0.03);
            padding: 0.5rem 1rem;
            border-radius: 30px;
            font-size: 0.8rem;
            color: var(--text-muted);
            border: 1px solid rgba(255,255,255,0.1);
        }

        .requirement i {
            color: var(--accent);
            margin-right: 0.3rem;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .top-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }

            .card-header-custom h2 {
                font-size: 1.5rem;
            }

            .card-body {
                padding: 1.5rem;
            }

            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
            }

            .btn-volver, .btn-subir {
                width: 100%;
                justify-content: center;
            }
        }

        /* Animaciones */
        .form-group {
            animation: fadeIn 0.5s ease forwards;
            opacity: 0;
        }

        .form-group:nth-child(1) { animation-delay: 0.1s; }
        .form-group:nth-child(2) { animation-delay: 0.2s; }
        .form-group:nth-child(3) { animation-delay: 0.3s; }
        .form-group:nth-child(4) { animation-delay: 0.4s; }

        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }

        /* Loading spinner */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
            z-index: 9999;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 1rem;
        }

        .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid rgba(201,164,91,0.3);
            border-top: 5px solid var(--accent);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading-text {
            color: white;
            font-weight: 600;
        }
    </style>
</head>
<body>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
        <div class="loading-text">Subiendo acta...</div>
    </div>

    <!-- Partículas animadas -->
    <div id="particles"></div>

    <div class="main-container">

        <!-- Barra superior -->
        <div class="top-bar" data-aos="fade-down">
            <div class="logo-mini">
                <i class="bi bi-tree-fill"></i>
                <span>Comunidad Callqui Chico</span>
            </div>
            <div class="user-badge">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Secretario</span>
            </div>
        </div>

        <!-- Tarjeta principal -->
        <div class="upload-card" data-aos="fade-up" data-aos-delay="100">

            <div class="card-header-custom">
                <div class="header-icon">
                    <i class="bi bi-file-earmark-arrow-up-fill"></i>
                </div>
                <h2>Subir Acta Comunal</h2>
                <p>Digitaliza y archiva las actas de reuniones</p>
            </div>

            <div class="card-body">
                <form action="guardar_acta.php" method="POST" enctype="multipart/form-data" id="uploadForm">

                    <!-- Título -->
                    <div class="form-group mb-4">
                        <label class="form-label">
                            <i class="bi bi-pencil-fill"></i>
                            Título del Acta
                        </label>
                        <input type="text" name="titulo" class="form-control" 
                               placeholder="Ej: Asamblea General Ordinaria - Marzo 2026" 
                               required>
                    </div>

                    <!-- Descripción -->
                    <div class="form-group mb-4">
                        <label class="form-label">
                            <i class="bi bi-text-paragraph"></i>
                            Descripción
                        </label>
                        <textarea name="descripcion" class="form-control" 
                                  placeholder="Breve descripción del contenido del acta..."></textarea>
                    </div>

                    <!-- Fecha -->
                    <div class="form-group mb-4">
                        <label class="form-label">
                            <i class="bi bi-calendar-event-fill"></i>
                            Fecha del Acta
                        </label>
                        <input type="date" name="fecha" class="form-control" 
                               value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <!-- Archivo - Área mejorada -->
                    <div class="form-group mb-4">
                        <label class="form-label">
                            <i class="bi bi-file-earmark-pdf-fill"></i>
                            Archivo (PDF o Imagen)
                        </label>
                        
                        <div class="file-upload-area" id="fileUploadArea">
                            <i class="bi bi-cloud-upload"></i>
                            <h5>Haga clic o arrastre el archivo</h5>
                            <p>Formatos permitidos: PDF, JPG, JPEG, PNG</p>
                            <p class="small mt-2">Tamaño máximo: 10 MB</p>
                            <div class="file-info" id="fileInfo">
                                <i class="bi bi-check-circle-fill"></i>
                                <span id="fileName"></span>
                            </div>
                        </div>
                        
                        <input type="file" name="archivo" id="fileInput" 
                               class="d-none" 
                               accept=".pdf,.jpg,.jpeg,.png" required>
                        
                        <div class="requirements">
                            <span class="requirement">
                                <i class="bi bi-file-pdf"></i> PDF
                            </span>
                            <span class="requirement">
                                <i class="bi bi-file-image"></i> JPG
                            </span>
                            <span class="requirement">
                                <i class="bi bi-file-image"></i> PNG
                            </span>
                            <span class="requirement">
                                <i class="bi bi-hdd"></i> Máx 10MB
                            </span>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <a href="secretario.php" class="btn-volver">
                            <i class="bi bi-arrow-left"></i>
                            Volver al Panel
                        </a>
                        <button type="submit" class="btn-subir" id="submitBtn">
                            <i class="bi bi-cloud-arrow-up-fill"></i>
                            Subir Acta
                        </button>
                    </div>

                    <!-- Información adicional -->
                    <div class="info-box">
                        <i class="bi bi-info-circle-fill"></i>
                        <p>
                            <strong>Importante:</strong> Las actas subidas estarán disponibles 
                            para todos los comuneros en la sección "Actas Comunales".
                        </p>
                    </div>

                </form>
            </div>

        </div>

    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <script>
        // Inicializar AOS
        AOS.init({
            duration: 800,
            once: true,
            easing: 'ease-out-cubic'
        });

        // Crear partículas
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 10 + 's';
                particle.style.animationDuration = Math.random() * 10 + 10 + 's';
                particlesContainer.appendChild(particle);
            }
        }
        createParticles();

        // Manejo del área de archivo
        const fileInput = document.getElementById('fileInput');
        const fileUploadArea = document.getElementById('fileUploadArea');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');

        fileUploadArea.addEventListener('click', () => {
            fileInput.click();
        });

        fileUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            fileUploadArea.style.borderColor = 'var(--accent)';
            fileUploadArea.style.background = 'rgba(201,164,91,0.1)';
        });

        fileUploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            fileUploadArea.style.borderColor = 'rgba(201,164,91,0.3)';
            fileUploadArea.style.background = 'rgba(255,255,255,0.03)';
        });

        fileUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            fileUploadArea.style.borderColor = 'rgba(201,164,91,0.3)';
            fileUploadArea.style.background = 'rgba(255,255,255,0.03)';
            
            const files = e.dataTransfer.files;
            if (files.length) {
                fileInput.files = files;
                actualizarNombreArchivo(files[0].name);
            }
        });

        fileInput.addEventListener('change', function() {
            if (this.files.length) {
                actualizarNombreArchivo(this.files[0].name);
            }
        });

        function actualizarNombreArchivo(nombre) {
            fileName.textContent = nombre;
            fileInfo.style.display = 'flex';
            
            // Validar extensión
            const ext = nombre.split('.').pop().toLowerCase();
            if (!['pdf','jpg','jpeg','png'].includes(ext)) {
                alert('Formato no válido. Solo se permiten PDF, JPG y PNG.');
                fileInput.value = '';
                fileInfo.style.display = 'none';
            }
            
            // Validar tamaño (10MB)
            if (fileInput.files[0] && fileInput.files[0].size > 10 * 1024 * 1024) {
                alert('El archivo no puede ser mayor a 10MB');
                fileInput.value = '';
                fileInfo.style.display = 'none';
            }
        }

        // Validación del formulario
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Validar campos
            const titulo = document.querySelector('input[name="titulo"]').value.trim();
            if (!titulo) {
                alert('Por favor ingrese un título para el acta');
                return;
            }
            
            if (!fileInput.files.length) {
                alert('Por favor seleccione un archivo');
                return;
            }
            
            // Mostrar loading
            document.getElementById('loadingOverlay').style.display = 'flex';
            
            // Enviar formulario
            this.submit();
        });

        // Atajo de teclado: Ctrl+S para guardar
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.getElementById('submitBtn').click();
            }
        });

        // Mensaje de confirmación al salir
        window.addEventListener('beforeunload', function(e) {
            if (fileInput.files.length > 0) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>

</body>
</html>
