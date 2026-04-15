<?php 
require_once 'config/session.php';
require_once 'config/csrf.php';
SessionManager::init();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sistema Comunal Callqui Chico</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2563eb;
            --primary-dark: #1e40af;
            --primary-light: #60a5fa;
            --secondary: #10b981;
            --dark-bg: #0a1928;
        }

        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Fondo con imagen y overlay */
        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('./assets/img/fondo_callqui.jpg') no-repeat center center fixed;
            background-size: cover;
            filter: blur(3px) brightness(0.7);
            transform: scale(1.05);
            z-index: -2;
        }

        body::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(145deg, rgba(10,25,40,0.8) 0%, rgba(11,30,47,0.9) 100%);
            z-index: -1;
        }

        /* Partículas animadas */
        .particles {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: hidden;
            z-index: 0;
        }

        .particle {
            position: absolute;
            display: block;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            animation: float 15s infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) rotate(720deg);
                opacity: 0;
            }
        }

        /* Tarjeta de login moderna */
        .login-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 420px;
            padding: 1rem;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 2.5rem 2rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255,255,255,0.3);
            transform: translateY(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .login-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.6);
        }

        /* Logo y título */
        .logo-wrapper {
            text-align: center;
            margin-bottom: 2rem;
        }

        .logo {
            width: 110px;
            height: 110px;
            margin-bottom: 1.2rem;
            border-radius: 28px;
            box-shadow: 0 10px 25px rgba(37,99,235,0.3);
            transition: transform 0.3s ease;
            background: white;
            padding: 5px;
        }

        .logo:hover {
            transform: scale(1.05) rotate(3deg);
        }

        .title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e293b;
            margin-bottom: 0.3rem;
        }

        .subtitle {
            color: #64748b;
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Formulario */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .input-group {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            z-index: 10;
            transition: color 0.3s ease;
        }

        .form-control {
            width: 100%;
            padding: 0.9rem 1rem 0.9rem 45px;
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(37,99,235,0.1);
            outline: none;
        }

        .form-control:focus + .input-icon {
            color: var(--primary);
        }

        .floating-label {
            position: absolute;
            left: 45px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            pointer-events: none;
            transition: all 0.3s ease;
            background: transparent;
            padding: 0 5px;
        }

        .form-control:focus ~ .floating-label,
        .form-control:not(:placeholder-shown) ~ .floating-label {
            top: 0;
            font-size: 0.75rem;
            color: var(--primary);
            background: white;
        }

        /* Botón */
        .btn-login {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #2563eb, #1e40af);
            border: none;
            border-radius: 16px;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.8rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin-top: 1rem;
        }

        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.5s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(37,99,235,0.4);
        }

        .btn-login:hover::before {
            left: 100%;
        }

        .btn-login i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .btn-login:hover i {
            transform: translateX(5px);
        }

        /* Enlaces */
        .forgot-password {
            text-align: center;
            margin-top: 1.5rem;
        }

        .forgot-password a {
            color: #64748b;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: color 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
        }

        .forgot-password a:hover {
            color: var(--primary);
        }

        .forgot-password a i {
            font-size: 0.8rem;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 2rem;
            color: rgba(255,255,255,0.7);
            font-size: 0.85rem;
            position: relative;
            z-index: 10;
        }

        /* Mensajes de error */
        .error-message {
            background: rgba(239,68,68,0.1);
            border: 1px solid rgba(239,68,68,0.3);
            color: #ef4444;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            backdrop-filter: blur(5px);
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-card {
                padding: 2rem 1.5rem;
            }

            .logo {
                width: 90px;
                height: 90px;
            }

            .title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>

    <!-- Partículas animadas -->
    <div class="particles" id="particles"></div>

    <div class="login-container">
        <div class="login-card">
            
            <!-- Logo y título -->
            <div class="logo-wrapper">
                <img src="assets/img/logo_callqui.png" class="logo" alt="Logo Callqui Chico">
                <h1 class="title">Bienvenido</h1>
                <p class="subtitle">Sistema de Gestión Comunal</p>
            </div>

            <!-- Mensaje de error (se muestra si hay error en login) -->
            <?php if(isset($_GET['error'])): ?>
                <div class="error-message">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <?php 
                        $errores = [
                            '1' => 'DNI o contraseña incorrectos',
                            '2' => 'Debe completar todos los campos',
                            'csrf' => 'Token de seguridad inválido',
                            'sesion_expirada' => 'Su sesión ha expirado',
                            'bloqueado' => 'Cuenta temporalmente bloqueada'
                        ];
                        $error_msg = $errores[$_GET['error']] ?? 'Error en el sistema';
                        
                        // Agregar tiempo de bloqueo si existe
                        if (isset($_GET['tiempo'])) {
                            $error_msg .= '. Intente en ' . intval($_GET['tiempo']) . ' minutos';
                        }
                        
                        echo htmlspecialchars($error_msg);
                    ?>
                </div>
            <?php endif; ?>

            <!-- Formulario de login -->
            <form method="POST" action="./config/validar_login.php">
                <?php echo csrf_field(); ?>
                <div class="form-group">
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="bi bi-person-badge"></i>
                        </span>
                        <input type="text" 
                               name="dni" 
                               class="form-control" 
                               placeholder=" " 
                               maxlength="8" 
                               pattern="[0-9]{8}" 
                               title="Ingrese 8 dígitos"
                               required>
                        <span class="floating-label">DNI</span>
                    </div>
                </div>

                <div class="form-group">
                    <div class="input-group">
                        <span class="input-icon">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input type="password" 
                               name="password" 
                               class="form-control" 
                               placeholder=" " 
                               required>
                        <span class="floating-label">Contraseña</span>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <span>Ingresar al Sistema</span>
                    <i class="bi bi-arrow-right-short"></i>
                </button>

                <div class="forgot-password">
                    <a href="#">
                        <i class="bi bi-question-circle"></i>
                        ¿Olvidaste tu contraseña?
                    </a>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <i class="bi bi-tree-fill me-1"></i>
            Comunidad Campesina Callqui Chico
            <br>
            <small>© 2026 - Todos los derechos reservados</small>
        </div>
    </div>

    <!-- Script para crear partículas animadas -->
    <script>
        function createParticles() {
            const particlesContainer = document.getElementById('particles');
            const numberOfParticles = 50;

            for (let i = 0; i < numberOfParticles; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                
                // Tamaño aleatorio
                const size = Math.random() * 5 + 2;
                particle.style.width = `${size}px`;
                particle.style.height = `${size}px`;
                
                // Posición inicial aleatoria
                particle.style.left = `${Math.random() * 100}%`;
                particle.style.bottom = `-${Math.random() * 20}px`;
                
                // Duración de animación aleatoria
                const duration = Math.random() * 10 + 10;
                particle.style.animation = `float ${duration}s linear infinite`;
                particle.style.animationDelay = `${Math.random() * 5}s`;
                
                particlesContainer.appendChild(particle);
            }
        }

        // Ejecutar cuando cargue la página
        window.onload = createParticles;
    </script>

    <!-- Bootstrap JS (opcional, solo para funcionalidades) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>