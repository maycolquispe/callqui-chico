# Callqui Chico - Sistema de Gestión Comunal

## Estructura del Proyecto

```
/2026
├── modules/              # Módulos del sistema (arquitectura por capas)
│   ├── Pagos/            # Módulo de pagos
│   ├── Certificados/     # Módulo de certificados
│   ├── Adjudicaciones/   # Módulo de adjudicaciones
│   ├── Usuarios/         # Módulo de usuarios
│   ├── Auth/             # Módulo de autenticación
│   └── Notificaciones/  # Módulo de notificaciones
│
├── api/                  # Endpoints API (puentes a modules)
│
├── config/               # Configuración centralizada
│
├── src/                  # Código reutilizable
│   ├── Utils/            # Utilidades
│   └── Services/         # Servicios globales
│
├── public/               # Zona pública (accesible sin login)
│
├── dashboard/            # Paneles de administración por rol
│   ├── presidente/
│   ├── secretario/
│   ├── tesorero/
│   ├── comunero/
│   ├── comite/
│   └── lotes/
│
├── storage/              # Archivos y uploads (reorganizado)
│   ├── uploads/
│   ├── documentos/
│   ├── documentos_firmados/
│   ├── certificados/
│   └── qr/
│
├── vendor/               # Bibliotecas externas
│   └── tcpdf/            # TCPDF (generación PDFs)
│
├── scripts/              # Scripts externos
│   └── python/           # Scripts Python (firma digital)
│
├── assets/               # Recursos estáticos
│   ├── css/
│   └── img/
│
├── sql/                  # Scripts de base de datos (referencia)
│
└── [archivos raíz]       # Archivos principales
```

## Estructura de un Módulo

Cada módulo sigue el patrón MVC:

```
modules/{Nombre}/
├── Controller.php    # Endpoints/API
├── Service.php       # Lógica de negocio
├── Repository.php    # Acceso a datos (SQL)
└── [otros archivos]  # Services adicionales, Traits, etc.
```

## Rutas de Acceso

- **API**: `/api/{archivo}.php` → Puente a `/modules/{Modulo}/Controller.php`
- **Dashboard**: `/dashboard/{rol}/{pagina}.php`
- **Público**: `/publico/{pagina}.php`

## Notas

- Los archivos en `/api/` son puentes de compatibilidad
- Los nuevos desarrollos deben ir en `/modules/`
- Los archivos de storage deben mantener permisos de escritura

## Archivos Eliminados (Limpieza)

- **API**: `debug_*.php`, `corregir_*.php`, `agregar_*.php`, `test_*.php`, `crear_*.php`, `buscar_mariluz.php`
- **Raíz**: `verificar_*.php`, `instalar_firma_digital.php`, `migracion_*.php`, `_nul`, `nul`, `cookies.txt`, `actualizar_password.php`, `crear_password.php`, `generar_pdf.php`, `exportar.php`
- **Dashboard**: `debug_session.php`
- **Carpetas duplicadas**: `certificados/`, `documentos/`, `documentos_firmados/`, `python/`, `css/`

## Imágenes del Sistema (assets/img/)

- `logo_peru.jpg` - Logo del Perú
- `logo_callqui.jpg` - Logo de la comunidad
- `logo_blanco_negro.jpg` - Logo para watermark
- `Guilloché.jpg` - Patrón de fondo
- `fondo_certificado.png` - Fondo para certificados