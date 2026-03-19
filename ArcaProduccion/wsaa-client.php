#!/usr/bin/php
<?php
#==============================================================================
# WSAA Client AFIP - Producción (Solo SoapClient)
#==============================================================================
define("WSDL", __DIR__ . "/wsaa.wsdl");                       
define("CERT", __DIR__ . "/ProduccionFinal.crt");                     
define("PRIVATEKEY", __DIR__ . "/privada.key");               
define("PASSPHRASE", "xxxxx");                                
define("URL", "https://wsaa.afip.gov.ar/ws/services/LoginCms"); 
define("CA_CHAIN", __DIR__ . "/cadena-nueva.pem");
#==============================================================================

ini_set("soap.wsdl_cache_enabled", "0");
ini_set('default_socket_timeout', 60);

function CreateTRA($SERVICE)
{
    $TRA = new SimpleXMLElement(
        '<?xml version="1.0" encoding="UTF-8"?><loginTicketRequest version="1.0"></loginTicketRequest>'
    );

    $TRA->addChild('header');
    $TRA->header->addChild('uniqueId', time());
    $TRA->header->addChild('generationTime', date('c', time()-600));
    $TRA->header->addChild('expirationTime', date('c', time()+600));
    $TRA->addChild('service',$SERVICE);
    $TRA->asXML('TRA.xml');
    echo "✓ TRA creado para servicio: $SERVICE\n";
}

function SignTRA()
{
    echo "Firmando TRA...\n";
    
    $status = openssl_pkcs7_sign(
        "TRA.xml",
        "TRA.tmp",
        "file://".CERT,
        array("file://".PRIVATEKEY, PASSPHRASE),
        array(),
        !PKCS7_DETACHED
    );

    if (!$status) { 
        $errors = [];
        while (($error = openssl_error_string()) !== false) {
            $errors[] = $error;
        }
        exit("ERROR al firmar PKCS#7: " . implode(", ", $errors) . "\n"); 
    }

    $inf = fopen("TRA.tmp", "r");
    $i = 0;
    $CMS = "";
    while (!feof($inf)) {
        $buffer = fgets($inf);
        if ($i++ >= 4) { $CMS .= $buffer; }
    }
    fclose($inf);
    unlink("TRA.tmp");
    
    echo "✓ TRA firmado correctamente\n";
    return $CMS;
}

function CallWSAA($CMS)
{
    echo "Conectando a WSAA AFIP...\n";
    
    // Primero intentar con certificados SSL completos
    try {
        echo "Intentando con autenticación SSL completa...\n";
        
        // Leer certificados
        $certData = file_get_contents(CERT);
        $keyData = file_get_contents(PRIVATEKEY);
        
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'cafile' => CA_CHAIN,
                'local_cert' => CERT,
                'local_pk' => PRIVATEKEY,
                'passphrase' => PASSPHRASE,
                'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
                'ciphers' => 'HIGH:!SSLv2:!SSLv3'
            ],
            'http' => [
                'timeout' => 60,
                'user_agent' => 'WSAA-Client-PHP/1.0'
            ]
        ]);

        $client = new SoapClient(WSDL, array(
            'soap_version'   => SOAP_1_2,
            'location'       => URL,
            'trace'          => 1,
            'exceptions'     => true,
            'connection_timeout' => 60,
            'stream_context' => $context,
            'cache_wsdl' => WSDL_CACHE_NONE
        ));

        $results = $client->loginCms(array('in0' => $CMS));
        
        // Guardar para debugging
        file_put_contents("request-loginCms.xml", $client->__getLastRequest());
        file_put_contents("response-loginCms.xml", $client->__getLastResponse());
        
        if (is_soap_fault($results)) {
            throw new Exception("SOAP Fault: {$results->faultcode} - {$results->faultstring}");
        }
        
        echo "✓ Autenticación SSL exitosa\n";
        return $results->loginCmsReturn;

    } catch (Exception $e) {
        echo "✗ Falló autenticación SSL: " . $e->getMessage() . "\n";
    }

    // Si falla, intentar con configuración más permisiva
    try {
        echo "Intentando con configuración SSL permisiva...\n";
        
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ],
            'http' => [
                'timeout' => 60
            ]
        ]);

        $client = new SoapClient(WSDL, array(
            'soap_version'   => SOAP_1_2,
            'location'       => URL,
            'trace'          => 1,
            'exceptions'     => true,
            'connection_timeout' => 60,
            'stream_context' => $context,
            'cache_wsdl' => WSDL_CACHE_NONE
        ));

        $results = $client->loginCms(array('in0' => $CMS));
        
        file_put_contents("request-loginCms.xml", $client->__getLastRequest());
        file_put_contents("response-loginCms.xml", $client->__getLastResponse());
        
        if (is_soap_fault($results)) {
            throw new Exception("SOAP Fault: {$results->faultcode} - {$results->faultstring}");
        }
        
        echo "⚠ Conexión exitosa con SSL permisivo\n";
        return $results->loginCmsReturn;

    } catch (Exception $e) {
        echo "✗ También falló SSL permisivo: " . $e->getMessage() . "\n";
        
        // Mostrar detalles del último request/response si están disponibles
        if (isset($client)) {
            echo "\n--- ÚLTIMO REQUEST ---\n";
            echo $client->__getLastRequest() . "\n";
            echo "\n--- ÚLTIMA RESPONSE ---\n";
            echo $client->__getLastResponse() . "\n";
        }
        
        exit("ERROR: No se pudo establecer conexión con WSAA\n");
    }
}

function ShowUsage($MyPath)
{
    printf("Uso  : %s Arg#1\n", $MyPath);
    printf("donde: Arg#1 debe ser el service name del WS de negocio.\n");
    printf("  Ej.: %s wsfe\n", $MyPath);
}

#==============================================================================
echo "=== WSAA CLIENT AFIP PRODUCCIÓN ===\n";

// Verificar archivos necesarios
$requiredFiles = [
    CERT => 'Certificado cliente',
    PRIVATEKEY => 'Clave privada', 
    WSDL => 'WSDL',
    CA_CHAIN => 'Cadena de confianza'
];

foreach ($requiredFiles as $file => $desc) {
    if (!file_exists($file)) {
        exit("ERROR: No se encuentra $desc: $file\n");
    }
    echo "✓ $desc encontrado\n";
}

if ($argc < 2) { 
    ShowUsage($argv[0]); 
    exit(); 
}

$SERVICE = $argv[1];
echo "\n--- Procesando servicio: $SERVICE ---\n";

CreateTRA($SERVICE);
$CMS = SignTRA();
$TA = CallWSAA($CMS);

if (!$TA) {
    exit("ERROR: No se recibió un TA válido\n");
}

if (!file_put_contents("TA.xml", $TA)) { 
    exit("ERROR: No se pudo escribir TA.xml\n"); 
}

echo "\n✓ TA generado correctamente en TA.xml\n";
echo "Tamaño del TA: " . strlen($TA) . " bytes\n";

// Validar básicamente el TA generado
if (strpos($TA, '<credentials>') !== false && strpos($TA, '<token>') !== false) {
    echo "✓ TA parece válido (contiene credentials y token)\n";
} else {
    echo "⚠ TA generado pero formato inusual - revisar contenido\n";
}

echo "\n=== PROCESO COMPLETADO ===\n";
?>