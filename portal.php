<?php
// facturas/portal.php - Portal de Verificación para el Paciente
require_once __DIR__ . '/config/EnvLoader.php';
EnvLoader::load(__DIR__ . '/.env');

require_once __DIR__ . '/controllers/InvoiceController.php';
require_once __DIR__ . '/utils/Security.php';

$id = $_GET['id'] ?? null;
$sign = $_GET['s'] ?? '';

if (!$id || !$sign) {
    die("Acceso denegado: Parámetros insuficientes.");
}

$controller = new InvoiceController();
$service = new ChainService();
$invoice = $service->getInvoiceById((int)$id);

if (!$invoice) {
    die("Factura no encontrada.");
}

// Verificar firma de acceso público
$expectedSign = Security::generatePublicSignature($id, $invoice['current_hash']);
if (!hash_equals($expectedSign, $sign)) {
    die("Acceso denegado: Firma de seguridad inválida.");
}

$data = json_decode($invoice['entry_data'], true);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal de Facturación Electrónica - Everest</title>
    <link rel="stylesheet" href="frontend/assets/style.css">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #0d1117 0%, #161b22 100%);
            --accent-glow: 0 0 20px rgba(88, 166, 255, 0.2);
            --accent: #58a6ff;
            --border-color: #30363d;
            --text-secondary: #8b949e;
        }
        body {
            background: var(--primary-gradient);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0;
            padding: 0;
            color: #fff;
        }
        .portal-container {
            max-width: 750px;
            margin: 0 auto;
            padding: 20px;
        }
        .invoice-card {
            background: rgba(22, 27, 34, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-color);
            box-shadow: var(--accent-glow);
            border-radius: 12px;
            padding: 30px;
            position: relative;
        }
        .header-logo {
            text-align: center;
            margin-bottom: 20px;
        }
        .guarantees-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(46, 160, 67, 0.1);
            color: #3fb950;
            padding: 8px 16px;
            border-radius: 50px;
            border: 1px solid rgba(46, 160, 67, 0.3);
            font-size: 13px;
            margin-bottom: 20px;
        }
        .data-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid rgba(48, 54, 61, 0.5);
            max-width: 500px;
            margin: 0 auto;
        }
        .data-label { color: var(--text-secondary); font-size: 13px; }
        .data-value { color: #fff; font-weight: 500; font-size: 14px; }
        
        .hash-box {
            background: rgba(0,0,0,0.2);
            padding: 15px;
            border-radius: 6px;
            border: 1px solid var(--border-color);
        }

        .btn {
            background: var(--border-color);
            color: #fff;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            border: 1px solid #444c56;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body { 
                background: #fff !important; 
                color: #000 !important;
                padding: 0;
                margin: 0;
            }
            .portal-container { 
                max-width: 100%; 
                width: 100%;
                margin: 0;
                padding: 1cm;
            }
            .invoice-card { 
                box-shadow: none !important; 
                border: 1px solid #eee !important;
                background: #fff !important;
                backdrop-filter: none !important;
                padding: 0;
            }
            .header-logo h1 { color: #000 !important; }
            .data-value { color: #000 !important; }
            .data-label { color: #555 !important; }
            .btn, .descargar-link, .aeat-box { display: none !important; }
            .guarantees-badge { border: 1px solid #ccc !important; color: #000 !important; }
            .hash-box { background: #f9f9f9 !important; border: 1px solid #ddd !important; color: #333 !important; }
            h2, h3, span { color: #000 !important; }
            .amount-box { 
            align-items:right;
            justify-content:flex-end; 
            width:50%; 
            background: linear-gradient(90deg, rgba(88, 166, 255, 0.1) 0%, rgba(88, 166, 255, 0.05) 100%); 
            padding: 5px; 
            padding-right:15px; 
            border-radius: 12px; 
            margin: 15px; 
            text-align:right;
            margin-left:auto;
            margin-right:5px;
            
            display:flex;
            align-items:center;
            justify-content:space-between;
            }
            .amount-box span { color: #000 !important; text-shadow: none !important; }
            .qr-container { 
                display: flex !important; 
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                margin: 20px auto !important;
                width: 100% !important;
            }
            .qr-container img {
                filter: grayscale(1) contrast(1.2);
                width: 100px !important;
                height: 100px !important;
            }
            .qr-container div img:last-child {
                width: 25px !important;
                height: auto !important;
                filter: none !important;
                position: absolute !important;
                top: 50% !important;
                left: 50% !important;
                transform: translate(-50%, -50%) !important;
                background: white !important;
            }
        }
        .qr-container {
            width: 75%;
            margin: 30px auto 0 auto;
            text-align: center;
            padding: 15px;
            border-top: 1px dashed var(--border-color);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }
        .qr-container img {
            align-self: center;
            background: white;
            padding: 5px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        .qr-text {
            display: block;
            font-size: 11px;
            color: var(--text-secondary);
            margin-top: 8px;
        }

        .amount-box { 
            align-items:right;
            justify-content:flex-end; 
            width:50%; 
            background: linear-gradient(90deg, rgba(88, 166, 255, 0.1) 0%, rgba(88, 166, 255, 0.05) 100%); 
            padding: 5px; 
            padding-right:15px; 
            border-radius: 12px; 
            margin: 15px; 
            text-align:right;
            margin-left:auto;
            margin-right:5px;
            
            display:flex;
            align-items:center;
            justify-content:space-between;
        }

        .datos-box { 

            margin:5px; 
            padding: 5px; 
            border-radius: 12px; 
            border: 1px solid rgba(88, 166, 255, 0.2);
        }
    </style>
</head>
<body>
    <div class="portal-container">
        <div class="header-logo">
                <div style="width:75%; display:flex; align-items:center; justify-content:space-between; margin: 0 auto;">
                     <img src="/jesus/everest2026/imagenes/logoeve.jpg" alt="Logo" width="120" style="border-radius:10px; box-shadow: 0 4px 12px rgba(0,0,0,0.5); object-fit: contain;">
                     <div style="flex: 1; text-align: center; padding-left: 20px;">
                        <h1 style="margin:0; font-size:28px; color:#fff; letter-spacing:-0.5px;">Portal de Facturación Electrónica</h1>
                        <h2 style="color:var(--text-secondary); font-size:14px; margin: 5px 0;"><strong>FISIOTERAPIA EVEREST, S.L.</strong> | CIF: B87715025</h2>
                        <p style="color:var(--text-secondary); font-size:12px; opacity:0.8; margin: 0;">Pza. Valencia 5 (esq. C/Barcelona 9) - Móstoles</p>
                     </div>
                </div>
        </div>

        <div class="invoice-card ">
            <div style="text-align:center;">
                <div class="guarantees-badge">
                    <svg style="width:18px; height:18px; margin-right:10px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <strong>Garantía de Integridad:</strong> Este registro no puede ser alterado.
                </div>
            </div>

            <div class="datos-box">
                 <h2 style="font-size:18px; margin-bottom:5px; color:var(--accent);">Resumen de Operación</h2>
                <div class="data-row">
                    <span class="data-label">Número de Factura Electrónica</span>
                    <span class="data-value" style="font-family:monospace; color:var(--accent); font-weight:bold;">VF-<?= date('Y', strtotime($invoice['timestamp'])) ?>-<?= str_pad($id, 5, '0', STR_PAD_LEFT) ?></span>
                </div>
                <div class="data-row ">
                    <span class="data-label">Fecha de Emisión</span>
                    <span class="data-value"><?= date('d/m/Y H:i:s', strtotime($invoice['timestamp'])) ?></span>
                </div>
            
            </div>

            <?php if (isset($data['fechacita'])): ?>
              <div class="datos-box" style="background: linear-gradient(90deg, rgba(88, 166, 255, 0.1) 0%, rgba(88, 166, 255, 0.05) 100%); margin:5px; padding: 5px; border-radius: 12px; text-align: center; border: 1px solid rgba(88, 166, 255, 0.2);"> 
                    <span class="data-label" style="color:#fff;">Sesión de Fisioterapia</span>
                    <span class="data-value"><?= date('d/m/Y', strtotime(str_replace('/','-',$data['fechacita']))) ?> a las <?= $data['horacita'] ?></span>
                </div>
            <?php endif; ?>

        <div class="datos-box" >
           <h2 style="padding:15px; margin-left:5px; font-size:18px; margin-bottom:5px; color:var(--accent);">Datos del Receptor</h2>
                <div style="padding: 0 10px 20px 10px;">
                <div class="data-row">
                    <span class="data-label">Nombre / Razón Social</span>
                    <span class="data-value"><?= htmlspecialchars($data['recipientName']) ?></span>
                </div>
                <div class="data-row">
                    <span class="data-label">NIF / CIF</span>
                    <span class="data-value"><?= htmlspecialchars($data['recipientNIF']) ?></span>
                </div>
                <div class="data-row">
                    <span class="data-label">Concepto</span>
                    <span class="data-value" style="text-align:right; max-width:60%;"><?= htmlspecialchars($data['concept']) ?></span>
                </div>
            </div>
            </div>
            <div class="amount-box" >
                <div style="text-align:right;">
                <span style="color:var(--text-secondary); display:block; margin:4px; text-transform:uppercase; font-size:11px; letter-spacing:1px;">Importe Total Devengado</span>
                <span style="font-size:25px; font-weight:800; color:#fff; text-shadow: 0 0 15px rgba(88, 166, 255, 0.3);"><?= number_format((float)$data['amount'], 2, ',', '.') ?> €</span>
                </div>
            </div>

            <div style="margin-top:25px; padding: 15px; background: rgba(0,0,0,0.1); border-radius: 8px;">
                <h3 style="font-size:13px; color:var(--text-secondary); margin-bottom:8px; display:flex; align-items:center;">
                    <svg style="width:14px; margin-right:6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    Huella Digital Criptográfica (Chain Link)
                </h3>
                <div class="hash-box" style="margin-top:5px; font-size:10px; opacity:0.7; word-break:break-all; line-height:1.3; font-family:monospace;">
                   <?= $invoice['current_hash'] ?>
                </div>
            </div>

            <div class="qr-container">
                <div style="position:relative; display:inline-block; background:#fff; padding:5px; border-radius:8px; border:1px solid var(--border-color);">
                    <img src="index.php?action=qr&id=<?= $id ?>" alt="QR VeriFactu" width="100" height="100" style="display:block;">
                    <img src="/jesus/everest2026/imagenes/logoeve.jpg" style="position:absolute; top:50%; left:50%; transform:translate(-50%, -50%); width:30px; height:auto; background:white; padding:2px; border:1px solid #eee;">
                </div>
                <span class="qr-text">Escanea este código para verificar la autenticidad de la factura electrónica</span>
            </div>
            
            <div style="margin-top:30px; text-align:center; display:flex; gap:15px; justify-content:center;">
                 <a href="index.php?action=download&id=<?= $id ?>" class="btn descargar-link">
                    <svg style="width:14px; margin-right:6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                    JSON Original
                 </a>
                 <button onclick="window.print()" class="btn">
                    <svg style="width:14px; margin-right:6px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                    Imprimir Factura
                 </button>
            </div>
        </div>
        
        <p style="text-align:center; color:var(--text-secondary); font-size:10px; margin-top:30px; opacity:0.6;">
            &copy; <?= date('Y') ?> Fisioterapia Everest. Sistema de Facturación Electrónica Certificado.<br>
            Powered by VeriFactu Secure Chain Technology.
        </p>
    </div>
</body>
</html>
