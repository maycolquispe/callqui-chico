<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Portal de Trámites - Consultar Estado</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

<style>
body{
    min-height:100vh;
    background: linear-gradient(135deg,#0f172a,#020617);
    color:#fff;
    font-family:'Segoe UI',sans-serif;
}

body::before{
    content:"";
    position:fixed;
    inset:0;
    background:url('../img/fondo_callqui.jpg') center/cover no-repeat;
    opacity:0.06;
    z-index:-1;
}

.glass{
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(12px);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:18px;
}

.input-dark{
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.2);
    color:#fff;
}

.input-dark::placeholder{
    color:rgba(255,255,255,0.6);
}

.result-card{
    background:rgba(0,0,0,0.3);
    border-radius:16px;
    padding:1.5rem;
    margin-top:1.5rem;
}

.badge-estado{
    padding:0.5rem 1rem;
    border-radius:50px;
    font-weight:600;
}
</style>
</head>

<body class="d-flex align-items-center justify-content-center">

<div class="container py-5">
    <div class="glass p-4 p-md-5" style="max-width:600px; margin:0 auto;">

        <div class="text-center mb-4">
            <a href="javascript:history.back()" class="btn btn-outline-light btn-sm position-absolute start-0 ms-3">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            <i class="bi bi-building" style="font-size:3rem; color:#c9a45c;"></i>
            <h2 class="fw-bold mt-3">Consulta de Trámites</h2>
            <small class="text-white-50">Comunidad Campesina Callqui Chico</small>
        </div>

        <!-- Buscador -->
        <div class="card glass p-4">
            <h5 class="mb-3 text-info"><i class="bi bi-search me-2"></i>Consultar estado de mi trámite</h5>

            <form method="GET" class="row g-2">
                <div class="col-md-9">
                    <input type="text" name="codigo" class="form-control form-control-lg input-dark" 
                           placeholder="Ingrese código (Ej. ADJ-2026-XXXXXX o PERM-2026-XXXXXX)" required>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-info w-100 btn-lg btn-rounded">Buscar</button>
                </div>
            </form>
        </div>

<?php
if (isset($_GET['codigo']) && !empty(trim($_GET['codigo']))) {
    require_once("../config/conexion.php");

    $codigo = trim($_GET['codigo']);
    $encontrado = false;
    
    // Buscar en permisos (código único)
    $sql = "SELECT id, estado, archivo, tipo_permiso as tipo, nombre_solicitante, dni_solicitante FROM permisos WHERE codigo_unico = ? OR codigo_unico LIKE ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $codigo_like = $codigo . '%';
        $stmt->bind_param("ss", $codigo, $codigo_like);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $encontrado = true;
            $row = $result->fetch_assoc();
            $estado = $row['estado'];
            $tipo = $row['tipo'];

            $color = match($estado){
                'Aprobado' => 'success',
                'Rechazado' => 'danger',
                default => 'warning'
            };

            $icon = match($estado){
                'Aprobado' => 'check-circle-fill',
                'Rechazado' => 'x-circle-fill',
                default => 'hourglass-split'
            };

            echo '<div class="result-card">';
            echo '<h6 class="mb-3 text-center">Estado del Trámite - Permiso</h6>';
            echo '<div class="text-center mb-3">';
            echo '<span class="badge bg-'.$color.' fs-6 px-3 py-2"><i class="bi bi-'.$icon.' me-1"></i> '.$estado.'</span>';
            echo '</div>';
            echo '<p class="text-white-50 text-center mb-0">Tipo: '.htmlspecialchars($tipo).'</p>';
            
            // Buscar cargo PDF
            $cargo_file = 'documentos/cargos/cargo_' . $codigo . '.pdf';
            $cargo_path = __DIR__ . '/' . $cargo_file;
            if (file_exists($cargo_path)) {
                echo '<div class="text-center mt-3">';
                echo '<a href="' . $cargo_file . '" class="btn btn-success" target="_blank">';
                echo '<i class="bi bi-download me-2"></i>Descargar Cargo';
                echo '</a>';
                echo '</div>';
            }
            
            if($estado == 'Pendiente'){
                echo '<p class="text-white-50 text-center mt-2 small">Su solicitud está en revisión.</p>';
            }

            echo '</div>';
        }

        $stmt->close();
    }
    
    // Buscar en adjudicaciones (código de seguimiento)
    if (!$encontrado) {
        $sql2 = "SELECT id, estado, certificado, pdf_firmado, nombre, dni, lote, manzana, area_m2 FROM adjudicaciones WHERE codigo_seguimiento = ? OR codigo = ?";
        
        if ($stmt2 = $conn->prepare($sql2)) {
            $stmt2->bind_param("ss", $codigo, $codigo);
            $stmt2->execute();
            $result2 = $stmt2->get_result();

            if ($result2->num_rows > 0) {
                $encontrado = true;
                $row = $result2->fetch_assoc();
                $estado = $row['estado'];

                $color = match($estado){
                    'aprobado' => 'success',
                    'aprobado_total' => 'success',
                    'certificado_generado' => 'primary',
                    'rechazado' => 'danger',
                    default => 'warning'
                };

                $icon = match($estado){
                    'aprobado' => 'check-circle-fill',
                    'aprobado_total' => 'check-circle-fill',
                    'certificado_generado' => 'award-fill',
                    'rechazado' => 'x-circle-fill',
                    default => 'hourglass-split'
                };
                
                $estadoLabel = match($estado){
                    'pendiente' => 'Pendiente',
                    'en_revision' => 'En Revisión',
                    'aprobado' => 'Aprobado',
                    'aprobado_total' => 'Aprobado Total',
                    'certificado_generado' => 'Certificado Generado',
                    'rechazado' => 'Rechazado',
                    default => $estado
                };

                echo '<div class="result-card">';
                echo '<h6 class="mb-3 text-center">Estado del Trámite - Adjudicación</h6>';
                echo '<div class="text-center mb-3">';
                echo '<span class="badge bg-'.$color.' fs-6 px-3 py-2"><i class="bi bi-'.$icon.' me-1"></i> '.$estadoLabel.'</span>';
                echo '</div>';
                echo '<div class="row text-start">';
                echo '<div class="col-6 mb-2"><small class="text-white-50">Solicitante</small><p class="mb-0">'.htmlspecialchars($row['nombre']).'</p></div>';
                echo '<div class="col-6 mb-2"><small class="text-white-50">DNI</small><p class="mb-0">'.htmlspecialchars($row['dni']).'</p></div>';
                echo '<div class="col-4 mb-2"><small class="text-white-50">Lote</small><p class="mb-0">'.htmlspecialchars($row['lote']).'</p></div>';
                echo '<div class="col-4 mb-2"><small class="text-white-50">Manzana</small><p class="mb-0">'.htmlspecialchars($row['manzana'] ?? 'N/A').'</p></div>';
                echo '<div class="col-4 mb-2"><small class="text-white-50">Área</small><p class="mb-0">'.htmlspecialchars($row['area_m2'] ?? $row['area']).' m²</p></div>';
                echo '</div>';

                if(($estado == 'certificado_generado' || $estado == 'aprobado_total') && (!empty($row['certificado']) || !empty($row['pdf_firmado']))){
                    echo '<div class="text-center mt-3">';
                    echo '<a href="descargar_certificado.php?codigo='.$codigo.'" class="btn btn-primary" target="_blank">';
                    echo '<i class="bi bi-eye me-2"></i>Ver Certificado';
                    echo '</a>';
                    echo '</div>';
                }

                echo '</div>';
            }

            $stmt2->close();
        }
    }
    
    if (!$encontrado) {
        echo '<div class="alert alert-danger mt-4">Código no encontrado. Verifique e intente nuevamente.</div>';
    }
}
?>

        <div class="text-center mt-4">
            <small class="text-white-50">© 2026 Comunidad Callqui Chico</small>
        </div>

    </div>
</div>

</body>
</html>