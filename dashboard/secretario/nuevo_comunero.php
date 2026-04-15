<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar Comunero | Callqui Chico</title>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
            display: flex;
            align-items: center;
            position: relative;
            overflow-x: hidden;
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

        /* Elementos decorativos */
        .decoration {
            position: fixed;
            width: 300px;
            height: 300px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--accent), var(--accent-light));
            filter: blur(100px);
            opacity: 0.15;
            z-index: -1;
        }

        .decoration-1 {
            top: -100px;
            left: -100px;
        }

        .decoration-2 {
            bottom: -100px;
            right: -100px;
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
        }

        .container {
            position: relative;
            z-index: 10;
        }

        /* Logo mejorado */
        .logo-wrapper {
            background: white;
            border-radius: 30px;
            padding: 30px 20px;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(201, 164, 91, 0.3);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .logo-wrapper:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 50px -10px rgba(10, 43, 60, 0.3);
        }

        .logo-wrapper::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(201, 164, 91, 0.1) 0%, transparent 70%);
            animation: rotate 15s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .logo-img {
            max-width: 150px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(10, 43, 60, 0.2);
            transition: transform 0.3s ease;
        }

        .logo-wrapper:hover .logo-img {
            transform: scale(1.05) rotate(3deg);
        }

        .logo-wrapper h5 {
            font-weight: 700;
            color: var(--primary);
            margin-top: 15px;
            position: relative;
            z-index: 10;
        }

        .logo-wrapper small {
            color: var(--accent);
            font-weight: 600;
            letter-spacing: 1px;
        }

        /* Card principal */
        .card-registro {
            border: none;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: all 0.3s ease;
            background: white;
        }

        .card-registro:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -10px rgba(10, 43, 60, 0.3);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            padding: 25px 30px;
            border-bottom: 3px solid var(--accent);
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
            background: radial-gradient(circle, rgba(201, 164, 91, 0.2) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        .card-header-custom h5 {
            color: white;
            font-weight: 700;
            font-size: 1.5rem;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
            z-index: 10;
        }

        .card-header-custom h5 i {
            background: var(--accent);
            color: var(--primary-dark);
            width: 45px;
            height: 45px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.5rem;
        }

        .card-body {
            padding: 30px;
        }

        /* Formulario */
        .form-label {
            font-weight: 600;
            color: var(--primary);
            margin-bottom: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .form-label i {
            color: var(--accent);
            font-size: 1rem;
        }

        .form-control, .form-select {
            border: 2px solid #e9ecef;
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 4px rgba(201, 164, 91, 0.15);
            background: white;
            outline: none;
        }

        /* Grupos de input con iconos */
        .input-group-custom {
            position: relative;
        }

        .input-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-light);
            pointer-events: none;
            transition: color 0.3s ease;
        }

        .form-control:focus ~ .input-icon {
            color: var(--accent);
        }

        /* Campos con validación visual */
        .form-control.is-valid {
            border-color: #10b981;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%2310b981' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23ef4444'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath stroke-linejoin='round' d='M5.8 3.6h.4L6 6.5z'/%3e%3ccircle cx='6' cy='8.2' r='.6' fill='%23ef4444' stroke='none'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }

        /* Botones */
        .btn-volver {
            background: #f8f9fa;
            color: var(--primary);
            border: 2px solid #e9ecef;
            padding: 12px 25px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-volver:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
            transform: translateX(-5px);
        }

        .btn-guardar {
            background: linear-gradient(135deg, var(--primary), var(--primary-light));
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 12px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(10, 43, 60, 0.3);
            position: relative;
            overflow: hidden;
        }

        .btn-guardar::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-guardar:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(10, 43, 60, 0.4);
        }

        .btn-guardar:hover::before {
            left: 100%;
        }

        /* Información adicional */
        .info-adicional {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 15px;
            margin-top: 20px;
            border: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-light);
        }

        .info-adicional i {
            color: var(--accent);
            font-size: 1.2rem;
        }

        .info-adicional small {
            font-size: 0.85rem;
        }

        /* Animaciones */
        [data-aos] {
            pointer-events: none;
        }

        [data-aos].aos-animate {
            pointer-events: auto;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }

            .logo-wrapper {
                margin-bottom: 30px;
            }

            .card-header-custom h5 {
                font-size: 1.2rem;
            }

            .btn-volver, .btn-guardar {
                padding: 10px 20px;
            }
        }

        /* Password strength indicator */
        .password-strength {
            height: 5px;
            background: #e9ecef;
            border-radius: 5px;
            margin-top: 8px;
            overflow: hidden;
        }

        .strength-bar {
            height: 100%;
            width: 0;
            transition: width 0.3s ease;
        }

        .strength-weak { width: 33%; background: #ef4444; }
        .strength-medium { width: 66%; background: #f59e0b; }
        .strength-strong { width: 100%; background: #10b981; }

        /* Tooltips */
        [data-tooltip] {
            position: relative;
            cursor: help;
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
            z-index: 1000;
        }

        [data-tooltip]:hover:before {
            opacity: 1;
            visibility: visible;
            bottom: 120%;
        }
    </style>
</head>
<body>

    <!-- Elementos decorativos -->
    <div class="decoration decoration-1"></div>
    <div class="decoration decoration-2"></div>

    <div class="container">
        <div class="row justify-content-center align-items-center g-5">

            <!-- LOGO MEJORADO -->
            <div class="col-md-4" data-aos="fade-right" data-aos-delay="200">
                <div class="logo-wrapper text-center">
                    <img src="../../assets/img/logo_callqui.png" class="logo-img mb-3" alt="Logo Comunidad">
                    <h5 class="fw-bold">Comunidad Campesina</h5>
                    <small>Callqui Chico</small>
                    <div class="mt-3">
                        <span class="badge bg-light text-dark p-2">
                            <i class="bi bi-tree-fill me-1" style="color: var(--accent);"></i>
                            Registro de Miembros
                        </span>
                    </div>
                </div>
            </div>

            <!-- FORMULARIO MEJORADO -->
            <div class="col-md-6" data-aos="fade-left" data-aos-delay="300">
                <div class="card-registro">
                    <div class="card-header-custom">
                        <h5>
                            <i class="bi bi-person-plus-fill"></i>
                            Registrar Nuevo Comunero
                        </h5>
                    </div>

                    <div class="card-body">
                        <form method="POST" action="guardar_comunero.php" id="registroForm" novalidate>

                            <!-- DNI -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person-badge"></i>
                                    DNI
                                </label>
                                <div class="input-group-custom">
                                    <input type="text" 
                                           name="dni" 
                                           class="form-control" 
                                           maxlength="8" 
                                           pattern="[0-9]{8}" 
                                           title="Ingrese 8 dígitos numéricos"
                                           placeholder="12345678"
                                           required
                                           data-tooltip="Ingrese DNI de 8 dígitos">
                                    <i class="bi bi-asterisk input-icon text-danger"></i>
                                </div>
                                <div class="form-text">Máximo 8 dígitos numéricos</div>
                            </div>

                            <!-- Nombres -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person"></i>
                                    Nombres
                                </label>
                                <input type="text" 
                                       name="nombres" 
                                       class="form-control" 
                                       placeholder="Ej: Juan Carlos"
                                       required
                                       data-tooltip="Ingrese nombres completos">
                            </div>

                            <!-- Apellidos -->
                            <div class="mb-3">
                                <label class="form-label">
                                    <i class="bi bi-person-badge"></i>
                                    Apellidos
                                </label>
                                <input type="text" 
                                       name="apellidos" 
                                       class="form-control" 
                                       placeholder="Ej: Pérez Mamani"
                                       required
                                       data-tooltip="Ingrese apellidos completos">
                            </div>

                            <!-- Rol / Cargo -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="bi bi-shield"></i>
                                    Rol / Cargo
                                </label>
                                <select name="rol" class="form-select" required data-tooltip="Seleccione el cargo en la comunidad">
                                    <option value="comunero">👤 Comunero</option>
                                    <option value="secretario">📝 Secretario</option>
                                    <option value="presidente">👑 Presidente</option>
                                    <option value="tesorero">💰 Tesorero</option>
                                    <option value="vocal">🎤 Vocal</option>
                                    <option value="fiscal">⚖️ Fiscal</option>
                                    <option value="comite_lotes">🏡 Comité de Lotes</option>
                                </select>
                            </div>

                            <!-- Información adicional -->
                            <div class="info-adicional">
                                <i class="bi bi-info-circle-fill"></i>
                                <small>Los campos marcados con <span class="text-danger">*</span> son obligatorios</small>
                            </div>

                            <!-- Botones -->
                            <div class="d-flex justify-content-between mt-4">
                                <a href="comuneros.php" class="btn-volver">
                                    <i class="bi bi-arrow-left"></i>
                                    Volver
                                </a>

                                <button type="submit" class="btn-guardar" id="submitBtn">
                                    <i class="bi bi-save"></i>
                                    Guardar Comunero
                                </button>
                            </div>

                            <!-- Mensaje de confirmación -->
                            <div class="text-center mt-3 d-none" id="loadingMessage">
                                <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
                                Registrando comunero...
                            </div>

                        </form>
                    </div>
                </div>
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

        // Validación en tiempo real
        const form = document.getElementById('registroForm');
        const dniInput = document.querySelector('input[name="dni"]');
        const nombresInput = document.querySelector('input[name="nombres"]');
        const apellidosInput = document.querySelector('input[name="apellidos"]');
        const submitBtn = document.getElementById('submitBtn');
        const loadingMessage = document.getElementById('loadingMessage');

        // Validar DNI (solo números)
        dniInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
            
            if (this.value.length === 8) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        });

        // Validar nombres (solo letras y espacios)
        nombresInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            
            if (this.value.length >= 3) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        });

        // Validar apellidos (solo letras y espacios)
        apellidosInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ\s]/g, '');
            
            if (this.value.length >= 3) {
                this.classList.add('is-valid');
                this.classList.remove('is-invalid');
            } else {
                this.classList.add('is-invalid');
                this.classList.remove('is-valid');
            }
        });

        // Validación antes de enviar
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            let isValid = true;

            // Validar DNI
            if (dniInput.value.length !== 8) {
                dniInput.classList.add('is-invalid');
                isValid = false;
            }

            // Validar nombres
            if (nombresInput.value.length < 3) {
                nombresInput.classList.add('is-invalid');
                isValid = false;
            }

            // Validar apellidos
            if (apellidosInput.value.length < 3) {
                apellidosInput.classList.add('is-invalid');
                isValid = false;
            }

            if (isValid) {
                // Mostrar loading
                submitBtn.disabled = true;
                loadingMessage.classList.remove('d-none');
                
                // Enviar formulario
                this.submit();
            } else {
                // Mostrar mensaje de error general
                alert('Por favor, complete todos los campos correctamente.');
            }
        });

        // Tooltips personalizados
        document.querySelectorAll('[data-tooltip]').forEach(element => {
            element.addEventListener('mouseenter', function(e) {
                // El CSS ya maneja el tooltip
            });
        });

        // Efecto de enfoque en campos
        document.querySelectorAll('.form-control, .form-select').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.querySelector('.input-icon')?.style.setProperty('color', 'var(--accent)');
            });

            input.addEventListener('blur', function() {
                this.parentElement.querySelector('.input-icon')?.style.setProperty('color', 'var(--text-light)');
            });
        });

        // Animación adicional para el botón guardar
        submitBtn.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });

        submitBtn.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });

        // Prevenir envío con Enter accidental
        window.addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && e.target.tagName !== 'TEXTAREA') {
                e.preventDefault();
            }
        });
    </script>

</body>
</html>