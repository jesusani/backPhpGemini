<?php
// frontend/create.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'api_client.php';
// Necesitamos acceso a Security para generar el token
require_once __DIR__ . '/../utils/Security.php';

$csrfToken = Security::generateCsrfToken();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF en el Frontend antes de enviar (Doble capa)
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Error de seguridad: Sesión expirada o token inválido.';
    } else {
        // Basic formatting
        $payload = [
            'type' => $_POST['type'],
            'concept' => $_POST['concept'],
            'amount' => (float)$_POST['amount'],
            'recipientNIF' => $_POST['recipientNIF'],
            'recipientName' => $_POST['recipientName'],
            'csrf_token' => $_POST['csrf_token'] // Pasamos el token al backend
        ];

        if ($payload['type'] === 'RECTIFICATIVA') {
            $payload['originalInvoiceId'] = (int)$_POST['originalInvoiceId'];
            $payload['reason'] = $_POST['reason'];
        }

        $response = $api->register($payload);
        
        if ($response['code'] === 200 && ($response['body']['success'] ?? false)) {
            header("Location: index.php");
            exit;
        } else {
            $error = $response['body']['error'] ?? 'Error desconocido al registrar.';
            if (isset($response['body']['details'])) {
                $error .= " " . implode(", ", $response['body']['details']);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Factura - VTS</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="container">
        <header>
            <div style="display:flex; align-items:center;">
                <a href="index.php" class="btn btn-secondary" style="margin-right:20px;">&larr; Volver</a>
                <h1>Registrar Factura</h1>
            </div>
        </header>

        <div class="card" style="max-width: 600px; margin: 0 auto;">
            <?php if ($error): ?>
                <div style="background: rgba(218, 54, 51, 0.2); color: #ff7b72; padding: 10px; border-radius: 6px; margin-bottom: 20px;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="create.php" onsubmit="return validateForm()">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                
                <div class="form-group">
                    <label>Tipo de Asiento</label>
                    <select name="type" id="typeSelect" onchange="toggleRectificativa()">
                        <option value="INITIAL">Factura Ordinaria (INITIAL)</option>
                        <option value="RECTIFICATIVA">Factura Rectificativa</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Cliente / Receptor</label>
                    <div style="display:flex; gap:10px;">
                        <input type="text" id="nifInput" name="recipientNIF" placeholder="NIF (ej. B12345678)" required style="flex:1;" pattern="^[A-Za-z0-9]{9}$" title="Debe tener 9 caracteres alfanuméricos" oninput="validateNifInfo()">
                        <input type="text" name="recipientName" placeholder="Razón Social" required style="flex:2;">
                    </div>
                    <small id="nifFeedback" style="display:none; color:var(--danger); font-size:12px;">Formato de NIF inválido (9 caracteres requeridos)</small>
                </div>

                <div class="form-group">
                    <label>Concepto</label>
                    <input type="text" name="concept" placeholder="Descripción del servicio..." required minlength="5">
                </div>

                <div class="form-group">
                    <label>Importe Total (€)</label>
                    <input type="number" step="0.01" name="amount" placeholder="0.00" required min="0.01">
                </div>

                <!-- Rectificativa Fields -->
                <div id="rectFields" style="display:none; border-top:1px solid #30363d; padding-top:20px; margin-top:20px;">
                    <h3 style="font-size:16px; margin-bottom:15px; color:var(--accent);">Datos Rectificación</h3>
                    <div class="form-group">
                        <label>ID Factura Original</label>
                        <input type="number" name="originalInvoiceId" placeholder="ID de la factura a corregir">
                    </div>
                    <div class="form-group">
                        <label>Motivo</label>
                        <input type="text" name="reason" placeholder="Razón de la corrección">
                    </div>
                </div>

                <div style="margin-top:30px;">
                    <button type="submit" class="btn" style="width:100%; padding:12px;">Registrar Asiento</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function toggleRectificativa() {
            var type = document.getElementById('typeSelect').value;
            var fields = document.getElementById('rectFields');
            fields.style.display = (type === 'RECTIFICATIVA') ? 'block' : 'none';
        }

        function validateNifInfo() {
            var nif = document.getElementById('nifInput').value;
            var feedback = document.getElementById('nifFeedback');
            // Regex JS simple para feedback visual (coincide con backend)
            var regex = /^[A-Za-z0-9]{9}$/;
            
            if (nif.length > 0 && !regex.test(nif)) {
                feedback.style.display = 'block';
                document.getElementById('nifInput').style.borderColor = 'var(--danger)';
            } else {
                feedback.style.display = 'none';
                document.getElementById('nifInput').style.borderColor = (nif.length > 0) ? 'var(--success)' : 'var(--border-color)';
            }
        }
    </script>
</body>
</html>
