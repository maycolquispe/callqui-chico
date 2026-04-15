<?php
header('Content-Type: application/json');

session_start();
header('Content-Type: application/json');

$conn = new mysqli("localhost", "root", "", "callqui_chico");

if ($conn->connect_error) {
    echo json_encode(['status'=>'error','message'=>'Error BD']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status'=>'error','message'=>'Método inválido']);
    exit;
}

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    echo json_encode(['status'=>'error','message'=>'No logueado']);
    exit;
}

$acta_id = intval($_POST['acta_id'] ?? 0);
$lat = floatval($_POST['lat'] ?? 0);
$lng = floatval($_POST['lng'] ?? 0);

if (!$acta_id || !$lat || !$lng) {
    echo json_encode(['status'=>'error','message'=>'Datos incompletos']);
    exit;
}

/* ACTA */
$stmt = $conn->prepare("SELECT latitud, longitud, radio_metros, hora_inicio, hora_fin FROM actas WHERE id=?");
$stmt->bind_param("i", $acta_id);
$stmt->execute();
$acta = $stmt->get_result()->fetch_assoc();

if (!$acta) {
    echo json_encode(['status'=>'error','message'=>'Acta no existe']);
    exit;
}

/* HORARIO */
if ($acta['hora_inicio'] && $acta['hora_fin']) {
    $hora = date("H:i:s");
    if ($hora < $acta['hora_inicio'] || $hora > $acta['hora_fin']) {
        echo json_encode([
            'status'=>'error',
            'code'=>'HORARIO',
            'message'=>'Fuera de horario'
        ]);
        exit;
    }
}

/* DISTANCIA */
function distancia($lat1,$lon1,$lat2,$lon2){
    $R=6371000;
    $dLat=deg2rad($lat2-$lat1);
    $dLon=deg2rad($lon2-$lon1);
    $a=sin($dLat/2)**2+cos(deg2rad($lat1))*cos(deg2rad($lat2))*sin($dLon/2)**2;
    return $R*(2*atan2(sqrt($a),sqrt(1-$a)));
}

if ($acta['latitud'] && $acta['longitud'] && $acta['radio_metros']) {
    $dist = distancia($lat,$lng,$acta['latitud'],$acta['longitud']);

    if ($dist > $acta['radio_metros']) {
        echo json_encode([
            'status'=>'error',
            'code'=>'DISTANCIA',
            'message'=>'Fuera del área (' . round($dist) . 'm)'
        ]);
        exit;
    }
}

/* DUPLICADO */
$check = $conn->prepare("SELECT id FROM asistencias WHERE usuario_id=? AND acta_id=?");
$check->bind_param("ii", $usuario_id, $acta_id);
$check->execute();
if ($check->get_result()->num_rows > 0) {
    echo json_encode([
        'status'=>'error',
        'code'=>'DUPLICADO',
        'message'=>'Ya registraste'
    ]);
    exit;
}

/* INSERT */
$insert = $conn->prepare("INSERT INTO asistencias (usuario_id, acta_id, estado, latitud, longitud, fecha_registro) VALUES (?, ?, 'asistio', ?, ?, NOW())");

if (!$insert) {
    echo json_encode(['status'=>'error','message'=>$conn->error]);
    exit;
}

$insert->bind_param("iidd", $usuario_id, $acta_id, $lat, $lng);

if ($insert->execute()) {
    echo json_encode(['status'=>'success','message'=>'Asistencia OK']);
} else {
    echo json_encode(['status'=>'error','message'=>$insert->error]);
}