<?php
// services/CertificateLoader.php

class CertificateLoader {
    private $certStore = [];

    /**
     * Carga un certificado PKCS#12 (.p12 o .pfx).
     * @param string $path Ruta absoluta al archivo .p12
     * @param string $password Contraseña del certificado
     * @return array ['cert' => string PEM, 'pkey' => string PEM]
     * @throws Exception
     */
    public static function loadPkcs12(string $path, string $password): array {
        if (!file_exists($path)) {
            throw new Exception("El archivo de certificado no existe en: $path");
        }

        $p12Content = file_get_contents($path);
        $certs = [];

        if (!openssl_pkcs12_read($p12Content, $certs, $password)) {
            throw new Exception("Error al leer el certificado PKCS#12. Verifique la contraseña o el formato.");
        }

        return [
            'cert' => $certs['cert'],      // Certificado público
            'pkey' => $certs['pkey'],      // Clave privada
            'extracerts' => $certs['extracerts'] ?? [] // Cadena de confianza
        ];
    }

    public static function signData(string $data, string $privateKey) {
        $signature = '';
        if (!openssl_sign($data, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
             throw new Exception("Fallo al firmar los datos.");
        }
        return base64_encode($signature);
    }
}
?>
