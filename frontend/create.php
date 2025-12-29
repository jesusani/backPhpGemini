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
$invoice = null;
$amount = null;
$prefill = [];

// Lógica de Rectificación (Prefill)
if (isset($_GET['rectify_id'])) {
    $rectId = (int)$_GET['rectify_id'];
    $resp = $api->getInvoice($rectId);
    if ($resp['code'] === 200) {
        $source = $resp['body']['invoice'];
        // Parsear entry_data para obtener detalles
        $data = is_array($source['entry_data']) ? $source['entry_data'] : json_decode($source['entry_data'], true);
        
        $prefill = [
            'tipo' => 'RECTIFICATIVA',
            'originalInvoiceId' => $rectId,
            'recipientName' => $data['recipient']['name'] ?? $source['recipient_name'],
            'recipientNIF' => $data['recipient']['nif'] ?? $source['recipient_nif'],
            'recipientAddress' => $data['recipient']['address'] ?? '',
            'concept' => 'Rectificación: ' . ($data['details']['items'][0]['concept'] ?? $data['concept'] ?? ''),
            'baseAmount' => ($data['breakdown']['baseAmount'] ?? $data['amount'] ?? 0) * -1, // Sugerir negativo
            'vatRate' => $data['breakdown']['vatRate'] ?? 21,
            'rectificationReason' => ''
        ];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar CSRF en el Frontend antes de enviar (Doble capa)
    if (!Security::validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Error de seguridad: Sesión expirada o token inválido.';
    } else {
        // Basic formatting
        $payload = [
            'type' => $_POST['tipo'], // Map 'tipo' (Frontend) to 'type' (Backend)
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
                    <label>Tipo de Registro</label>
                    <select id="tipoSelect" name="tipo" onchange="toggleFields()">
                        <option value="INITIAL" <?= (($_POST['tipo']??($prefill['tipo']??'')) === 'INITIAL') ? 'selected' : '' ?>>Factura Original</option>
                        <option value="RECTIFICATIVA" <?= (($_POST['tipo']??($prefill['tipo']??'')) === 'RECTIFICATIVA') ? 'selected' : '' ?>>Factura Rectificativa</option>
                    </select>
                </div>

                <!-- Seccion Económica -->
                <div class="form-group">
                    <label for="amount">Base Imponible (€)</label>
                    <input type="number" id="amount" name="amount" step="0.01" required placeholder="Ej: 100.00" value="<?= htmlspecialchars($_POST['amount'] ?? ($_GET['amount'] ?? ($prefill['baseAmount']??''))) ?>">
                </div>

                <div class="form-group">
                    <label for="vatRate">Tipo de IVA</label>
                    <select id="vatRate" name="vatRate" onchange="toggleExemptReason()">
                        <?php 
                        $currentRate = $_POST['vatRate'] ?? ($prefill['vatRate'] ?? '0'); 
                        ?>
                         <option value="0" <?= $currentRate == '0' ? 'selected' : '' ?>>Exento (0%)</option>
                        <option value="21" <?= $currentRate == '21' ? 'selected' : '' ?>>General (21%)</option>
                        <option value="10" <?= $currentRate == '10' ? 'selected' : '' ?>>Reducido (10%)</option>
                        <option value="4" <?= $currentRate == '4' ? 'selected' : '' ?>>Superreducido (4%)</option>
                         </select>
                </div>

                <div class="form-group" id="exemptReasonGroup" style="display:none;">
                     <label for="exemptReason">Motivo de Exención</label>
                     <select id="exemptReason" name="exemptReason">
                           <?php 
                        $currentexemptReason = $_POST['exemptReason'] ?? ($prefill['exemptReason'] ?? 'E1'); 
                        ?>
                         <option value="E1" <?= $currentexemptReason == 'E1' ? 'selected' : '' ?>>Art. 20 LIVA (Exentas Operaciones Interiores)</option>
                         <option value="E2" <?= $currentexemptReason == 'E2' ? 'selected' : '' ?>>Art. 21 LIVA (Exportaciones)</option>
                         <option value="E3" <?= $currentexemptReason == 'E3' ? 'selected' : '' ?>>Art. 25 LIVA (Entregas Intracomunitarias)</option>
                     </select>
                </div>

                <!-- Datos Receptor -->
                <div class="form-group">
                    <label for="recipientNIF">NIF Receptor (Opcional)</label>
                    <input type="text" id="recipientNIF" name="recipientNIF" 
                           pattern="^[A-Za-z0-9]{9}$"
                           title="Debe tener 9 caracteres alfanuméricos"
                           oninput="validateNifInfo(this)"
                           value="<?= htmlspecialchars($_POST['recipientNIF'] ?? ($prefill['recipientNIF']??'')) ?>">
                    <small id="nifFeedback" style="display:block; height:15px; font-size:11px; margin-top:2px;"></small>
                </div>

                <div class="form-group">
                    <label for="recipientName">Nombre / Razón Social Receptor</label>
                    <input type="text" id="recipientName" name="recipientName" required value="<?= htmlspecialchars($_POST['recipientName'] ?? ($prefill['recipientName']??'')) ?>">
                </div>

                <div class="form-group">
                    <label for="recipientAddress">Dirección Receptor (Factura E.)</label>
                    <input type="text" id="recipientAddress" name="recipientAddress" placeholder="Dirección completa del cliente" value="<?= htmlspecialchars($_POST['recipientAddress'] ?? ($prefill['recipientAddress']??'')) ?>">
                </div>

                <div class="form-group">
                    <label for="concept">Concepto</label>
                    <input type="text" id="concept" name="concept" placeholder="Descripción del servicio..." required minlength="5" value="<?= htmlspecialchars($_POST['concept'] ?? ($prefill['concept']??'')) ?>">
                </div>

                <!-- Rectificativa Fields -->
                <div id="rectificativaFields" style="display:none; border-left: 2px solid var(--warning); padding-left: 15px; margin-top: 20px;">
                    <h3 style="color:var(--warning); font-size:16px;">Datos de Rectificación</h3>
                    
                    <div class="form-group">
                        <label>Factura Original ID</label>
                        <input type="number" name="originalInvoiceId" placeholder="ID de la factura original" value="<?= htmlspecialchars($_POST['originalInvoiceId'] ?? ($prefill['originalInvoiceId']??'')) ?>">
                    </div>

                    <div class="form-group">
                        <label>Motivo</label>
                        <textarea name="reason" placeholder="Explique el motivo de la corrección..." required><?= htmlspecialchars($_POST['reason'] ?? ($prefill['rectificationReason']??'')) ?></textarea>
                    </div>
                </div>

                <button type="submit" class="btn" style="width:100%; margin-top:20px;">Registrar Factura</button>
            </form>
        </div>
    </div>

    <script>
        function toggleFields() {
            var tipo = document.getElementById('tipoSelect').value;
            var fields = document.getElementById('rectificativaFields');
            fields.style.display = (tipo === 'RECTIFICATIVA') ? 'block' : 'none';
        }

        function toggleExemptReason() {
            var rate = document.getElementById('vatRate');
            var reasonGroup = document.getElementById('exemptReasonGroup');
            var exemptReason = document.getElementById('exemptReason');
            
            if (rate && rate.value == '0') {
                reasonGroup.style.display = 'block';
                exemptReason.required = true;
            } else {
                reasonGroup.style.display = 'none';
                exemptReason.required = false;
                exemptReason.value = '';
            }
        }
        
        // Init on load
        document.addEventListener('DOMContentLoaded', function() {
            toggleExemptReason();
            toggleFields(); // Auto-show rect fields if selected
        });

        function validateForm() {
            var tipo = document.getElementById('tipoSelect').value;
            var amount = parseFloat(document.getElementById('amount').value);
            
            if (tipo === 'INITIAL' && amount < 0) {
                alert('Las facturas originales no pueden tener un importe negativo.');
                return false;
            }
            
            if (tipo === 'RECTIFICATIVA') {
                var reason = document.getElementsByName('reason')[0].value;
                if (reason.trim().length < 5) {
                    alert('Por favor, indique un motivo de rectificación válido (mín. 5 caracteres).');
                    return false;
                }
            }
            return true;
        }

        function validateNifInfo() {
            var nif = document.getElementById('recipientNIF').value;
            var feedback = document.getElementById('nifFeedback');
            var regex = /^[A-Za-z0-9]{9}$/;
            
            if (nif.length > 0 && !regex.test(nif)) {
                feedback.style.display = 'block';
                feedback.innerText = 'Formato incorrecto (9 caracteres)';
                document.getElementById('recipientNIF').style.borderColor = 'var(--danger)';
            } else {
                feedback.style.display = 'none';
                document.getElementById('recipientNIF').style.borderColor = (nif.length > 0) ? 'var(--success)' : 'var(--border-color)';
            }
        }
    </script>
</body>
</html>
