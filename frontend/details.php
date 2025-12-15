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
