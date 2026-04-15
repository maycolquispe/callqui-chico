<?php
/**
 * Service - Certificados
 * Lógica de negocio del módulo de certificados
 * Sistema Callqui Chico - Modular
 */
require_once __DIR__ . '/Repository.php';
require_once __DIR__ . '/FirmadorService.php';

class CertificadoService {
    
    private $repository;
    private $firmador;
    private $storage_dir;
    private $documentos_dir;
    private $img_dir;
    
    public function __construct() {
        $this->repository = new CertificadoRepository();
        $this->firmador = new FirmadorService();
        
        $base_path = dirname(__DIR__);
        $this->storage_dir = $base_path . '/storage';
        $this->documentos_dir = $this->storage_dir . '/documentos';
        $this->img_dir = $base_path . '/img';
        
        foreach ([$this->storage_dir, $this->documentos_dir, $this->documentos_dir . '/qr'] as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }
        }
    }
    
    public function generarCertificadoPDF($adjudicacion_id) {
        $adj = $this->repository->obtenerAdjudicacion($adjudicacion_id);
        
        if (!$adj) {
            return ['success' => false, 'message' => 'Adjudicación no encontrada'];
        }
        
        $año = date('Y');
        $codigo = 'ADJ-' . $año . '-' . str_pad($adjudicacion_id, 4, '0', STR_PAD_LEFT);
        $this->repository->actualizarCodigoCertificado($adjudicacion_id, $codigo);
        $adj['codigo_certificado'] = $codigo;
        
        $datos = $this->prepararDatosCertificado($adj);
        
        $pdf = $this->crearPDF($datos);
        
        $nombre_pdf = 'certificado_adjudicacion_' . $adjudicacion_id . '_' . date('YmdHis') . '.pdf';
        $ruta_completa = $this->documentos_dir . '/' . $nombre_pdf;
        $ruta_db = 'storage/documentos/' . $nombre_pdf;
        
        $pdf->Output($ruta_completa, 'F');
        
        $this->repository->actualizarPDF($adjudicacion_id, $ruta_db);
        
        return [
            'success' => true,
            'pdf_path' => $ruta_completa,
            'pdf_db' => $ruta_db,
            'codigo' => $codigo
        ];
    }
    
    public function firmarCertificado($adjudicacion_id, $usuario_id = null) {
        $adj = $this->repository->obtenerAdjudicacion($adjudicacion_id);
        
        if (!$adj || empty($adj['certificado'])) {
            return ['success' => false, 'message' => 'Certificado no encontrado'];
        }
        
        $pdf_input = dirname(__DIR__) . '/' . $adj['certificado'];
        
        $resultado = $this->firmador->firmarPDF($pdf_input, $adjudicacion_id, $usuario_id);
        
        if ($resultado['success']) {
            $this->repository->actualizarPDF(
                $adjudicacion_id, 
                $adj['certificado'],
                $resultado['pdf_firmado_db']
            );
        }
        
        return $resultado;
    }
    
    public function generarYFirmar($adjudicacion_id) {
        $resultado = $this->generarCertificadoPDF($adjudicacion_id);
        
        if (!$resultado['success']) {
            return $resultado;
        }
        
        $firma = $this->firmarCertificado($adjudicacion_id);
        
        if (!$firma['success']) {
            return [
                'success' => false,
                'message' => 'PDF generado pero la firma falló: ' . $firma['message'],
                'pdf_path' => $resultado['pdf_path']
            ];
        }
        
        return [
            'success' => true,
            'pdf_firmado' => $firma['pdf_firmado'],
            'pdf_firmado_db' => $firma['pdf_firmado_db'],
            'codigo' => $resultado['codigo']
        ];
    }
    
    public function obtenerPorCodigo($codigo) {
        return $this->repository->obtenerAdjudicacionPorCodigo($codigo);
    }
    
    public function obtenerMisCertificados($usuario_id) {
        return $this->repository->obtenerCertificadosPorUsuario($usuario_id);
    }
    
    private function prepararDatosCertificado($adj) {
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
        
        $lindero_frente = !empty(trim($adj['lindero_frente'])) ? trim($adj['lindero_frente']) : '_______________________';
        $lindero_fondo = !empty(trim($adj['lindero_fondo'])) ? trim($adj['lindero_fondo']) : '_______________________';
        $lindero_derecha = !empty(trim($adj['lindero_derecha'])) ? trim($adj['lindero_derecha']) : '_______________________';
        $lindero_izquierda = !empty(trim($adj['lindero_izquierda'])) ? trim($adj['lindero_izquierda']) : '_______________________';
        
        $metros_frente = !empty($adj['metros_frente']) ? $adj['metros_frente'] : '____';
        $metros_fondo = !empty($adj['metros_fondo']) ? $adj['metros_fondo'] : '____';
        $metros_derecha = !empty($adj['metros_derecha']) ? $adj['metros_derecha'] : '____';
        $metros_izquierda = !empty($adj['metros_izquierda']) ? $adj['metros_izquierda'] : '____';
        
        $meses = ['', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];
        $fecha_emision = date('d') . ' de ' . $meses[date('n')] . ' de ' . date('Y');
        
        return [
            'codigo' => $adj['codigo_certificado'],
            'nombre' => $nombre,
            'dni' => $dni,
            'sector' => $sector,
            'manzana' => $manzana,
            'lote' => $lote,
            'area' => $area,
            'linderos' => [
                'frente' => ['texto' => $lindero_frente, 'metros' => $metros_frente],
                'fondo' => ['texto' => $lindero_fondo, 'metros' => $metros_fondo],
                'derecha' => ['texto' => $lindero_derecha, 'metros' => $metros_derecha],
                'izquierda' => ['texto' => $lindero_izquierda, 'metros' => $metros_izquierda]
            ],
            'fecha_emision' => $fecha_emision
        ];
    }
    
    private function crearPDF($datos) {
        require_once dirname(__DIR__) . '/vendor/tcpdf/tcpdf.php';
        
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8');
        $pdf->SetCreator('Callqui Chico');
        $pdf->SetAuthor('Comunidad Campesina Callqui Chico');
        $pdf->SetTitle('Certificado de Adjudicación - ' . $datos['codigo']);
        $pdf->SetMargins(20, 20, 20);
        $pdf->SetAutoPageBreak(true, 15);
        
        $color_dorado = [201, 164, 92];
        $color_fondo = [252, 250, 247];
        $color_guilloché = [200, 200, 200];
        $color_gris_oscuro = [80, 80, 80];
        $color_linea_institucion = [180, 180, 180];
        
        $firmas = $this->repository->obtenerFirmasVisuales();
        
        $this->agregarPaginaCertificado($pdf, $datos, $color_dorado, $color_fondo, $color_guilloché, $color_gris_oscuro, $color_linea_institucion, $firmas);
        $this->agregarPaginaRegistro($pdf, $datos, $color_dorado, $color_fondo, $color_guilloché, $color_gris_oscuro, $color_linea_institucion, $firmas);
        
        return $pdf;
    }
    
    private function agregarPaginaCertificado($pdf, $datos, $color_dorado, $color_fondo, $color_guilloché, $color_gris_oscuro, $color_linea_institucion, $firmas) {
        $pdf->AddPage();
        
        $pdf->SetFillColor($color_fondo[0], $color_fondo[1], $color_fondo[2]);
        $pdf->Rect(0, 0, 210, 297, 'F');
        
        $pdf->SetDrawColor($color_linea_institucion[0], $color_linea_institucion[1], $color_linea_institucion[2]);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, 25, 190, 25);
        
        $logoWatermark = $this->img_dir . '/logo_blanco_negro.jpg';
        if (file_exists($logoWatermark)) {
            $pdf->SetAlpha(0.07);
            $pdf->Image($logoWatermark, 50, 90, 120, 120, 'JPG');
            $pdf->SetAlpha(1);
        }
        
        $guillochéImg = $this->img_dir . '/Guilloché.jpg';
        if (file_exists($guillochéImg)) {
            $pdf->SetAlpha(0.10);
            $pdf->Image($guillochéImg, 5, 20, 200, 260, 'JPG');
            $pdf->SetAlpha(1);
        }
        
        for ($radio = 10; $radio <= 70; $radio += 10) {
            $pdf->Circle(0, 0, $radio, 0, 360, 'D');
        }
        
        $logoPeru = $this->img_dir . '/logo_peru.jpg';
        $logoCallqui = $this->img_dir . '/logo_callqui.jpg';
        
        if (file_exists($logoPeru)) {
            $pdf->Image($logoPeru, 20, 20, 14, 14, 'JPEG');
        }
        if (file_exists($logoCallqui)) {
            $pdf->Image($logoCallqui, 176, 20, 14, 14, 'JPEG');
        }
        
        $pdf->SetFont('times', 'B', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 6, 'EN NOMBRE DE LA COMUNIDAD', 0, 1, 'C');
        
        $pdf->SetFont('times', 'B', 14);
        $pdf->Cell(0, 7, 'COMUNIDAD CAMPESINA DE CALLQUI CHICO', 0, 1, 'C');
        
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(0, 5, 'El Presidente de la Comunidad Campesa de Callqui Chico', 0, 1, 'C');
        
        $pdf->Ln(5);
        $pdf->SetFont('times', '', 10);
        $pdf->MultiCell(0, 4.5, 'Por cuanto: La Junta Directiva de la Comunidad Campesina de Callqui Chico, en sesión ordinaria de fecha _______________, ha acordado adjudicar el siguiente lote conforme a los acuerdos comunales y normas internas vigentes:', 0, 'J');
        
        $pdf->Ln(8);
        $pdf->SetFont('times', 'B', 20);
        $pdf->SetTextColor($color_dorado[0], $color_dorado[1], $color_dorado[2]);
        $pdf->Cell(0, 10, 'CERTIFICADO DE ADJUDICACIÓN', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        
        $pdf->Ln(6);
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 6, 'A favor de:', 0, 1, 'L');
        
        $pdf->SetFont('times', '', 12);
        $pdf->Cell(0, 6, strtoupper($datos['nombre']), 0, 1, 'L');
        $pdf->Cell(0, 6, 'Identificada con DNI N° ' . $datos['dni'], 0, 1, 'L');
        
        $pdf->Ln(5);
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 6, 'El lote ubicado en el sector: ' . strtoupper($datos['sector']), 0, 1, 'L');
        
        $pdf->Ln(3);
        $pdf->SetFont('times', '', 11);
        
        if ($datos['manzana']) {
            $pdf->Cell(55, 6, 'Manzana: ' . strtoupper($datos['manzana']), 0, 0);
        }
        $pdf->Cell(45, 6, 'Lote: ' . strtoupper($datos['lote']), 0, 0);
        $pdf->Cell(0, 6, 'Área total: ' . $datos['area'] . ' m²', 0, 1);
        
        $pdf->Ln(6);
        $pdf->SetFont('times', 'B', 12);
        $pdf->Cell(0, 6, 'Colindancias del lote:', 0, 1, 'L');
        
        $pdf->SetFont('times', '', 11);
        
        foreach ($datos['linderos'] as $nombre => $lindero) {
            $pdf->Cell(22, 5, '', 0, 0);
            $pdf->SetFont('times', 'B', 11);
            $pdf->Cell(28, 5, strtoupper($nombre) . ':', 0, 0);
            $pdf->SetFont('times', '', 11);
            $pdf->MultiCell(0, 5, 'colinda con ' . strtoupper($lindero['texto']) . ' (' . $lindero['metros'] . ' ml)', 0, 'L');
        }
        
        $pdf->Ln(6);
        $pdf->SetFont('times', '', 10);
        $pdf->MultiCell(0, 4.5, 'El presente lote ha sido adjudicado en reconocimiento de sus derechos comunales, quedando sujeto al cumplimiento de las normas internas, acuerdos de asamblea y disposiciones de la Junta Directiva.', 0, 'J');
        
        $pdf->Ln(4);
        $pdf->Cell(0, 4.5, 'Por tanto:', 0, 1, 'L');
        $pdf->MultiCell(0, 4.5, 'Se expide el presente CERTIFICADO DE ADJUDICACIÓN, para que se le reconozca como legítima posesionaria del lote descrito, con los derechos y obligaciones que la Comunidad Campesina de Callqui Chico establece.', 0, 'J');
        
        $pdf->Ln(6);
        $pdf->SetFont('times', '', 11);
        $pdf->Cell(0, 6, 'Callqui Chico, ' . $datos['fecha_emision'], 0, 1, 'L');
        
        $pdf->Ln(28);
        $y_firma = $pdf->GetY();
        
        $pdf->SetDrawColor($color_gris_oscuro[0], $color_gris_oscuro[1], $color_gris_oscuro[2]);
        $pdf->SetFont('times', '', 9);
        $pdf->SetTextColor($color_gris_oscuro[0], $color_gris_oscuro[1], $color_gris_oscuro[2]);
        
        $this->agregarFirma($pdf, 30, 75, $y_firma, $firmas, 'secretario', 'EL SECRETARIO');
        $this->agregarFirma($pdf, 82, 45, $y_firma, $firmas, 'tesorero', 'EL TESORERO');
        $this->agregarFirma($pdf, 135, 45, $y_firma, $firmas, 'presidente', 'EL PRESIDENTE');
    }
    
    private function agregarPaginaRegistro($pdf, $datos, $color_dorado, $color_fondo, $color_guilloché, $color_gris_oscuro, $color_linea_institucion, $firmas) {
        $pdf->AddPage();
        
        $pdf->SetFillColor($color_fondo[0], $color_fondo[1], $color_fondo[2]);
        $pdf->Rect(0, 0, 210, 297, 'F');
        
        $pdf->SetDrawColor($color_linea_institucion[0], $color_linea_institucion[1], $color_linea_institucion[2]);
        $pdf->SetLineWidth(0.5);
        $pdf->Line(20, 25, 190, 25);
        
        $logoWatermark = $this->img_dir . '/logo_blanco_negro.jpg';
        if (file_exists($logoWatermark)) {
            $pdf->SetAlpha(0.07);
            $pdf->Image($logoWatermark, 45, 80, 120, 120, 'JPG');
            $pdf->SetAlpha(1);
        }
        
        $guillochéImg = $this->img_dir . '/Guilloché.jpg';
        if (file_exists($guillochéImg)) {
            $pdf->SetAlpha(0.10);
            $pdf->Image($guillochéImg, 5, 20, 200, 260, 'JPG');
            $pdf->SetAlpha(1);
        }
        
        for ($radio = 10; $radio <= 70; $radio += 10) {
            $pdf->Circle(210, 297, $radio, 0, 360, 'D');
        }
        
        $pdf->Ln(10);
        $pdf->SetFont('times', 'B', 13);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(0, 7, 'REGISTRO OFICIAL DEL CERTIFICADO', 0, 1, 'C');
        
        $pdf->SetFont('times', 'B', 11);
        $pdf->Cell(0, 5, 'COMUNIDAD CAMPESINA DE CALLQUI CHICO', 0, 1, 'C');
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(0, 5, 'SECRETARÍA GENERAL – COMITÉ DE LOTES', 0, 1, 'C');
        
        $pdf->Ln(8);
        $this->agregarTablaRegistro($pdf, $datos);
        
        $pdf->Ln(25);
        $y_qr = $pdf->GetY();
        
        $qr_data = (string)$datos['codigo'];
        $style = [
            'border' => 0,
            'padding' => 0,
            'fgcolor' => [0, 0, 0],
            'bgcolor' => [255, 255, 255]
        ];
        
        $pdf->write2DBarcode($qr_data, 'QRCODE', 88, $y_qr, 34, 34, $style, 'N');
        
        $pdf->SetXY(88, $y_qr + 36);
        $pdf->SetFont('times', 'I', 9);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(34, 5, 'Escanee para verificar', 0, 1, 'C');
        
        $pdf->Ln(10);
        $pdf->SetFont('times', 'I', 9);
        $pdf->SetTextColor(100, 100, 100);
        $pdf->MultiCell(0, 4, 'Este es un documento auténtico imprimible de un elemento electrónico archivado por la Comunidad Campesina Callqui Chico, aplicando lo dispuesto por el reglamento de la comunidad Callqui Chico. Su autenticidad e integridad pueden ser contrastadas a través de la siguiente dirección web:', 0, 'J');
        
        $pdf->Ln(3);
        $pdf->SetFont('times', '', 9);
        $pdf->SetTextColor($color_dorado[0], $color_dorado[1], $color_dorado[2]);
        $pdf->Cell(0, 4, 'www.callquichico.pe/publico/certificado.php?codigo=' . $datos['codigo'], 0, 1, 'C');
        
        $pdf->Ln(18);
        $y_firmas_reg = $pdf->GetY();
        
        $pdf->SetDrawColor($color_gris_oscuro[0], $color_gris_oscuro[1], $color_gris_oscuro[2]);
        $pdf->SetFont('times', '', 9);
        $pdf->SetTextColor($color_gris_oscuro[0], $color_gris_oscuro[1], $color_gris_oscuro[2]);
        
        $this->agregarFirma($pdf, 30, 55, $y_firmas_reg, $firmas, 'secretario', 'SECRETARIO GENERAL');
        $pdf->SetXY(105, $y_firmas_reg);
        $pdf->Line(105, $y_firmas_reg, 160, $y_firmas_reg);
        $pdf->Cell(55, 5, 'RESPONSABLE DE REGISTRO / LOTES', 0, 1, 'C');
        
        $pdf->Ln(15);
        $pdf->SetFont('times', 'I', 8);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 3, 'Documento generado por el Sistema de Gestión de la Comunidad Campesina Callqui Chico', 0, 1, 'C');
        $pdf->Cell(0, 3, 'Reconocida mediante Resolución N° 138-2005/GOB.REG.HVCA/GRDE-DRA', 0, 1, 'C');
    }
    
    private function agregarFirma($pdf, $x, $ancho, $y, $firmas, $rol, $label) {
        $pdf->SetXY($x, $y);
        $pdf->Line($x, $y, $x + $ancho, $y);
        
        if (!empty($firmas[$rol])) {
            $ruta_firma = dirname(__DIR__) . '/publico/' . $firmas[$rol];
            if (file_exists($ruta_firma)) {
                $pdf->Image($ruta_firma, $x + 5, $y - 5, $ancho - 10, 4, 'PNG');
            }
        }
        
        $pdf->Cell($ancho, 5, $label, 0, 0, 'C');
    }
    
    private function agregarTablaRegistro($pdf, $datos) {
        $color_borde_tabla = [60, 60, 60];
        $pdf->SetDrawColor($color_borde_tabla[0], $color_borde_tabla[1], $color_borde_tabla[2]);
        $pdf->SetLineWidth(0.5);
        $pdf->SetFillColor(255, 255, 255);
        
        $col1_x = 20;
        $col2_x = 105;
        $ancho_col = 85;
        $alto_fila = 6;
        
        $filas = [
            ['Código de la comunidad:', 'CCCH-001', 'Tipo de documento:', 'Cert. Adjudicación'],
            ['DNI del titular:', $datos['dni'], 'N° Certificado:', $datos['codigo']],
            ['Número de acta:', '_______________', 'Fecha de acta:', '_______________'],
            ['N° Resolución:', '_______________ (Opc.)', 'Tipo inmuebilidad:', 'Ordinaria'],
        ];
        
        $y = $pdf->GetY();
        
        foreach ($filas as $i => $fila) {
            $pdf->Rect($col1_x, $y, $ancho_col, $alto_fila);
            $pdf->Rect($col2_x, $y, $ancho_col, $alto_fila);
            
            $pdf->SetXY($col1_x + 3, $y + 1.5);
            $pdf->SetFont('times', '', 10);
            $pdf->Cell(0, 4, $fila[0], 0, 0);
            $pdf->SetFont('times', 'B', 10);
            $pdf->Cell(0, 4, $fila[1], 0, 1);
            
            $pdf->SetXY($col2_x + 3, $y + 1.5);
            $pdf->SetFont('times', '', 10);
            $pdf->Cell(0, 4, $fila[2], 0, 0);
            $pdf->SetFont('times', 'B', 10);
            $pdf->Cell(0, 4, $fila[3], 0, 1);
            
            $y += $alto_fila;
        }
        
        $pdf->Rect($col1_x, $y, $ancho_col, $alto_fila);
        $pdf->Rect($col2_x, $y, $ancho_col, $alto_fila);
        
        $pdf->SetXY($col1_x + 3, $y + 1.5);
        $pdf->SetFont('times', '', 10);
        $pdf->Cell(20, 4, 'Libro:', 0, 0);
        $pdf->Cell(15, 4, '____', 0, 0);
        $pdf->Cell(20, 4, 'Folio:', 0, 0);
        $pdf->Cell(15, 4, '____', 0, 1);
        
        $pdf->SetXY($col2_x + 3, $y + 1.5);
        $pdf->Cell(30, 4, 'Registro:', 0, 0);
        $pdf->Cell(15, 4, '____', 0, 1);
        
        $y += $alto_fila;
        
        $pdf->Line($col1_x, $y, 190, $y);
        $pdf->Line($col1_x, $pdf->GetY() - count($filas) * $alto_fila - $alto_fila, $col1_x, $y);
        $pdf->Line(105, $pdf->GetY() - count($filas) * $alto_fila - $alto_fila, 105, $y);
        $pdf->Line(190, $pdf->GetY() - count($filas) * $alto_fila - $alto_fila, 190, $y);
    }
}