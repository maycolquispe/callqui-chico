<?php
/**
 * Funciones Utilitarias - Sistema Callqui Chico
 * Profesional v2.0
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

class Util {
    
    /**
     * Sanitizar entrada de usuario
     */
    public static function sanitizar($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizar'], $data);
        }
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }
    
    /**
     * Validar DNI peruano (8 dígitos)
     */
    public static function validarDNI($dni) {
        return preg_match('/^[0-9]{8}$/', $dni);
    }
    
    /**
     * Generar código único
     */
    public static function generarCodigo($prefijo = 'COD') {
        return $prefijo . date('YmdHis') . rand(100, 999);
    }
    
    /**
     * Formatear fecha para display
     */
    public static function formatFecha($fecha, $formato = 'd/m/Y') {
        if (empty($fecha)) return '-';
        $date = is_string($fecha) ? strtotime($fecha) : $fecha;
        return date($formato, $date);
    }
    
    /**
     * Formatear fecha y hora
     */
    public static function formatFechaHora($fecha) {
        return self::formatFecha($fecha, 'd/m/Y H:i');
    }
    
    /**
     * Obtener tiempo relativo (hace X tiempo)
     */
    public static function tiempoRelativo($fecha) {
        $timestamp = is_string($fecha) ? strtotime($fecha) : $fecha;
        $diferencia = time() - $timestamp;
        
        if ($diferencia < 60) return 'Hace un momento';
        if ($diferencia < 3600) return 'Hace ' . floor($diferencia / 60) . ' minutos';
        if ($diferencia < 86400) return 'Hace ' . floor($diferencia / 3600) . ' horas';
        if ($diferencia < 604800) return 'Hace ' . floor($diferencia / 86400) . ' días';
        
        return self::formatFecha($fecha);
    }
    
    /**
     * Obtener IP del cliente
     */
    public static function getIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }
    }
    
    /**
     * Redireccionar
     */
    public static function redirigir($ruta, $mensaje = '') {
        if ($mensaje) {
            $separador = strpos($ruta, '?') !== false ? '&' : '?';
            $ruta .= $separador . 'msg=' . urlencode($mensaje);
        }
        header("Location: $ruta");
        exit;
    }
    
    /**
     * Mostrar mensaje flash
     */
    public static function getMensaje() {
        $msg = $_GET['msg'] ?? '';
        $error = $_GET['error'] ?? '';
        
        if ($msg) {
            return ['tipo' => 'success', 'texto' => self::sanitizar($msg)];
        }
        if ($error) {
            return ['tipo' => 'danger', 'texto' => self::sanitizar($error)];
        }
        return null;
    }
    
    /**
     * Paginación simple
     */
    public static function paginar($total, $porPagina, $paginaActual) {
        $totalPaginas = ceil($total / $porPagina);
        $paginaActual = max(1, min($paginaActual, $totalPaginas));
        
        return [
            'total' => $total,
            'pagina' => $paginaActual,
            'porPagina' => $porPagina,
            'totalPaginas' => $totalPaginas,
            'offset' => ($paginaActual - 1) * $porPagina
        ];
    }
    
    /**
     * Obtener datos del usuario por ID
     */
    public static function getUsuario($id) {
        $conn = getDB();
        $stmt = $conn->prepare("SELECT id, dni, nombres, apellidos, foto, rol, estado FROM usuarios WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $usuario = $result->fetch_assoc();
        $stmt->close();
        return $usuario;
    }
    
    /**
     * Obtener nombre completo
     */
    public static function getNombreCompleto($usuario) {
        return trim(($usuario['nombres'] ?? '') . ' ' . ($usuario['apellidos'] ?? ''));
    }
    
    /**
     * Contar registros por condición
     */
    public static function contar($tabla, $where = '', $params = []) {
        $conn = getDB();
        $sql = "SELECT COUNT(*) as total FROM $tabla";
        
        if ($where) {
            $sql .= " WHERE $where";
        }
        
        $stmt = $conn->prepare($sql);
        if ($params) {
            $tipos = str_repeat('s', count($params));
            $stmt->bind_param($tipos, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        return $row['total'] ?? 0;
    }
    
    /**
     * Verificar si archivo es PDF válido
     */
    public static function validarPDF($archivo) {
        if (!isset($archivo['tmp_name']) || empty($archivo['tmp_name'])) {
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        
        return $mime === 'application/pdf';
    }
    
    /**
     * Subir archivo con validación
     */
    public static function subirArchivo($archivo, $carpeta = 'uploads', $prefijo = '') {
        if (!isset($archivo['tmp_name']) || empty($archivo['tmp_name'])) {
            return ['success' => false, 'error' => 'No se recibió archivo'];
        }
        
        if (!self::validarPDF($archivo)) {
            return ['success' => false, 'error' => 'Solo se permiten archivos PDF'];
        }
        
        if ($archivo['size'] > 10 * 1024 * 1024) {
            return ['success' => false, 'error' => 'El archivo excede 10MB'];
        }
        
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0777, true);
        }
        
        $nombreOriginal = basename($archivo['name']);
        $nombreLimpio = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', $nombreOriginal);
        $nombreArchivo = $prefijo . time() . '_' . $nombreLimpio;
        $rutaFinal = $carpeta . '/' . $nombreArchivo;
        
        if (move_uploaded_file($archivo['tmp_name'], $rutaFinal)) {
            return ['success' => true, 'archivo' => $nombreArchivo, 'ruta' => $rutaFinal];
        }
        
        return ['success' => false, 'error' => 'Error al mover el archivo'];
    }
    
    /**
     * Eliminar archivo
     */
    public static function eliminarArchivo($ruta) {
        if (file_exists($ruta) && is_file($ruta)) {
            return unlink($ruta);
        }
        return false;
    }
    
    /**
     * Obtener extensión de archivo
     */
    public static function getExtension($archivo) {
        return strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
    }
    
    /**
     * Generar slug URL-friendly
     */
    public static function slug($texto) {
        $texto = strtolower($texto);
        $texto = preg_replace('/[^a-z0-9\s\-]/', '', $texto);
        $texto = preg_replace('/[\s\-]+/', '-', $texto);
        return trim($texto, '-');
    }
    
    /**
     * Truncar texto
     */
    public static function truncate($texto, $longitud = 100, $suffix = '...') {
        if (strlen($texto) <= $longitud) {
            return $texto;
        }
        return substr($texto, 0, $longitud) . $suffix;
    }
    
    /**
     * Array to CSV
     */
    public static function arrayToCSV($datos, $cabeceras = []) {
        $output = fopen('php://temp', 'w');
        
        if ($cabeceras) {
            fputcsv($output, $cabeceras, ';');
        }
        
        foreach ($datos as $fila) {
            fputcsv($output, is_array($fila) ? $fila : (array)$fila, ';');
        }
        
        rewind($output);
        $contenido = stream_get_contents($output);
        fclose($output);
        
        return $contenido;
    }
    
    /**
     * Descargar contenido como archivo
     */
    public static function descargarArchivo($contenido, $nombre, $tipo = 'application/octet-stream') {
        header('Content-Type: ' . $tipo);
        header('Content-Disposition: attachment; filename="' . $nombre . '"');
        header('Content-Length: ' . strlen($contenido));
        echo $contenido;
        exit;
    }
    
    /**
     * JSON response
     */
    public static function jsonResponse($data, $status = 200) {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * Debug variable (solo desarrollo)
     */
    public static function debug($var, $label = '') {
        if (isset($_GET['debug'])) {
            echo $label ? "<pre><strong>$label:</strong></pre>" : '';
            echo '<pre>';
            print_r($var);
            echo '</pre>';
        }
    }
}

// Funciones helper
function sanitizar($data) {
    return Util::sanitizar($data);
}

function generar_codigo($prefijo = 'COD') {
    return Util::generarCodigo($prefijo);
}

function format_fecha($fecha, $formato = 'd/m/Y') {
    return Util::formatFecha($fecha, $formato);
}

function tiempo_relativo($fecha) {
    return Util::tiempoRelativo($fecha);
}

function get_ip() {
    return Util::getIP();
}

function redirigir($ruta, $mensaje = '') {
    Util::redirigir($ruta, $mensaje);
}

function subir_archivo($archivo, $carpeta = 'uploads', $prefijo = '') {
    return Util::subirArchivo($archivo, $carpeta, $prefijo);
}

function json_response($data, $status = 200) {
    Util::jsonResponse($data, $status);
}
