<?php
/**
 * Footer Template
 * Sistema Callqui Chico - Profesional
 */
?>
    
    <!-- Footer -->
    <footer class="footer-callqui">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0 text-muted">
                        <i class="bi bi-tree-fill me-1 text-success"></i>
                        Comunidad Campesina Callqui Chico
                        <span class="mx-2">|</span>
                        <small>&copy; <?php echo date('Y'); ?> - Todos los derechos reservados</small>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <small class="text-muted">
                        Sistema de Gestión Comunal v2.0
                    </small>
                </div>
            </div>
        </div>
    </footer>

    <style>
        .footer-callqui {
            background: var(--white);
            border-top: 1px solid #e2e8f0;
            padding: 1rem 0;
            margin-top: auto;
        }
    </style>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- CSRF Ajax Setup -->
    <script>
        // Agregar token CSRF a todas las peticiones AJAX
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
        
        if (csrfToken) {
            // Interceptar fetch
            const originalFetch = window.fetch;
            window.fetch = function(...args) {
                let [url, options = {}] = args;
                
                if (options.method && ['POST', 'PUT', 'DELETE'].includes(options.method.toUpperCase())) {
                    options.headers = options.headers || {};
                    options.headers['X-CSRF-Token'] = csrfToken;
                }
                
                return originalFetch.apply(this, args);
            };
            
            // Agregar token a formularios AJAX
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.dataset.ajax === 'true') {
                    const csrfInput = form.querySelector('input[name="csrf_token"]');
                    if (!csrfInput && csrfToken) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'csrf_token';
                        input.value = csrfToken;
                        form.appendChild(input);
                    }
                }
            });
        }
        
        // Función helper para mostrar alertas
        function mostrarAlerta(mensaje, tipo = 'info') {
            const alertas = document.querySelector('.alert-container');
            if (!alertas) return;
            
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${mensaje}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            alertas.appendChild(alertDiv);
            
            setTimeout(() => alertDiv.remove(), 5000);
        }
        
        // Confirmar acciones peligrosas
        function confirmarAccion(mensaje) {
            return confirm(mensaje || '¿Está seguro de realizar esta acción?');
        }
    </script>
</body>
</html>
