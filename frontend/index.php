<?php
// frontend/index.php
ini_set('display_errors', 1); 
error_reporting(E_ALL);



if (isset($_SESSION['registrado']) && $_SESSION['registrado'] == 'true') {
		
		echo "<script>setTimeout('document.location.reload()',1000*60*16); </script>";
		$minutos = ((($_SESSION['duracion']) - (time())) );
		
		
		if ($minutos < 1) {
			echo 'Más de 15 minutos de diferencia';
			echo "salimos de la sesion";
			session_destroy();
			
			unset($_COOKIE['admin']);
			unset($_COOKIE['usuario']);
			unset($_COOKIE['contraseña']);
			
			setcookie('admin', null, -1, '/');
			setcookie('usuario', null, -1, '/');
			setcookie('contraseña', null, -1, '/');
		
			echo "<script>";
			echo "  window.location.replace('./index.php');";
			echo "</script>";
						
		} else {
				
			$tiempo = time()+900;
			$_SESSION['duracion'] = $tiempo;
			
		} 


require_once 'api_client.php';



try {
    $response = $api->getList();
    $invoices = ($response['code'] === 200) ? $response['body']['records'] : [];
    if (!is_array($invoices)) $invoices = [];
} catch (Exception $e) {
    die("Error crítico: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>VTS VeriFactu Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div style="display:flex; justify-content:space-between; align-items:center;">
                <div>
                    <h1>VTS VeriFactu</h1>
                    <span style="color:var(--text-secondary);">Sistema de Facturación Antifraude</span>
                </div>
                <a href="create.php" class="btn">+ Nueva Factura</a>
            </div>
        </header>

        <div class="card">
            <h2>Libro Registro de Facturas</h2>
            <?php if (empty($invoices)): ?>
                <p>No hay facturas registradas. <a href="create.php">Crea la primera</a>.</p>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Concepto</th>
                            <th>Importe</th>
                            <th>Tipo</th>
                            <th>Hash (Corto)</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($invoices as $inv): ?>
                            <?php 
                                // Decode entry data safely
                                $data = json_decode($inv['entry_data'], true); 
                                $class = ($inv['data']['type'] ?? '') === 'RECTIFICATIVA' ? 'badge-rect' : 'badge-initial';
                                $typeName = $data['type'] ?? 'INITIAL';
                            ?>
                            <tr>
                                <td>#<?= $inv['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($inv['timestamp'])) ?></td>
                                <td><?= htmlspecialchars($data['concept'] ?? 'N/A') ?></td>
                                <td style="font-family:monospace;"><?= number_format((float)($data['amount'] ?? 0), 2) ?> €</td>
                                <td><span class="badge <?= ($typeName === 'RECTIFICATIVA' ? 'badge-rect' : 'badge-initial') ?>"><?= $typeName ?></span></td>
                                <td><span class="hash-box" style="padding:2px 6px;"><?= substr($inv['current_hash'], 0, 16) ?>...</span></td>
                                <td>
                                    <a href="details.php?id=<?= $inv['id'] ?>">Ver Detalle</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
	}else{
		echo "No estás autorizado";
	}
    ?>