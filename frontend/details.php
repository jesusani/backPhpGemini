<?php
// frontend/details.php
require_once 'api_client.php';

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
        <header>
            <div style="display:flex; align-items:center; justify-content:space-between;">
                <div style="display:flex; align-items:center;">
                    <a href="index.php" class="btn btn-secondary" style="margin-right:20px;">&larr; Volver</a>
                    <h1>Detalle Asiento #<?= $id ?></h1>
                </div>
                <div>
                    <a href="create.php?rectify_id=<?= $id ?>" class="btn btn-warning" style="margin-right:10px;">📉 Crear Rectificativa</a>
                    <a href="../index.php?action=download&id=<?= $id ?>" class="btn btn-secondary" target="_blank" style="margin-right:10px;">⬇ JSON</a>
                    <button onclick="window.print()" class="btn">🖨 Imprimir PDF</button>
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
        ?>
            <div class="card">
                <div style="display:flex; justify-content:space-between;">
                    <div>
                        <span class="badge <?= ($meta['type'] == 'RECTIFICATIVA' ? 'badge-rect' : 'badge-initial') ?>">
                            <?= $meta['type'] ?>
                        </span>
                        <span style="color:var(--text-secondary); margin-left:10px;">ID Sistema: <?= $invoice['id'] ?></span>
                    </div>
                    <div style="text-align:right;">
                        <h2 style="margin:0;"><?= number_format((float)$meta['amount'], 2) ?> €</h2>
                        <small style="color:var(--text-secondary);"><?= $invoice['timestamp'] ?></small>
                    </div>
                </div>
                
                <hr style="border:0; border-bottom:1px solid var(--border-color); margin:20px 0;">

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div>
                        <label>Receptor</label>
                        <p style="margin-top:0;">
                            <strong><?= htmlspecialchars($meta['recipientName']) ?></strong><br>
                            <?= htmlspecialchars($meta['recipientNIF']) ?>
                        </p>
                    </div>
                    <div>
                        <label>Concepto</label>
                        <p style="margin-top:0;"><?= htmlspecialchars($meta['concept']) ?></p>
                    </div>
                    
                    <?php if ($meta['type'] === 'RECTIFICATIVA'): ?>
                    <div style="grid-column: 1 / -1; background: rgba(218, 54, 51, 0.1); padding:10px; border-radius:4px;">
                        <strong style="color:var(--danger);">Datos de Rectificación</strong>
                        <p style="margin:5px 0 0;">
                            Factura Original ID: <strong>#<?= $meta['originalInvoiceId'] ?></strong><br>
                            Motivo: <?= htmlspecialchars($meta['rectificationReason']) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card" style="text-align:center;">
                <h3>Código QR VeriFactu</h3>
                <p style="color:var(--text-secondary); font-size:12px; margin-bottom:15px;">
                    Este código permite al paciente verificar la autenticidad de la factura en la Agencia Tributaria.
                </p>
                <div style="position:relative; display:inline-block; background:#fff; padding:10px; border-radius:8px; border:1px solid var(--border-color);">
                    <img src="../index.php?action=qr&id=<?= $id ?>" alt="QR Verificación AEAT" width="180" style="display:block;">
                    <img src="../../imagenes/logoeve.jpg" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:40px; height:auto; background:white; padding:2px; border:1px solid #eee;">
                </div>
                <br><br>
                <small style="color:var(--text-secondary);">Enlace de consulta: <a href="https://www.agenciatributaria.es/consultafactura" target="_blank">AEAT Conecta</a></small>
            </div>

            <div class="card">
                <h3>Huella Digital y Cadena (Blockchain)</h3>
                
                <div class="form-group">
                    <label>Hash Actual (SHA-256)</label>
                    <div class="hash-box"><?= $invoice['current_hash'] ?></div>
                </div>

                <div class="form-group">
                    <label>Hash Anterior (Encadenamiento)</label>
                    <div class="hash-box" style="color:#79c0ff;"><?= $invoice['previous_hash'] ?></div>
                </div>

                <div class="form-group">
                    <label>Firma Electrónica (Criptográfica)</label>
                    <div class="hash-box" style="color:#56d364;"><?= substr($invoice['signature_proof'], 0, 64) ?>...</div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
