<?php
#==============================================================================
# Script de Diagnóstico para WSAA AFIP
#==============================================================================

echo "=== DIAGNÓSTICO DE CONECTIVIDAD WSAA AFIP ===\n\n";

// 1. Verificar conectividad básica
echo "1. Verificando conectividad básica...\n";
$host = "wsaa.afip.gov.ar";
$port = 443;

$connection = @fsockopen($host, $port, $errno, $errstr, 30);
if ($connection) {
    echo "✓ Conectividad TCP a $host:$port OK\n";
    fclose($connection);
} else {
    echo "✗ ERROR: No se puede conectar a $host:$port - $errstr ($errno)\n";
}

// 2. Verificar resolución DNS
echo "\n2. Verificando resolución DNS...\n";
$ip = gethostbyname($host);
if ($ip !== $host) {
    echo "✓ DNS OK: $host -> $ip\n";
} else {
    echo "✗ ERROR: No se puede resolver DNS para $host\n";
}

// 3. Verificar cURL básico
echo "\n3. Verificando cURL básico...\n";
if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://wsaa.afip.gov.ar/ws/services/LoginCms?wsdl");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Solo para test
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // Solo para test
    
    $result = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($result && $httpCode == 200) {
        echo "✓ cURL OK: WSDL descargado correctamente\n";
    } else {
        echo "✗ ERROR cURL: $error (HTTP: $httpCode)\n";
    }
} else {
    echo "✗ ERROR: cURL no está instalado\n";
}

// 4. Verificar SOAP
echo "\n4. Verificando SOAP...\n";
if (class_exists('SoapClient')) {
    echo "✓ SoapClient disponible\n";
    
    try {
        // Test básico sin SSL estricto
        $client = new SoapClient("https://wsaa.afip.gov.ar/ws/services/LoginCms?wsdl", array(
            'soap_version' => SOAP_1_2,
            'trace' => 1,
            'exceptions' => true,
            'connection_timeout' => 30,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false
                ]
            ])
        ));
        echo "✓ SoapClient puede conectar al WSDL\n";
    } catch (Exception $e) {
        echo "✗ ERROR SoapClient: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ ERROR: SOAP no está instalado\n";
}

// 5. Verificar archivos certificados
echo "\n5. Verificando archivos de certificados...\n";

$files = [
    'ProduccionFinal.crt' => 'Certificado cliente',
    'privada.key' => 'Clave privada',
    'cadena-completa.pem' => 'Cadena de confianza',
    'wsaa.wsdl' => 'WSDL'
];

foreach ($files as $file => $desc) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "✓ $desc ($file): $size bytes\n";
        
        // Verificar contenido del certificado
        if (strpos($file, '.crt') !== false || strpos($file, '.pem') !== false) {
            $content = file_get_contents($file);
            if (strpos($content, '-----BEGIN CERTIFICATE-----') !== false) {
                echo "  ✓ Formato de certificado válido\n";
            } else {
                echo "  ✗ Formato de certificado inválido\n";
            }
        }
    } else {
        echo "✗ $desc ($file): ARCHIVO NO ENCONTRADO\n";
    }
}

// 6. Verificar configuración de red
echo "\n6. Configuración de red y proxy...\n";
$proxy = getenv('http_proxy') ?: getenv('HTTP_PROXY');
if ($proxy) {
    echo "⚠ Proxy detectado: $proxy\n";
} else {
    echo "✓ No hay proxy configurado\n";
}

// 7. Test de conectividad con diferentes métodos
echo "\n7. Test de conectividad alternativo...\n";
$context = stream_context_create([
    'http' => [
        'timeout' => 30,
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$wsdl_content = @file_get_contents("https://wsaa.afip.gov.ar/ws/services/LoginCms?wsdl", false, $context);
if ($wsdl_content) {
    echo "✓ file_get_contents puede descargar el WSDL\n";
    echo "  Tamaño WSDL: " . strlen($wsdl_content) . " bytes\n";
} else {
    echo "✗ file_get_contents no puede descargar el WSDL\n";
}

echo "\n=== FIN DEL DIAGNÓSTICO ===\n";
?>