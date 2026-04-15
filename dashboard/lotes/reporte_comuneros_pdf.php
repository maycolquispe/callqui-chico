<?php

require_once "../config/conexion.php";
require_once "../dompdf/autoload.inc.php";

use Dompdf\Dompdf;

$sql = "
SELECT 
u.dni,
u.nombres,
u.apellidos,
u.promocion,
COUNT(lp.id) as total_lotes
FROM callqui_chico_usuarios u
LEFT JOIN callqui_chico_lotes_propietarios lp 
ON lp.usuario_id = u.id AND lp.estado='ACTIVO'
WHERE u.rol='comunero'
GROUP BY u.id
ORDER BY u.promocion
";

$result = $conn->query($sql);

$html = "

<h2>Reporte de Comuneros y Lotes</h2>

<table border='1' width='100%'>

<tr>
<th>Comunero</th>
<th>DNI</th>
<th>Promoción</th>
<th>Lotes</th>
</tr>

";

while($row = $result->fetch_assoc()){

$html .= "

<tr>

<td>".$row['nombres']." ".$row['apellidos']."</td>
<td>".$row['dni']."</td>
<td>".$row['promocion']."</td>
<td>".$row['total_lotes']."</td>

</tr>

";

}

$html .= "</table>";

$dompdf = new Dompdf();

$dompdf->loadHtml($html);

$dompdf->setPaper('A4','portrait');

$dompdf->render();

$dompdf->stream("reporte_comuneros.pdf");

?>