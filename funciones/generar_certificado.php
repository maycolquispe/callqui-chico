<?php
/**
 * Funciones para Generación de Certificados - Comunidad Campesina Callqui Chico
 * Certificado tipo diploma profesional con diseño elegante
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Genera el PDF del certificado de adjudicación con diseño profesional tipo diploma
 */
function generarCertificadoPDF($adjudicacion_id) {
    $conn = getDB();
    
    require_once __DIR__ . '/../componentes/firma_digital.php';
    
    // Obtener datos de la adjudicacion
    $sql = "SELECT a.*, u.nombres as nombres_titular, u.apellidos as apellidos_titular, u.dni as dni_titular
            FROM adjudicaciones a
            LEFT JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $adjudicacion_id);
    $stmt->execute();
    $adj = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$adj) {
        return ['success' => false, 'message' => 'Adjudicación no encontrada'];
    }
    
    // Generar código único
    $año = date('Y');
    $codigo = 'ADJ-' . $año . '-' . str_pad($adjudicacion_id, 4, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("UPDATE adjudicaciones SET codigo_certificado = ? WHERE id = ?");
    $stmt->bind_param("si", $codigo, $adjudicacion_id);
    $stmt->execute();
    $stmt->close();
    
    $adj['codigo_certificado'] = $codigo;
    
    // Datos del adjudicatario
    $apellidos = trim($adj['apellidos_titular'] ?? $adj['nombre'] ?? '');
    $nombres = trim($adj['nombres_titular'] ?? '');
    $nombre = $apellidos . ($nombres ? ', ' . $nombres : '');
    
    $dni = $adj['dni_titular'] ?? $adj['dni'] ?? '';
    $sector = $adj['sector'] ?? 'CHUÑURANRA';
    
    $manzana = $adj['manzana'] ?? '';
    if ($manzana == '0' || $manzana == '' || $manzana == '-') {
        $manzana = '';
    }
    
    $lote = $adj['lote'] ?? '-';
    $area = $adj['area_m2'] ?? $adj['area'] ?? '0';
    
    // Linderos con metros
    $lindero_frente = !empty(trim($adj['lindero_frente'])) ? trim($adj['lindero_frente']) : '_______________________';
    $lindero_fondo = !empty(trim($adj['lindero_fondo'])) ? trim($adj['lindero_fondo']) : '_______________________';
    $lindero_derecha = !empty(trim($adj['lindero_derecha'])) ? trim($adj['lindero_derecha']) : '_______________________';
    $lindero_izquierda = !empty(trim($adj['lindero_izquierda'])) ? trim($adj['lindero_izquierda']) : '_______________________';
    
    $metros_frente = !empty($adj['metros_frente']) ? $adj['metros_frente'] : '____';
    $metros_fondo = !empty($adj['metros_fondo']) ? $adj['metros_fondo'] : '____';
    $metros_derecha = !empty($adj['metros_derecha']) ? $adj['metros_derecha'] : '____';
    $metros_izquierda = !empty($adj['metros_izquierda']) ? $adj['metros_izquierda'] : '____';
    
    // Fecha automática en español
    $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
    $fecha_emision = date('d') . ' de ' . $meses[date('n')] . ' de ' . date('Y');
    
    // Colores
    $color_dorado = [201, 164, 92];
    $color_beige = [245, 240, 230];
    $color_gris_claro = [240, 240, 240];
    $color_gris_oscuro = [80, 80, 80];
    
    // Generar PDF usando TCPDF (nueva ubicación)
    require_once __DIR__ . '/../vendor/tcpdf/tcpdf.php';
    
    $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
    $pdf->SetCreator('Callqui Chico');
    $pdf->SetAuthor('Comunidad Campesina Callqui Chico');
    $pdf->SetTitle('Certificado de Adjudicación - ' . $codigo);
    $pdf->SetMargins(20, 20, 20);
    $pdf->SetAutoPageBreak(true, 15);
    
    // Colores - estilo institucional moderno
    $color_dorado = [201, 164, 92];
    $color_fondo = [252, 250, 247];  // Blanco/beige muy claro
    $color_guilloché = [200, 200, 200];  // Guilloché gris muy suave (opacidad ~8%)
    $color_gris_oscuro = [80, 80, 80];
    $color_linea_institucion = [180, 180, 180];  // Línea gris sutil
    
    // ==================== PÁGINA 1: CERTIFICADO INSTITUCIONAL ====================
    $pdf->AddPage();
    
    // Fondo limpio (blanco/beige muy claro)
    $pdf->SetFillColor($color_fondo[0], $color_fondo[1], $color_fondo[2]);
    $pdf->Rect(0, 0, 210, 297, 'F');
    
    // Línea decorativa sutil en la parte superior
    $pdf->SetDrawColor($color_linea_institucion[0], $color_linea_institucion[1], $color_linea_institucion[2]);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, 25, 190, 25);
    
    // --- FONDO DE AGUA (WATERMARK) - Opacidad 7% ---
    $logoWatermark = __DIR__ . '/../assets/img/logo_blanco_negro.jpg';
    if (file_exists($logoWatermark)) {
        $pdf->SetAlpha(0.07);  // 7%
        $pdf->Image($logoWatermark, 50, 90, 120, 120, 'JPG');
        $pdf->SetAlpha(1);
    }
    
    // --- IMAGEN GUILLOCHÉ (cubriendo toda la hoja) ---
    $guillochéImg = __DIR__ . '/../assets/img/Guilloché.jpg';
    if (file_exists($guillochéImg)) {
        $pdf->SetAlpha(0.10);  // 10% opacidad
        $pdf->Image($guillochéImg, 5, 20, 200, 260, 'JPG');  // Cubrir toda la hoja A4
        $pdf->SetAlpha(1);
    }
    
    // Círculos concéntricos sutiles - esquina superior izquierda (8 círculos)
    $centro_x = 0;
    $centro_y = 0;
    for ($radio = 10; $radio <= 70; $radio += 10) {
        $pdf->Circle($centro_x, $centro_y, $radio, 0, 360, 'D');
    }
    
    // Logos institucionales
    $logoPeru = __DIR__ . '/../assets/img/logo_peru.jpg';
    $logoCallqui = __DIR__ . '/../assets/img/logo_callqui.jpg';
    
    if (file_exists($logoPeru)) {
        $pdf->Image($logoPeru, 20, 20, 14, 14, 'JPEG');
    }
    if (file_exists($logoCallqui)) {
        $pdf->Image($logoCallqui, 176, 20, 14, 14, 'JPEG');
    }
    
    // Encabezado - TAMAÑO AUMENTADO
    $pdf->SetFont('times', 'B', 12);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 6, 'EN NOMBRE DE LA COMUNIDAD', 0, 1, 'C');
    
    $pdf->SetFont('times', 'B', 14);
    $pdf->Cell(0, 7, 'COMUNIDAD CAMPESINA DE CALLQUI CHICO', 0, 1, 'C');
    
    $pdf->SetFont('times', '', 11);
    $pdf->Cell(0, 5, 'El Presidente de la Comunidad Campesina de Callqui Chico', 0, 1, 'C');
    
    $pdf->Ln(5);
    $pdf->SetFont('times', '', 10);
    $pdf->MultiCell(165, 4.5, 'Por cuanto: La Junta Directiva de la Comunidad Campesina de Callqui Chico, en sesión ordinaria de fecha _______________, ha acordado adjudicar el siguiente lote conforme a los acuerdos comunales y normas internas vigentes:', 0, 'J');
    
    $pdf->Ln(8);
    // TÍTULO DORADO - MÁS GRANDE Y RESALTANTE
    $pdf->SetFont('times', 'B', 20);
    $pdf->SetTextColor($color_dorado[0], $color_dorado[1], $color_dorado[2]);
    $pdf->Cell(0, 10, 'CERTIFICADO DE ADJUDICACIÓN', 0, 1, 'C');
    $pdf->SetTextColor(0, 0, 0);
    
    $pdf->Ln(6);
    // DATOS DEL ADJUDICATARIO - TAMAÑO AUMENTADO
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 6, 'A favor de:', 0, 1, 'L');
    
    $pdf->SetFont('times', '', 12);
    $pdf->Cell(0, 6, strtoupper($nombre), 0, 1, 'L');
    $pdf->Cell(0, 6, 'Identificada con DNI N° ' . $dni, 0, 1, 'L');
    
    // DATOS DEL TERRENO
    $pdf->Ln(5);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 6, 'El lote ubicado en el sector: ' . strtoupper($sector), 0, 1, 'L');
    
    $pdf->Ln(3);
    $pdf->SetFont('times', '', 11);
    
    if ($manzana) {
        $pdf->Cell(55, 6, 'Manzana: ' . strtoupper($manzana), 0, 0);
    }
    $pdf->Cell(45, 6, 'Lote: ' . strtoupper($lote), 0, 0);
    $pdf->Cell(0, 6, 'Área total: ' . $area . ' m²', 0, 1);
    
    // ==================== COLINDANCIAS ====================
    $pdf->Ln(6);
    $pdf->SetFont('times', 'B', 12);
    $pdf->Cell(0, 6, 'Colindancias del lote:', 0, 1, 'L');
    
    $pdf->SetFont('times', '', 11);
    
    $pdf->Cell(22, 5, '', 0, 0);
    $pdf->SetFont('times', 'B', 11);
    $pdf->Cell(28, 5, 'FRENTE:', 0, 0);
    $pdf->SetFont('times', '', 11);
    $pdf->MultiCell(0, 5, 'colinda con ' . strtoupper($lindero_frente) . ' (' . $metros_frente . ' ml)', 0, 'L');
    
    $pdf->Cell(22, 5, '', 0, 0);
    $pdf->SetFont('times', 'B', 11);
    $pdf->Cell(28, 5, 'FONDO:', 0, 0);
    $pdf->SetFont('times', '', 11);
    $pdf->MultiCell(0, 5, 'colinda con ' . strtoupper($lindero_fondo) . ' (' . $metros_fondo . ' ml)', 0, 'L');
    
    $pdf->Cell(22, 5, '', 0, 0);
    $pdf->SetFont('times', 'B', 11);
    $pdf->Cell(28, 5, 'DERECHA:', 0, 0);
    $pdf->SetFont('times', '', 11);
    $pdf->MultiCell(0, 5, 'colinda con ' . strtoupper($lindero_derecha) . ' (' . $metros_derecha . ' ml)', 0, 'L');
    
    $pdf->Cell(22, 5, '', 0, 0);
    $pdf->SetFont('times', 'B', 11);
    $pdf->Cell(28, 5, 'IZQUIERDA:', 0, 0);
    $pdf->SetFont('times', '', 11);
    $pdf->MultiCell(0, 5, 'colinda con ' . strtoupper($lindero_izquierda) . ' (' . $metros_izquierda . ' ml)', 0, 'L');
    
    // TEXTO LEGAL
    $pdf->Ln(6);
    $pdf->SetFont('times', '', 10);
    $pdf->MultiCell(165, 4.5, 'El presente lote ha sido adjudicado en reconocimiento de los derechos comunales que le corresponden, conforme a los usos y costumbres, asi como al estatuto y acuerdos adoptados en Asamblea General de la Comunidad Campesina de Callqui Chico, en concordancia con la Ley N.º 24656 y demas normas legales vigentes.', 0, 'J');
    
    $pdf->Ln(4);
    $pdf->MultiCell(165, 4.5, 'La presente adjudicacion acredita la posesion directa, pacifica, continua y publica del lote descrito. Asimismo, deja constancia de que dicha posesion ha sido otorgada por la Comunidad, sin perjuicio de los derechos que pudieran corresponder a terceros y sujeta al cumplimiento de las disposiciones internas de la organizacion comunal.', 0, 'J');
    
    $pdf->Ln(4);
    $pdf->SetFont('times', 'B', 10);
    $pdf->Cell(0, 4.5, 'POR TANTO:', 0, 1, 'L');
    
    $pdf->SetFont('times', '', 10);
    $pdf->MultiCell(165, 4.5, 'Se expide el presente CERTIFICADO DE ADJUDICACION a favor de la interesada, a fin de que se le reconozca como legitima posesionaria del lote descrito, con todos los derechos y obligaciones que establece la Comunidad Campesina de Callqui Chico. El presente documento se emite para los fines legales que estime convenientes.', 0, 'J');
    
    $pdf->Ln(6);
    $pdf->SetFont('times', '', 11);
    $pdf->Cell(0, 6, 'Callqui Chico, ' . $fecha_emision, 0, 1, 'L');
    
    // ==================== FIRMAS VISUALES CON TCPDF NATIVO ====================
    $pdf->Ln(8);
    $y_inicio = $pdf->GetY();
    
    $firmas = obtenerFirmasParaCertificado($conn, 'comite_lotes');
    
    if (!empty($firmas)) {
        $logo_path = __DIR__ . '/../assets/img/logo_callqui.jpg';
        
        $color_dorado = [201, 164, 92];
        $color_negro = [26, 26, 26];
        $color_gris = [85, 85, 85];
        
        $ancho_pagina = 210;
        $margen = 20;
        $espacio_disponible = $ancho_pagina - (2 * $margen);
        $num_firmas = min(count($firmas), 4);
        $ancho_celda = $espacio_disponible / $num_firmas;
        
        $pdf->SetDrawColor($color_dorado[0], $color_dorado[1], $color_dorado[2]);
        $pdf->SetTextColor($color_gris[0], $color_gris[1], $color_gris[2]);
        $pdf->SetFont('times', '', 6);
        
        for ($i = 0; $i < $num_firmas; $i++) {
            $f = $firmas[$i];
            $x_inicio = $margen + ($i * $ancho_celda);
            $x_centro = $x_inicio + ($ancho_celda / 2);
            
            // Logo comunidad
            if (file_exists($logo_path)) {
                $pdf->Image($logo_path, $x_centro - 4, $y_inicio, 8, 8, 'JPG');
            }
            
            $y_logo = $y_inicio + 9;
            
            // Datos: Por, DNI, Fecha
            $pdf->SetXY($x_inicio, $y_logo);
            $pdf->SetFont('times', '', 5.5);
            $pdf->Cell($ancho_celda, 3, 'Por: ' . strtoupper($f['nombre']), 0, 1, 'C');
            $pdf->SetX($x_inicio);
            $pdf->Cell($ancho_celda, 3, 'DNI: ' . $f['dni'], 0, 1, 'C');
            $pdf->SetX($x_inicio);
            $pdf->Cell($ancho_celda, 3, 'Fecha: ' . $f['fecha'], 0, 1, 'C');
            
            $y_datos = $pdf->GetY();
            
            // Línea divisoria
            $pdf->SetLineWidth(0.3);
            $pdf->Line($x_inicio + 3, $y_datos + 1, $x_inicio + $ancho_celda - 3, $y_datos + 1);
            
            // Nombre debajo de línea
            $pdf->SetTextColor($color_negro[0], $color_negro[1], $color_negro[2]);
            $pdf->SetFont('times', 'B', 5.5);
            $pdf->SetXY($x_inicio, $y_datos + 2);
            $pdf->Cell($ancho_celda, 3, strtoupper($f['nombre']), 0, 1, 'C');
            
            // Cargo
            $pdf->SetTextColor($color_gris[0], $color_gris[1], $color_gris[2]);
            $pdf->SetFont('times', 'I', 5);
            $pdf->SetXY($x_inicio, $pdf->GetY());
            $pdf->Cell($ancho_celda, 3, $f['cargo'], 0, 1, 'C');
        }
        
        $pdf->SetTextColor(0, 0, 0);
    }
    
    // ==================== PÁGINA 2: REGISTRO OFICIAL ====================
    $pdf->AddPage();
    
    // Fondo limpio
    $pdf->SetFillColor($color_fondo[0], $color_fondo[1], $color_fondo[2]);
    $pdf->Rect(0, 0, 210, 297, 'F');
    
    // Línea decorativa sutil
    $pdf->SetDrawColor($color_linea_institucion[0], $color_linea_institucion[1], $color_linea_institucion[2]);
    $pdf->SetLineWidth(0.5);
    $pdf->Line(20, 25, 190, 25);
    
    // --- FONDO DE AGUA (WATERMARK) - Opacidad 7% ---
    $logoWatermark = __DIR__ . '/../assets/img/logo_blanco_negro.jpg';
    if (file_exists($logoWatermark)) {
        $pdf->SetAlpha(0.07);  // 7%
        $pdf->Image($logoWatermark, 45, 80, 120, 120, 'JPG');
        $pdf->SetAlpha(1);
    }
    
    // --- IMAGEN GUILLOCHÉ (cubriendo toda la hoja) ---
    $guillochéImg = __DIR__ . '/../assets/img/Guilloché.jpg';
    if (file_exists($guillochéImg)) {
        $pdf->SetAlpha(0.10);  // 10% opacidad
        $pdf->Image($guillochéImg, 5, 20, 200, 260, 'JPG');  // Cubrir toda la hoja A4
        $pdf->SetAlpha(1);
    }
    
    // Círculos concéntricos sutiles - esquina inferior derecha página 2 (8 círculos)
    $centro_x2 = 210;
    $centro_y2 = 297;
    for ($radio = 10; $radio <= 70; $radio += 10) {
        $pdf->Circle($centro_x2, $centro_y2, $radio, 0, 360, 'D');
    }
    
    // Título
    $pdf->Ln(10);
    $pdf->SetFont('times', 'B', 13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(0, 7, 'REGISTRO OFICIAL DEL CERTIFICADO', 0, 1, 'C');
    
    $pdf->SetFont('times', 'B', 11);
    $pdf->Cell(0, 5, 'COMUNIDAD CAMPESINA DE CALLQUI CHICO', 0, 1, 'C');
    $pdf->SetFont('times', '', 10);
    $pdf->Cell(0, 5, 'SECRETARÍA GENERAL – COMITÉ DE LOTES', 0, 1, 'C');
    
    // ==================== TABLA DEL REGISTRO (bordes finos gris oscuro) ====================
    $pdf->Ln(8);
    $y_inicio_tabla = $pdf->GetY();
    
    // Color de bordes (gris oscuro)
    $color_borde_tabla = [60, 60, 60];
    $pdf->SetDrawColor($color_borde_tabla[0], $color_borde_tabla[1], $color_borde_tabla[2]);
    $pdf->SetLineWidth(0.5);  // Bordes finos 0.5px
    
    // Fondo blanco de la tabla
    $pdf->SetFillColor(255, 255, 255);
    
    // Ancho de columnas: 85mm cada una
    $col1_x = 20;
    $col2_x = 105;
    $ancho_col = 85;
    $alto_fila = 6;
    
    // ===== FILA 1: Código comunidad | Tipo documento =====
    $pdf->Rect($col1_x, $y_inicio_tabla, $ancho_col, $alto_fila);  // Borde izq
    $pdf->Rect($col2_x, $y_inicio_tabla, $ancho_col, $alto_fila);  // Borde der
    $pdf->SetFont('times', '', 10);
    $pdf->SetXY($col1_x + 3, $y_inicio_tabla + 1.5);
    $pdf->Cell(0, 4, 'Código de la comunidad:', 0, 0);
    $pdf->SetXY($col1_x + 55, $y_inicio_tabla + 1.5);
    $pdf->Cell(0, 4, 'CCCH-001', 0, 1);
    $pdf->SetXY($col2_x + 3, $y_inicio_tabla + 1.5);
    $pdf->Cell(0, 4, 'Tipo de documento:', 0, 0);
    $pdf->SetXY($col2_x + 50, $y_inicio_tabla + 1.5);
    $pdf->Cell(0, 4, 'Cert. Adjudicación', 0, 1);
    
    $y_fila = $y_inicio_tabla + $alto_fila;
    
    // ===== FILA 2: DNI | N° Certificado =====
    $pdf->Rect($col1_x, $y_fila, $ancho_col, $alto_fila);
    $pdf->Rect($col2_x, $y_fila, $ancho_col, $alto_fila);
    $pdf->SetXY($col1_x + 3, $y_fila + 1.5);
    $pdf->Cell(0, 4, 'DNI del titular:', 0, 0);
    $pdf->SetXY($col1_x + 55, $y_fila + 1.5);
    $pdf->Cell(0, 4, $dni, 0, 1);
    $pdf->SetXY($col2_x + 3, $y_fila + 1.5);
    $pdf->Cell(0, 4, 'N° Certificado:', 0, 0);
    $pdf->SetFont('times', 'B', 10);
    $pdf->SetXY($col2_x + 50, $y_fila + 1.5);
    $pdf->Cell(0, 4, $codigo, 0, 1);
    $pdf->SetFont('times', '', 10);
    
    $y_fila += $alto_fila;
    
    // ===== FILA 3: N° Acta | Fecha Acta =====
    $pdf->Rect($col1_x, $y_fila, $ancho_col, $alto_fila);
    $pdf->Rect($col2_x, $y_fila, $ancho_col, $alto_fila);
    $pdf->SetXY($col1_x + 3, $y_fila + 1.5);
    $pdf->Cell(0, 4, 'Número de acta:', 0, 0);
    $pdf->SetXY($col1_x + 55, $y_fila + 1.5);
    $pdf->Cell(0, 4, '_______________', 0, 1);
    $pdf->SetXY($col2_x + 3, $y_fila + 1.5);
    $pdf->Cell(0, 4, 'Fecha de acta:', 0, 0);
    $pdf->SetXY($col2_x + 50, $y_fila + 1.5);
    $pdf->Cell(0, 4, '_______________', 0, 1);
    
    $y_fila += $alto_fila;
    
    // ===== FILA 4: Resolución (Opcional) | Tipo Inmuebilidad =====
    $pdf->Rect($col1_x, $y_fila, $ancho_col, $alto_fila);
    $pdf->Rect($col2_x, $y_fila, $ancho_col, $alto_fila);
    $pdf->SetXY($col1_x + 3, $y_fila + 1.5);
    $pdf->Cell(0, 4, 'N° Resolución:', 0, 0);
    $pdf->SetXY($col1_x + 55, $y_fila + 1.5);
    $pdf->Cell(0, 4, '_______________ (Opc.)', 0, 1);
    $pdf->SetXY($col2_x + 3, $y_fila + 1.5);
    $pdf->Cell(0, 4, 'Tipo inmuebilidad:', 0, 0);
    $pdf->SetXY($col2_x + 50, $y_fila + 1.5);
    $pdf->Cell(0, 4, 'Ordinaria', 0, 1);
    
    $y_fila += $alto_fila;
    
    // ===== FILA 5: Libro | Folio | Registro =====
    $pdf->Rect($col1_x, $y_fila, $ancho_col, $alto_fila);
    $pdf->Rect($col2_x, $y_fila, $ancho_col, $alto_fila);
    $pdf->SetXY($col1_x + 3, $y_fila + 1.5);
    $pdf->Cell(20, 4, 'Libro:', 0, 0);
    $pdf->Cell(15, 4, '____', 0, 0);
    $pdf->Cell(20, 4, 'Folio:', 0, 0);
    $pdf->Cell(15, 4, '____', 0, 1);
    $pdf->SetXY($col2_x + 3, $y_fila + 1.5);
    $pdf->Cell(30, 4, 'Registro:', 0, 0);
    $pdf->Cell(15, 4, '____', 0, 1);
    
    $y_fila += $alto_fila;
    
    // ===== Borde inferior de la tabla =====
    $pdf->Line($col1_x, $y_fila, 190, $y_fila);
    $pdf->Line($col1_x, $y_inicio_tabla, $col1_x, $y_fila);
    $pdf->Line(105, $y_inicio_tabla, 105, $y_fila);
    $pdf->Line(190, $y_inicio_tabla, 190, $y_fila);
    
    // ==================== QR FUERA DEL CUADRO ====================
    $pdf->Ln(25);
    $y_qr = $pdf->GetY();
    
    // QR code a la derecha
    $qr_data = (string)$adjudicacion_id;
    $style = [
        'border' => 0,
        'padding' => 0,
        'fgcolor' => [0, 0, 0],
        'bgcolor' => [255, 255, 255]
    ];
    
    // QR centrado a la derecha
    $pdf->write2DBarcode($qr_data, 'QRCODE', 88, $y_qr, 34, 34, $style, 'N');
    
    $pdf->SetXY(88, $y_qr + 36);
    $pdf->SetFont('times', 'I', 9);
    $pdf->SetTextColor(80, 80, 80);
    $pdf->Cell(34, 5, 'Escanee para verificar', 0, 1, 'C');
    
    // ==================== TEXTO DE AUTENTICIDAD ====================
    $pdf->Ln(10);
    $pdf->SetFont('times', 'I', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(165, 4, 'El presente documento constituye una reproduccion autentica e integra de un documento electronico registrado por la Comunidad Campesina de Callqui Chico, conforme a su reglamento interno y normas vigentes, gozan de plena validez juridica.', 0, 'J');
    
    $pdf->Ln(4);
    $pdf->SetFont('times', 'I', 9);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->MultiCell(165, 4, 'Su autenticidad e integridad pueden ser verificadas a traves de la siguiente direccion web:', 0, 'J');
    
    $pdf->Ln(3);
    $pdf->SetFont('times', '', 9);
    $pdf->SetTextColor($color_dorado[0], $color_dorado[1], $color_dorado[2]);
    $pdf->Cell(0, 4, 'www.callquichico.pe/publico/certificado.php?codigo=' . $codigo, 0, 1, 'C');
    
    // ==================== FIRMAS HOJA 2 (MISMA LÓGICA QUE HOJA 1) ====================
    $pdf->Ln(18);
    $y_inicio_h2 = $pdf->GetY();
    
    // Obtener datos de usuarios para hoja 2
    $stmt_h2 = $conn->prepare("SELECT id, nombres, apellidos, dni, rol FROM usuarios WHERE rol IN ('secretario', 'comite_lotes')");
    $stmt_h2->execute();
    $res_h2 = $stmt_h2->get_result();
    $usuarios_h2 = [];
    while ($row_u = $res_h2->fetch_assoc()) {
        $usuarios_h2[$row_u['rol']] = $row_u;
    }
    $stmt_h2->close();
    
    $color_dorado = [201, 164, 92];
    $color_negro = [26, 26, 26];
    $color_gris = [85, 85, 85];
    $logo_path = __DIR__ . '/../assets/img/logo_callqui.jpg';
    
    // Dos columnas: SECRETARIO (izquierda) y RESPONSABLE LOTES (derecha)
    $x_secretario = 30;
    $x_lotes = 105;
    $ancho_col = 55;
    
    // Función para dibujar una firma (misma lógica que hoja 1)
    $dibujarFirmaH2 = function($usuario, $cargo_titulo, $x, $y) use ($pdf, $color_dorado, $color_negro, $color_gris, $ancho_col, $logo_path) {
        if (!$usuario) return;
        
        $nombre = strtoupper(trim($usuario['nombres'] . ' ' . $usuario['apellidos']));
        $dni = $usuario['dni'];
        $fecha = date('d/m/Y H:i');
        
        // Logo comunidad
        $x_centro = $x + ($ancho_col / 2);
        if (file_exists($logo_path)) {
            $pdf->Image($logo_path, $x_centro - 4, $y, 8, 8, 'JPG');
        }
        
        $y_logo = $y + 9;
        
        // Datos: Por, DNI, Fecha
        $pdf->SetTextColor($color_gris[0], $color_gris[1], $color_gris[2]);
        $pdf->SetFont('times', '', 5.5);
        $pdf->SetXY($x, $y_logo);
        $pdf->Cell($ancho_col, 3, 'Por: ' . $nombre, 0, 1, 'C');
        $pdf->SetX($x);
        $pdf->Cell($ancho_col, 3, 'DNI: ' . $dni, 0, 1, 'C');
        $pdf->SetX($x);
        $pdf->Cell($ancho_col, 3, 'Fecha: ' . $fecha, 0, 1, 'C');
        
        $y_datos = $pdf->GetY();
        
        // Línea divisoria dorada
        $pdf->SetDrawColor($color_dorado[0], $color_dorado[1], $color_dorado[2]);
        $pdf->SetLineWidth(0.3);
        $pdf->Line($x + 3, $y_datos + 1, $x + $ancho_col - 3, $y_datos + 1);
        
        // Nombre debajo de línea
        $pdf->SetTextColor($color_negro[0], $color_negro[1], $color_negro[2]);
        $pdf->SetFont('times', 'B', 5.5);
        $pdf->SetXY($x, $y_datos + 2);
        $pdf->Cell($ancho_col, 3, $nombre, 0, 1, 'C');
        
        // Cargo/título
        $pdf->SetTextColor($color_gris[0], $color_gris[1], $color_gris[2]);
        $pdf->SetFont('times', 'I', 5);
        $pdf->SetXY($x, $pdf->GetY());
        $pdf->Cell($ancho_col, 3, $cargo_titulo, 0, 1, 'C');
    };
    
    // SECRETARIO GENERAL (izquierda)
    $secretario = $usuarios_h2['secretario'] ?? null;
    $dibujarFirmaH2($secretario, 'EL SECRETARIO', $x_secretario, $y_inicio_h2);
    
    // RESPONSABLE DE REGISTRO/LOTES (derecha)
    $comite_lotes = $usuarios_h2['comite_lotes'] ?? null;
    $dibujarFirmaH2($comite_lotes, 'COMITÉ DE LOTES', $x_lotes, $y_inicio_h2);
    
    $pdf->SetTextColor(0, 0, 0);
    
    // Pie de página
    $pdf->Ln(15);
    $pdf->SetFont('times', 'I', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 3, 'Documento generado por el Sistema de Gestión de la Comunidad Campesina Callqui Chico', 0, 1, 'C');
    $pdf->Cell(0, 3, 'Reconocida mediante Resolución N° 138-2005/GOB.REG.HVCA/GRDE-DRA', 0, 1, 'C');
    
    // ==================== GUARDAR PDF ====================
    $documentos_dir = __DIR__ . '/../storage/documentos';
    if (!is_dir($documentos_dir)) {
        mkdir($documentos_dir, 0777, true);
    }
    
    $nombre_pdf = 'certificado_adjudicacion_' . $adjudicacion_id . '_' . date('YmdHis') . '.pdf';
    $ruta_completa = $documentos_dir . '/' . $nombre_pdf;
    $ruta_db = 'storage/documentos/' . $nombre_pdf;
    
    // Guardar PDF en archivo
    $pdf->Output($ruta_completa, 'F');
    
    // Generar hash SHA-256 del PDF para verificación de integridad
    $hash_sha256 = hash_file('sha256', $ruta_completa);
    $timestamp_generacion = date('Y-m-d H:i:s');
    
    // Actualizar la adjudición con hash y timestamp
    $stmt_update = $conn->prepare("UPDATE adjudicaciones SET 
        hash_sha256 = ?,
        timestamp_generacion = ?
        WHERE id = ?");
    $stmt_update->bind_param("ssi", $hash_sha256, $timestamp_generacion, $adjudicacion_id);
    $stmt_update->execute();
    $stmt_update->close();
    
    return [
        'success' => true,
        'pdf_path' => $ruta_completa,
        'pdf_db' => $ruta_db,
        'codigo' => $codigo,
        'hash_sha256' => $hash_sha256,
        'timestamp' => $timestamp_generacion
    ];
}


/**
 * Firma un PDF usando Python y pyHanko
 */
function firmarPDF($pdf_input, $adjudicacion_id, $usuario_id = null) {
    $conn = getDB();
    
    if (!$usuario_id) {
        $usuario_id = $_SESSION['usuario_id'] ?? 1;
    }
    
    $stmt = $conn->prepare("SELECT certificado_digital, password_certificado, nombres, apellidos FROM usuarios WHERE id = ?");
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (empty($user['certificado_digital']) || empty($user['password_certificado'])) {
        return ['success' => false, 'message' => 'Usuario no tiene certificado digital configurado'];
    }
    
    $cert_path = __DIR__ . '/../storage/' . $user['certificado_digital'];
    $cert_password = $user['password_certificado'];
    $nombre_firmante = $user['nombres'] . ' ' . $user['apellidos'];
    
    if (!file_exists($cert_path)) {
        // Intentar también con la ruta original
        $cert_path = __DIR__ . '/../' . $user['certificado_digital'];
    }
    
    if (!file_exists($cert_path)) {
        return ['success' => false, 'message' => 'Certificado digital no encontrado'];
    }
    
    if (!file_exists($pdf_input)) {
        return ['success' => false, 'message' => 'PDF a firmar no encontrado'];
    }
    
    $timestamp = date('YmdHis');
    $pdf_firmado_path = __DIR__ . '/../storage/documentos_firmados/adjudicacion_' . $adjudicacion_id . '_firmado_' . $timestamp . '.pdf';
    
    if (!is_dir(__DIR__ . '/../storage/documentos_firmados')) {
        mkdir(__DIR__ . '/../storage/documentos_firmados', 0777, true);
    }
    
    $python_script = __DIR__ . '/../scripts/python/firmar_pdf.py';
    $command = sprintf(
        'python "%s" "%s" "%s" "%s" "%s" --firmante "%s" --rol "firma"',
        $python_script,
        $pdf_input,
        $pdf_firmado_path,
        $cert_path,
        escapeshellarg($cert_password),
        $nombre_firmante
    );
    
    $output = [];
    $return_var = 0;
    exec($command, $output, $return_var);
    
    if ($return_var !== 0 || empty($output)) {
        return ['success' => false, 'message' => 'Error al ejecutar firma digital', 'debug' => implode("\n", $output)];
    }
    
    $resultado = json_decode(implode("\n", $output), true);
    if (!$resultado || !$resultado['success']) {
        return ['success' => false, 'message' => 'Error en la firma: ' . ($resultado['message'] ?? 'Unknown error')];
    }
    
    if (!file_exists($pdf_firmado_path)) {
        return ['success' => false, 'message' => 'PDF firmado no fue creado'];
    }
    
    $archivo_firmado = 'storage/documentos_firmados/adjudicacion_' . $adjudicacion_id . '_firmado_' . $timestamp . '.pdf';
    
    return [
        'success' => true,
        'pdf_firmado' => $pdf_firmado_path,
        'pdf_firmado_db' => $archivo_firmado,
        'firmante' => $nombre_firmante,
        'fecha' => $resultado['fecha']
    ];
}


/**
 * Genera el certificado y lo firma digitalmente
 */
function generarYFirmarCertificado($adjudicacion_id) {
    $resultado = generarCertificadoPDF($adjudicacion_id);
    
    if (!$resultado['success']) {
        return $resultado;
    }
    
    $firma = firmarPDF($resultado['pdf_path'], $adjudicacion_id);
    
    if (!$firma['success']) {
        return [
            'success' => false,
            'message' => 'PDF generado pero la firma falló: ' . $firma['message'],
            'pdf_path' => $resultado['pdf_path']
        ];
    }
    
    $conn = getDB();
    $stmt = $conn->prepare("UPDATE adjudicaciones SET 
        pdf_firmado = ?, 
        certificado = ?,
        completamente_firmado = 1,
        estado = 'aprobado_total'
        WHERE id = ?");
    $stmt->bind_param("ssi", $firma['pdf_firmado_db'], $firma['pdf_firmado_db'], $adjudicacion_id);
    $stmt->execute();
    $stmt->close();
    
    return [
        'success' => true,
        'pdf_firmado' => $firma['pdf_firmado'],
        'pdf_firmado_db' => $firma['pdf_firmado_db'],
        'codigo' => $resultado['codigo']
    ];
}


/**
 * Genera código QR para el certificado
 */
function generarQR($codigo) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base_path = '/2026';
    $qr_data = $protocol . $host . $base_path . '/publico/certificado.php?codigo=' . $codigo;
    
    $qr_dir = __DIR__ . '/../storage/qr';
    if (!is_dir($qr_dir)) {
        mkdir($qr_dir, 0777, true);
    }
    
    $qr_file = $qr_dir . '/qr_' . $codigo . '.png';
    
    $qr_url = 'https://chart.googleapis.com/chart?chs=150x150&cht=qr&chl=' . urlencode($qr_data);
    
    $ch = curl_init($qr_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $qr_image = curl_exec($ch);
    curl_close($ch);
    
    if ($qr_image) {
        file_put_contents($qr_file, $qr_image);
        return 'storage/qr/qr_' . $codigo . '.png';
    }
    
    return null;
}