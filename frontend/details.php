<?php
// frontend/details.php
require_once 'api_client.php';
$issuer_name = $_ENV['ISSUER_NAME'];
$issuer_cif = $_ENV['ISSUER_CIF'];
$issuer_address = $_ENV['ISSUER_ADDRESS'];
$issuer_city = $_ENV['ISSUER_CITY'];
$issuer_email = $_ENV['ISSUER_EMAIL'];

$id = $_GET['id'] ?? 0;
$response = $api->getInvoice($id);
$invoice = ($response['code'] === 200) ? $response['body']['invoice'] : null;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">  
    <title>Detalle Factura #<?= $id ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <!-- Barra de acciones (se oculta al imprimir) -->
        <header class="no-print" style="margin-bottom: 30px;">
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center;">
                    <a href="index.php" class="btn btn-secondary" style="margin-right:20px;">&larr; Volver</a>
                    <h1 style="margin:0;">Detalle de Factura</h1>
                </div>
                <div>
                    <a href="create.php?rectify_id=<?= $id ?>" class="btn btn-warning" style="margin-right:10px;">📉 Rectificar</a>
                    <button onclick="window.print()" class="btn btn-primary">🖨 Imprimir</button>
                </div>
            </div>
        </header>

        <?php if (!$invoice): ?>
            <div class="card">
                <h3 style="color:var(--danger);">Error</h3>
                <p>No se encontró la factura o hubo un error de conexión.</p>
            </div>
        <?php else: 
            $meta = json_decode($invoice['entry_data'], true);
            $fecha = new DateTime($invoice['timestamp']);
        ?>
            <!-- CABECERA EMPRESA -->
            <div class="card invoice-header" style="border-bottom: 4px solid var(--primary);">
                <div style="display:flex; justify-content:space-between; align-items: flex-start;">
                    <div style="flex: 1;">
                        <img src="../../imagenes/logoeve.jpg" alt="Logo Everest" style="height: 80px; margin-bottom: 15px;">
                        <h2 style="margin:0; color:var(--primary);"><?php echo $issuer_name; ?></h2>
                        <p style="margin:5px 0; font-size: 0.9rem;">
                            <strong>CIF:</strong> <?php echo $issuer_cif; ?><br>
                            <strong>Dirección:</strong> <?php echo $issuer_address; ?><br>
                            <?php echo $issuer_city; ?><br>
                            <strong>Email:</strong> <?php echo $issuer_email; ?>
                        </p>
                    </div>
                    <div style="text-align: right; flex: 1;">
                        <div style="background: var(--bg-secondary); padding: 15px; border-radius: 8px;">
                            <h3 style="margin:0; text-transform: uppercase; color: var(--text-secondary); font-size: 0.8rem; letter-spacing: 1px;">Número de Factura</h3>
                            <p style="font-size: 1.8rem; font-weight: bold; margin: 5px 0;">#<?= $id ?></p>
                            <h3 style="margin:10px 0 0; text-transform: uppercase; color: var(--text-secondary); font-size: 0.8rem; letter-spacing: 1px;">Fecha de Emisión</h3>
                            <p style="font-size: 1.1rem; margin: 5px 0;"><?= $fecha->format('d/m/Y H:i') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <!-- DATOS DEL PACIENTE -->
                <div class="card">
                    <h3 style="margin:0 0 15px; font-size: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">
                        <i class="bi bi-person"></i> Datos del Paciente
                    </h3>
                    <p style="margin:0; line-height: 1.6;">
                        <span style="color:var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">Nombre / Razón Social:</span><br>
                        <strong style="font-size: 1.1rem;"><?= htmlspecialchars($meta['recipientName']) ?></strong><br>
                        <span style="color:var(--text-secondary); font-size: 0.8rem; text-transform: uppercase;">NIF / DNI:</span><br>
                        <strong><?= htmlspecialchars($meta['recipientNIF']) ?></strong>
                    </p>
                </div>

                <!-- IMPORTE Y TIPO -->
                <div class="card" style="display: flex; flex-direction: column; justify-content: center; align-items: center; background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-card) 100%);">
                    <h3 style="margin:0 0 5px; font-size: 0.9rem; text-transform: uppercase; color: var(--text-secondary);">Total Factura</h3>
                    <p style="font-size: 2.5rem; font-weight: 800; margin: 0; color: var(--primary);">
                        <?= number_format((float)$meta['amount'], 2) ?> €
                    </p>
                    <span class="badge <?= ($meta['type'] == 'RECTIFICATIVA' ? 'badge-rect' : 'badge-initial') ?>" style="margin-top: 10px;">
                        <?= $meta['type'] ?>
                    </span>
                </div>
            </div>

            <!-- CONCEPTO Y QR -->
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <div class="card">
                    <h3 style="margin:0 0 15px; font-size: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 5px;">
                        Concepto / Servicios
                    </h3>
                    <p style="font-size: 1.1rem; margin: 0; min-height: 100px;">
                        <?= htmlspecialchars($meta['concept']) ?>
                    </p>
                    
                    <?php if ($meta['type'] === 'RECTIFICATIVA'): ?>
                        <div style="margin-top: 20px; padding: 15px; background: rgba(218, 54, 51, 0.05); border-left: 4px solid var(--danger); border-radius: 4px;">
                            <strong style="color:var(--danger);">Motivo de Rectificación:</strong><br>
                            <?= htmlspecialchars($meta['rectificationReason']) ?><br>
                            <small>Factura Original: #<?= $meta['originalInvoiceId'] ?></small>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card" style="text-align:center; display: flex; flex-direction: column; align-items: center; justify-content: center;">
                    <h3 style="margin:0 0 10px; font-size: 0.9rem; text-transform: uppercase;">Código VeriFactu</h3>
                    <div style="position:relative; display:inline-block; background:#fff; padding:8px; border-radius:8px; border:1px solid #ddd;">
                        <img src="../index.php?action=qr&id=<?= $id ?>" alt="QR AEAT" width="130" style="display:block;">
                        <img src="../../imagenes/logoeve.jpg" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:25px; height:auto; background:white; padding:1px;">
                    </div>
                    <p style="font-size: 0.7rem; color: var(--text-secondary); margin-top: 10px;">
                        Consulte la autenticidad en la AEAT
                    </p>
                </div>
            </div>

            <!-- FOOTER TECNICO (Blockchain) -->
            <div class="card no-print" style="margin-top:20px; padding: 15px; opacity: 0.8; font-size: 0.85rem;">
                <h3 style="margin: 0 0 10px; font-size: 0.9rem; color: var(--text-secondary);">Detalles del Registro (Blockchain)</h3>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div>
                        <strong>Hash del Asiento (SHA-256):</strong>
                        <div style="font-family: monospace; word-break: break-all; color: var(--success); background: #1a1a1a; padding: 5px; border-radius: 4px; margin-top: 3px;">
                            <?= $invoice['current_hash'] ?>
                        </div>
                    </div>
                    <div>
                        <strong>Encadenamiento Anterior:</strong>
                        <div style="font-family: monospace; word-break: break-all; color: var(--primary); background: #1a1a1a; padding: 5px; border-radius: 4px; margin-top: 3px;">
                            <?= $invoice['previous_hash'] ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
                @media print {
                    .no-print { display: none !important; }
                    body { background: white !important; color: black !important; padding: 0 !important; }
                    .container { max-width: 100% !important; margin: 0 !important; box-shadow: none !important; }
                    .card { box-shadow: none !important; border: 1px solid #eee !important; color: black !important; background: white !important; }
                    .invoice-header { border-bottom: 2px solid #333 !important; }
                    .badge { border: 1px solid #333 !important; color: black !important; background: transparent !important; }
                }
            </style>
        <?php endif; ?>
    </div>
</body>
</html>
