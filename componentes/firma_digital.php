<?php
/**
 * Componente de Firma Digital Visual
 * Genera HTML con estilos inline para TCPDF/DomPDF
 * Diseño limpio y profesional tipo certificado
 */

function renderizarFirmaHTML($firmas, $opciones = []) {
    $opciones = array_merge([
        'mostrar_logo' => true,
        'logo_path' => __DIR__ . '/../assets/img/logo_callqui.jpg'
    ], $opciones);
    
    // Colores
    $color_dorado = '#c9a45c';
    $color_negro = '#1a1a1a';
    $color_gris = '#555555';
    
    // Logo - intentar base64 si existe
    $logo_html = '';
    if ($opciones['mostrar_logo'] && !empty($opciones['logo_path'])) {
        $ruta_logo = $opciones['logo_path'];
        if (file_exists($ruta_logo)) {
            $extension = strtolower(pathinfo($ruta_logo, PATHINFO_EXTENSION));
            $mime = ($extension === 'png') ? 'image/png' : 'image/jpeg';
            $datos_binario = file_get_contents($ruta_logo);
            if ($datos_binario) {
                $logo_base64 = 'data:' . $mime . ';base64,' . base64_encode($datos_binario);
                $logo_html = '<img src="' . $logo_base64 . '" style="width: 14px; height: 14px; display: block; margin: 0 auto 1px;">';
            }
        }
    }
    
    // Si no se pudo cargar el logo, usar fallback
    if (empty($logo_html)) {
        $logo_html = '<div style="font-family: Arial, sans-serif; font-size: 5px; color: ' . $color_negro . '; margin-bottom: 1px;">
            <span style="display: inline-block; width: 12px; height: 12px; background: ' . $color_dorado . '; border-radius: 50%; color: white; line-height: 12px; font-weight: bold; font-size: 6px;">CC</span>
        </div>';
    }
    $firmas = array_slice($firmas, 0, 4);
    
    $html = '
    <table style="width: 100%; border-collapse: collapse; margin-top: 2px;" cellpadding="0">
        <tr>
    ';
    
    $ancho_celda = count($firmas) > 0 ? (100 / count($firmas)) . '%' : '25%';
    
    foreach ($firmas as $firma) {
        $nombre = strtoupper($firma['nombre'] ?? 'NOMBRE COMPLETO');
        $dni = $firma['dni'] ?? 'DNI';
        $cargo = $firma['cargo'] ?? 'CARGO';
        $fecha = $firma['fecha'] ?? date('d/m/Y H:i');
        
        $html .= '
            <td style="width: ' . $ancho_celda . '; vertical-align: top; padding: 2px 5px; text-align: center;">
                ' . $logo_html . '
                <div style="font-family: Arial, sans-serif; font-size: 3.5px; color: ' . $color_gris . '; line-height: 1.0;">
                    <div><strong>Por:</strong> ' . htmlspecialchars($nombre) . '</div>
                    <div><strong>DNI:</strong> ' . htmlspecialchars($dni) . '</div>
                    <div><strong>Fecha:</strong> ' . htmlspecialchars($fecha) . '</div>
                </div>
                <div style="border-top: 1px solid ' . $color_dorado . '; margin: 0px 4px;"></div>
                <div style="font-family: Arial, sans-serif; font-size: 4px; font-weight: bold; color: ' . $color_negro . ';">
                    ' . htmlspecialchars($nombre) . '
                </div>
                <div style="font-family: Arial, sans-serif; font-size: 4px; color: ' . $color_gris . '; font-style: italic;">
                    ' . htmlspecialchars($cargo) . '
                </div>
            </td>
        ';
    }
    
    $html .= '
        </tr>
    </table>
    ';
    
    return $html;
}

function getCargoPorRol($rol) {
    $cargos = [
        'tesorero' => 'EL TESORERO',
        'comite_lotes' => 'COMITÉ DE LOTES',
        'secretario' => 'EL SECRETARIO',
        'presidente' => 'EL PRESIDENTE'
    ];
    return $cargos[$rol] ?? strtoupper($rol);
}

function obtenerFirmasParaCertificado($conn, $excluir_rol = null) {
    $firmas = [];
    
    // Orden: Tesorero, Comité, Secretario, Presidente
    $orden_roles = ['tesorero', 'comite_lotes', 'secretario', 'presidente'];
    
    // Obtener usuarios
    $stmt = $conn->prepare("SELECT id, nombres, apellidos, dni, rol FROM usuarios WHERE rol IN ('tesorero', 'comite_lotes', 'secretario', 'presidente')");
    $stmt->execute();
    $result = $stmt->get_result();
    $usuarios_db = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios_db[$row['rol']] = $row;
    }
    $stmt->close();
    
    // Mapear según orden
    foreach ($orden_roles as $rol) {
        // Excluir rol si se especifica
        if ($excluir_rol && $rol === $excluir_rol) {
            continue;
        }
        
        if (isset($usuarios_db[$rol])) {
            $usr = $usuarios_db[$rol];
            $firmas[] = [
                'nombre' => trim($usr['nombres'] . ' ' . $usr['apellidos']),
                'dni' => $usr['dni'],
                'cargo' => getCargoPorRol($rol),
                'fecha' => date('d/m/Y H:i'),
                'rol_key' => $rol
            ];
        }
    }
    
    return $firmas;
}
