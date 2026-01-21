<?php
// test_signature.php

require_once __DIR__ . '/config/EnvLoader.php';
EnvLoader::load(__DIR__ . '/.env');

require_once __DIR__ . '/services/CertificateLoader.php';

echo "PRUEBA DE FIRMA DIGITAL\n";
echo "=======================\n";

$certPath = $_ENV['CERT_PATH'] ?? '';
$certPass = $_ENV['CERT_PASSWORD'] ?? '';

if (empty($certPath) || !file_exists($certPath)) {
    echo "[WARN] No se ha configurado 'CERT_PATH' o el archivo no existe.\n";
    echo "       Edite el archivo .env y apunte a su certificado .p12 real.\n";
    echo "       Ruta actual: '$certPath'\n";
    
    // Crear un certificado dummy autofirmado para pruebas si no existe
    echo "\n[INFO] Generando certificado de prueba (autofirmado) para validar lógica...\n";
    
    $dn = [
        "countryName" => "ES",
        "stateOrProvinceName" => "Madrid",
        "localityName" => "Madrid",
        "organizationName" => "Test Company",
        "organizationalUnitName" => "IT",
        "commonName" => "Test Cert",
        "emailAddress" => "admin@test.com"
    ];
    
    $privkey = openssl_pkey_new([
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA,
    ]);
    
    $csr = openssl_csr_new($dn, $privkey, ['digest_alg' => 'sha256']);
    $x509 = openssl_csr_sign($csr, null, $privkey, 365, ['digest_alg' => 'sha256']);
    
    $tempP12 = __DIR__ . '/dummy_cert.p12';
    $tempPass = '1234';
    openssl_pkcs12_export_to_file($x509, $tempP12, $privkey, $tempPass);
    
    $certPath = $tempP12;
    $certPass = $tempPass;
    echo "       Certificado dummy creado en: $certPath\n";
}

try {
    echo "\n[INFO] Cargando certificado...\n";
    $keys = CertificateLoader::loadPkcs12($certPath, $certPass);
    echo "       Certificado cargado correctamente.\n";
    echo "       ID Clave Pública: " . openssl_x509_parse($keys['cert'])['name'] . "\n";
    
    $data = "Datos de prueba para firmar " . time();
    echo "\n[INFO] Firmando datos de prueba: '$data'...\n";
    
    $signature = CertificateLoader::signData($data, $keys['pkey']);
    
    echo "       Firma generada (Base64): " . substr($signature, 0, 50) . "...\n";
    echo "\n[OK] El sistema está listo para firmar con certificados reales.\n";

} catch (Exception $e) {
    echo "\n[ERROR] " . $e->getMessage() . "\n";
}
?>
