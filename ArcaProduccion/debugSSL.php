<?php
#==============================================================================
# Debug de Certificados SSL para WSAA AFIP
#==============================================================================

echo "=== DEBUG DE CERTIFICADOS SSL ===\n\n";

// 1. Verificar certificado cliente
echo "1. Verificando certificado cliente...\n";
$certPath = __DIR__ . "/ProduccionFinal.crt";
if (file_exists($certPath)) {
    $certData = file_get_contents($certPath);
    $certInfo = openssl_x509_parse($certData);
    
    if ($certInfo) {
        echo "✓ Certificado válido\n";
        echo "  Sujeto: " . $certInfo['name'] . "\n";
        echo "  Emisor: " . $certInfo['issuer']['CN'] . "\n";
        echo "  Válido desde: " . date('Y-m-d H:i:s', $certInfo['validFrom_time_t']) . "\n";
        echo "  Válido hasta: " . date('Y-m-d H:i:s', $certInfo['validTo_time_t']) . "\n";
        
        // Verificar si está vencido
        if (time() > $certInfo['validTo_time_t']) {
            echo "  ⚠ CERTIFICADO VENCIDO\n";
        } else {
            echo "  ✓ Certificado vigente\n";
        }
    } else {
        echo "✗ Error al parsear certificado\n";
    }
} else {
    echo "✗ Certificado no encontrado\n";
}

// 2. Verificar clave privada
echo "\n2. Verificando clave privada...\n";
$keyPath = __DIR__ . "/privada.key";
if (file_exists($keyPath)) {
    $keyData = file_get_contents($keyPath);
    $keyResource = openssl_pkey_get_private($keyData, "xxxxx"); // Usar tu passphrase real
    
    if ($keyResource) {
        echo "✓ Clave privada válida y passphrase correcto\n";
        
        // Verificar que el certificado y la clave coincidan
        if (openssl_x509_check_private_key($certData, $keyResource)) {
            echo "✓ Certificado y clave privada coinciden\n";
        } else {
            echo "✗ Certificado y clave privada NO coinciden\n";
        }
    } else {
        echo "✗ Error al cargar clave privada (verificar passphrase)\n";
    }
} else {
    echo "✗ Clave privada no encontrada\n";
}

// 3. Verificar cadena de confianza
echo "\n3. Verificando cadena de confianza...\n";
$chainPath = __DIR__ . "/cadena-completa.pem";
if (file_exists($chainPath)) {
    $chainData = file_get_contents($chainPath);
    
    // Contar certificados en la cadena
    $certCount = substr_count($chainData, '-----BEGIN CERTIFICATE-----');
    echo "✓ Cadena contiene $certCount certificado(s)\n";
    
    // Verificar cada certificado en la cadena
    $certs = explode('-----END CERTIFICATE-----', $chainData);
    foreach ($certs as $i => $cert) {
        if (trim($cert)) {
            $cert .= '-----END CERTIFICATE-----';
            $certInfo = openssl_x509_parse($cert);
            if ($certInfo) {
                echo "  Certificado " . ($i+1) . ": " . $certInfo['issuer']['CN'] . "\n";
            }
        }
    }
} else {
    echo "✗ Cadena de confianza no encontrada\n";
}

// 4. Test de conexión SSL específico
echo "\n4. Test de conexión SSL a AFIP...\n";
$context = stream_context_create([
    "ssl" => [
        "capture_peer_cert" => true,
        "verify_peer" => false,
        "verify_peer_name" => false
    ]
]);

$stream = @stream_socket_client(
    "ssl://wsaa.afip.gov.ar:443", 
    $errno, 
    $errstr, 
    30, 
    STREAM_CLIENT_CONNECT, 
    $context
);

if ($stream) {
    $params = stream_context_get_params($stream);
    $cert = openssl_x509_parse($params['options']['ssl']['peer_certificate']);
    
    echo "✓ Conexión SSL establecida\n";
    echo "  Certificado servidor: " . $cert['subject']['CN'] . "\n";
    echo "  Emisor: " . $cert['issuer']['CN'] . "\n";
    echo "  Válido hasta: " . date('Y-m-d H:i:s', $cert['validTo_time_t']) . "\n";
    
    fclose($stream);
} else {
    echo "✗ No se pudo establecer conexión SSL: $errstr ($errno)\n";
}

// 5. Descargar certificados de AFIP actuales
echo "\n5. Descargando certificados actuales de AFIP...\n";
$cmd = "openssl s_client -connect wsaa.afip.gov.ar:443 -showcerts < /dev/null 2>/dev/null | openssl x509 -outform PEM";
$afipCert = shell_exec($cmd);

if ($afipCert) {
    echo "✓ Certificado de AFIP descargado\n";
    
    // Comparar con nuestra cadena
    if (strpos($chainData, trim($afipCert)) !== false) {
        echo "✓ Nuestro certificado de cadena coincide con el actual de AFIP\n";
    } else {
        echo "⚠ Nuestro certificado de cadena puede estar desactualizado\n";
        file_put_contents("afip_cert_actual.pem", $afipCert);
        echo "  Certificado actual guardado en: afip_cert_actual.pem\n";
    }
} else {
    echo "✗ No se pudo descargar certificado actual de AFIP\n";
}

echo "\n=== FIN DEBUG ===\n";
?>