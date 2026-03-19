<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();

require __DIR__ . '/../../Libs/fpdf/fpdf.php';
require __DIR__ . '/../../Panel/includes/conexion.php';

// ==========================
// DATOS DEL FORMULARIO
// ==========================
$productos = json_decode($_POST['productos'] ?? '[]', true);
$id_cliente = $_POST['id_cliente'] ?? 0;
$tipo_comprobante_input = $_POST['tipo_comprobante'] ?? 6; // default B
$alicuotaIVA = floatval($_POST['alicuotaIVA'] ?? 21);
$pto_vta = 3;

// Filtrar productos válidos
$productos = array_filter($productos, fn($p) => !empty($p['Nombre']));

// ==========================
// INSERTAR PRODUCTOS MANUALES EN LA BD
// ==========================
foreach ($productos as &$p) {
    if (empty($p['IdProducto']) || $p['IdProducto'] == 0) {
        $stmtProd = $conn->prepare("INSERT INTO Producto (nombre, precio_compra, stock) VALUES (?, ?, 0)");
        $stmtProd->bind_param("sd", $p['Nombre'], $p['PrecioFinal']);
        $stmtProd->execute();
        $p['IdProducto'] = $stmtProd->insert_id;
        $stmtProd->close();
    }
}
unset($p);

// ==========================
// VALIDAR QUE HAYA PRODUCTOS
// ==========================
if (count($productos) === 0) die("❌ ERROR: No hay productos válidos para enviar a AFIP");

// ==========================
// CLIENTE
// ==========================
$clienteNombre = "Consumidor Final";
if ($id_cliente > 0) {
    $stmt = $conn->prepare("SELECT nombre, apellido FROM Cliente WHERE id_cliente = ?");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $res = $stmt->get_result();
    $c = $res->fetch_assoc();
    if ($c) $clienteNombre = $c['nombre'] . ' ' . $c['apellido'];
} else if(!empty($_POST['cliente_json'])) {
    $cli = json_decode($_POST['cliente_json'], true);
    if ($cli) $clienteNombre = ($cli['nombre'] ?? '') . ' ' . ($cli['apellido'] ?? '');
}
$clienteNombre = iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $clienteNombre);

// ==========================
// MAPEO TIPO COMPROBANTE
// ==========================
$tipoMap = [1=>'A',6=>'B',4=>'M'];
$CbteTipoMap = ['A'=>1,'B'=>6,'M'=>4];
$tipo_factura = $tipoMap[$tipo_comprobante_input] ?? 'B';
$CbteTipo = $CbteTipoMap[$tipo_factura];

// ==========================
// CÁLCULO DE TOTALES Y DETALLE DE IVA AGRUPADO POR ALÍCUOTA
// ==========================
$impNeto = 0;
$impIVA = 0;
$ivaAcumulado = [];

foreach ($productos as $p) {
    $cantidad = $p['Cantidad'] ?? 0;
    $precioFinal = $p['PrecioFinal'] ?? 0; // ya incluye IVA 21%

    // Separar base e IVA
    $precioBase = $precioFinal;      // Base sin IVA
    $importeIVA = $precioBase * 0.21; // IVA 21%

    $impNeto += $precioBase;
    $impIVA += $importeIVA;

    $idIVA = $p['IdIVA'] ?? 5;
    if (!isset($ivaAcumulado[$idIVA])) {
        $ivaAcumulado[$idIVA] = [
            'Id' => $idIVA,
            'BaseImp' => 0,
            'Importe' => 0
        ];
    }
    $ivaAcumulado[$idIVA]['BaseImp'] += $precioBase;
    $ivaAcumulado[$idIVA]['Importe'] += $importeIVA;
}


// Preparar array final para AFIP
$ivaDetalle = [];
foreach ($ivaAcumulado as $ivaLinea) {
    $ivaDetalle[] = [
        'Id' => $ivaLinea['Id'],
        'BaseImp' => number_format($ivaLinea['BaseImp'], 2, '.', ''),
        'Importe' => number_format($ivaLinea['Importe'], 2, '.', '')
    ];
}

$impTotal = $impNeto + $impIVA;

// ==========================
// CONEXIÓN CON AFIP
// ==========================
$taFile = __DIR__ . '/../../ArcaProduccion/TA.xml';
if (!file_exists($taFile)) die("❌ Error: TA.xml no encontrado");
$ta = simplexml_load_file($taFile);
$token = (string)$ta->credentials->token;
$sign = (string)$ta->credentials->sign;

$context = stream_context_create([
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true,
        'crypto_method' => STREAM_CRYPTO_METHOD_TLS_CLIENT,
        'ciphers' => 'HIGH:!SSLv2:!SSLv3'
    ]
]);

$client = new SoapClient('https://servicios1.afip.gov.ar/wsfev1/service.asmx?wsdl', [
    'soap_version' => SOAP_1_2,
    'trace' => 1,
    'exceptions' => 1,
    'cache_wsdl' => WSDL_CACHE_NONE,
    'stream_context' => $context
]);


$auth = ['Token'=>$token,'Sign'=>$sign,'Cuit'=>20398443609];

// ==========================
// 🔍 VERIFICACIÓN DETALLADA PUNTO DE VENTA 3 
// ==========================
try {
    echo "<h3>🔍 VERIFICACIÓN DETALLADA PUNTO DE VENTA 3</h3>";
    
    $parametros = $client->FEParamGetPtosVenta([
        'Auth' => $auth
    ]);
    
    // 🔎 DEBUG: Mostrar respuesta completa
    echo "<h4>📊 Respuesta completa de AFIP:</h4>";
    echo "<pre>";
    var_dump($parametros);
    echo "</pre>";
    
    // 🔎 Mostrar XML de respuesta
    echo "<h4>📑 XML Response:</h4>";
    echo "<pre>" . htmlspecialchars($client->__getLastResponse()) . "</pre>";
    
    if (isset($parametros->FEParamGetPtosVentaResult->ResultGet->PtoVenta)) {
        $ptosVenta = $parametros->FEParamGetPtosVentaResult->ResultGet->PtoVenta;
        
        // ... resto del código de verificación ...
        
    } else {
        echo "❌ No se pudieron obtener los puntos de venta<br>";
        
        // Verificar si hay errores en la respuesta
        if (isset($parametros->FEParamGetPtosVentaResult->Errors)) {
            echo "<h4>❌ Errores AFIP:</h4>";
            var_dump($parametros->FEParamGetPtosVentaResult->Errors);
        }
        
        if (isset($parametros->FEParamGetPtosVentaResult->Events)) {
            echo "<h4>⚠️ Eventos AFIP:</h4>";
            var_dump($parametros->FEParamGetPtosVentaResult->Events);
        }
    }
    
} catch (Exception $e) {
    echo "<h4>❌ Excepción:</h4>";
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Trace: " . $e->getTraceAsString();
}


// ==========================
// OBTENER NÚMERO DE FACTURA
// ==========================
try {
    $ultimo = $client->FECompUltimoAutorizado([
        'Auth'=>$auth,
        'PtoVta'=>$pto_vta,
        'CbteTipo'=>$CbteTipo
    ]);
    $numeroFactura = $ultimo->FECompUltimoAutorizadoResult->CbteNro + 1;
} catch (Exception $e) {
    die("❌ ERROR AFIP Ultimo Comprobante: " . $e->getMessage());
}
// ==========================
// CONFIGURAR ZONA HORARIA ARGENTINA
// ==========================
date_default_timezone_set('America/Argentina/Buenos_Aires');
$fechaAFIP = date('Ymd');

// ==========================
// PREPARAR FECAEDetRequest
// ==========================
$feDetRequest = [
    'Concepto' => 1,
    'DocTipo' => 99,
    'DocNro' => 0,
    'CbteDesde' => $numeroFactura,
    'CbteHasta' => $numeroFactura,
    'CbteFch' => date('Ymd'),
    'ImpTotal' => round($impTotal,2),
    'ImpTotConc' => 0.00,
    'ImpNeto' => round($impNeto,2),
    'ImpOpEx' => 0.00,
    'ImpTrib' => 0.00,
    'ImpIVA' => round($impIVA,2),
    'MonId' => 'PES',
    'MonCotiz' => 1,
    'CondicionIVAReceptorId' => 5
];

// Solo agregar IVA si hay
if ($impIVA > 0) $feDetRequest['Iva'] = ['AlicIva' => $ivaDetalle];

$feCAEReq = [
    
    'FeCabReq' => [
        'CantReg' => 1,
        'PtoVta' => $pto_vta,
        'CbteTipo' => $CbteTipo
    ],
    'FeDetReq' => ['FECAEDetRequest' => $feDetRequest]
];

//  echo "<h3>📤 Array enviado a AFIP</h3>";
//  echo "<pre>";
//  print_r([
//      'Auth' => $auth,
//      'FeCAEReq' => $feCAEReq
//  ]);
//  echo "</pre>";

// ==========================
// ENVIAR FACTURA A AFIP
// ==========================
try {
    if($impTotal <= 0) die("❌ ERROR: Total factura = 0, no se puede enviar.");

    $resp = $client->FECAESolicitar([
        'Auth' => $auth,
        'FeCAEReq' => $feCAEReq
    ]);

    
//  🔎 DEBUG: Mostrar XML real enviado y recibido
echo "<h3>📑 XML Request</h3>";
echo "<pre>" . htmlspecialchars($client->__getLastRequest()) . "</pre>";

 echo "<h3>📑 XML Response</h3>";
 echo "<pre>" . htmlspecialchars($client->__getLastResponse()) . "</pre>";


    // Validar respuesta
    if (isset($resp->FECAESolicitarResult->FeDetResp->FECAEDetResponse)) {
        $det = $resp->FECAESolicitarResult->FeDetResp->FECAEDetResponse;

        if ($det->Resultado === 'A') {
            // Factura aceptada
            $CAE = $det->CAE;
            $vtoCAE = $det->CAEFchVto;
        } else {
            // Factura rechazada: mostrar motivo
            $obs = $det->Observaciones->Obs ?? null;
            if ($obs) {
                $codigo = $obs->Código ?? '';
                $mensaje = $obs->Msg ?? '';
                die("❌ Factura rechazada por AFIP (Código: $codigo) - $mensaje");
            } else {
                die("❌ Factura rechazada: sin observaciones detalladas de AFIP");
            }
        }
    } else {
        die("❌ Factura rechazada: sin FECAEDetResponse");
    }

} catch (Exception $e) {
    die("❌ ERROR AFIP: " . $e->getMessage());
}

// ==========================
// MENSAJE PARA PDF
// ==========================
$mensajeCAE = "Factura autorizada. CAE: $CAE - Vto: $vtoCAE";

// ==========================
// GUARDAR EN BASE DE DATOS
// ==========================
try {
    $conn->begin_transaction();

    // ====== 1️⃣ CLIENTE MANUAL ======
    if ($id_cliente == 0 && !empty($_POST['cliente_json'])) {
        $cli = json_decode($_POST['cliente_json'], true);
        if ($cli) {
            $nombre    = $cli['nombre'] ?? '';
            $apellido  = $cli['apellido'] ?? '';
            $dni_cuit  = $cli['dni_cuit'] ?? '';
            $telefono  = $cli['telefono'] ?? '';
            $email     = $cli['email'] ?? '';
            $direccion = $cli['direccion'] ?? '';

            $stmtCli = $conn->prepare("INSERT INTO Cliente (nombre, apellido, dni_cuit, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)");
            $stmtCli->bind_param("ssssss", $nombre, $apellido, $dni_cuit, $telefono, $email, $direccion);
            $stmtCli->execute();
            $id_cliente = $stmtCli->insert_id;
            $stmtCli->close();

            $clienteNombre = $nombre . ' ' . $apellido;
        }
    }

    // ====== 2️⃣ INSERTAR LA VENTA ======
    $stmt = $conn->prepare("INSERT INTO Venta (id_cliente, id_usuario, total) VALUES (?, ?, ?)");
    $stmt->bind_param("iid", $id_cliente, $_SESSION['id_usuario'], $impTotal);
    $stmt->execute();
    $id_venta = $stmt->insert_id;
    $stmt->close();

    // ====== 3️⃣ DETALLE DE VENTA Y RESTA DE STOCK ======
    foreach ($productos as $p) {
        // Verificar stock disponible
        $stmtCheck = $conn->prepare("SELECT stock, nombre FROM Producto WHERE id_producto = ?");
        $stmtCheck->bind_param("i", $p['IdProducto']);
        $stmtCheck->execute();
        $resultCheck = $stmtCheck->get_result();
        $row = $resultCheck->fetch_assoc();
        $stmtCheck->close();

        if (!$row) {
            throw new Exception("Producto con ID {$p['IdProducto']} no encontrado.");
        }

        $stockDisponible = (int)$row['stock'];
        $nombreProducto  = $row['nombre'];

        if ($p['Cantidad'] > $stockDisponible) {
            throw new Exception("❌ Stock insuficiente para '$nombreProducto'. Disponible: $stockDisponible, solicitado: {$p['Cantidad']}.");
        }

        // Insertar detalle
        $stmtDet = $conn->prepare("INSERT INTO Detalle_Venta (id_venta, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
        $stmtDet->bind_param("iiid", $id_venta, $p['IdProducto'], $p['Cantidad'], $p['PrecioFinal']);
        $stmtDet->execute();
        $stmtDet->close();

        // Restar stock
        $stmtStock = $conn->prepare("UPDATE Producto SET stock = stock - ? WHERE id_producto = ?");
        $stmtStock->bind_param("ii", $p['Cantidad'], $p['IdProducto']);
        $stmtStock->execute();
        $stmtStock->close();
    }

    // ====== 4️⃣ INSERTAR COMPROBANTE ======
    $stmtComp = $conn->prepare("
        INSERT INTO Comprobante (
            id_venta, tipo_comprobante, punto_venta, numero_comprobante,
            fecha_emision, cae, fecha_vencimiento_cae, estado, monto_total
        ) VALUES (?, ?, ?, ?, NOW(), ?, ?, 'Autorizado', ?)
    ");
    $stmtComp->bind_param("isisssd", $id_venta, $tipo_factura, $pto_vta, $numeroFactura, $CAE, $vtoCAE, $impTotal);
    $stmtComp->execute();
    $stmtComp->close();

    $conn->commit();

// Confirmación visual
echo "<script>
    alert('✅ Venta registrada correctamente. El cliente fue guardado y el stock actualizado.');
    window.close(); // cierra la pestaña si se abrió en otra ventana
</script>";
exit;

} catch (Exception $e) {
    $conn->rollback();
    die("❌ ERROR al guardar en BD: " . $e->getMessage());
}

// ======================================
// FACTURA AFIP ESTILO COMÚN (guardar en A/B/M)
// ======================================

$pdf = new FPDF();
$pdf->AddPage();

// --- LOGO Y NOMBRE ---
$logoPath = __DIR__ . '/../../Panel/image_logo/logo1.jpeg';
if(file_exists($logoPath)){
    $pdf->Image($logoPath, 10,10,30);
}
$pdf->SetFont('Arial','B',26);
$pdf->SetXY(42,12);
$pdf->Cell(0,7, iconv('UTF-8','ISO-8859-1//TRANSLIT','L & M'),0,1);
$pdf->SetFont('Arial','',12);
$pdf->SetX(42);
$pdf->Cell(90,6, iconv('UTF-8','ISO-8859-1//TRANSLIT','MATERIALES ELÉCTRICOS'),0,1);

// --- NÚMERO DE FACTURA ARRIBA DERECHA ---
$pdf->SetFont('Arial','B',16);
$anchoPagina = $pdf->GetPageWidth();
$pdf->SetXY($anchoPagina - 80, 15);
$ptoVtaFormateado = str_pad($pto_vta, 4, '0', STR_PAD_LEFT);
$numeroFacturaForm = str_pad($numeroFactura, 8, '0', STR_PAD_LEFT);
$facturaCompleta = "$ptoVtaFormateado-$numeroFacturaForm";
$pdf->Cell(70,10, iconv('UTF-8','ISO-8859-1//TRANSLIT',"N° $facturaCompleta"),0,0,'R');

// --- CUADRO TIPO DE FACTURA AL LADO DEL NOMBRE, CENTRADO VERTICALMENTE ---
$anchoCaja = 30;
$altoCaja = 30;
$espacio = 58; // espacio entre el nombre y el cuadro

// Obtener la posición del nombre
$xNombre = 42;
$yNombre = 12; // misma Y que usaste para el texto
$altoNombre = 7; // altura del Cell del nombre
$anchoNombre = 90;

// Calcular posición del cuadro
$xCuadro = $xNombre + $espacio;

// Centrar verticalmente respecto al nombre
$yCuadro = $yNombre + ($altoNombre/2) - ($altoCaja/2);

$pdf->SetXY($xCuadro, $yCuadro);
$pdf->SetDrawColor(0,0,0); 
// $pdf->SetFillColor(230,230,230); 
$pdf->Rect($xCuadro, $yCuadro, $anchoCaja, $altoCaja, 'D');

// Mapeo tipo factura → código ARCA
$codigoArcaMap = ['A'=>'02','B'=>'04','M'=>'09'];
$codigoArca = $codigoArcaMap[$tipo_factura] ?? '00';

// --- Letra centrada ---
$pdf->SetFont('Arial','B',16);
$pdf->SetXY($xCuadro, $yCuadro + 5); // margen superior dentro del cuadro
$pdf->Cell($anchoCaja, 10, $tipo_factura, 0, 0, 'C');

// --- Número ARCA centrado debajo ---
$pdf->SetFont('Arial','B',12);
$pdf->SetXY($xCuadro, $yCuadro + 16); 
$pdf->Cell($anchoCaja, 10, $codigoArca, 0, 0, 'C');

// --- DATOS DEL LOCAL ---
$pdf->SetY(50);
$pdf->SetX(10);
$pdf->SetFont('Arial','',10);
$colWidth = 90;
$lineHeight = 6;
$pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1//TRANSLIT','Titular: LUCATTO CRISTIAN EDUARDO'),0,0);
$pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1//TRANSLIT','CUIT: 20-39844360-9'),0,1);
$pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1//TRANSLIT','Dirección: Ruta 20 / Km 12.521'),0,0);
$pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1//TRANSLIT','Condición de IVA: RESPONSABLE INSCRIPTO'),0,1);
$pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1//TRANSLIT','Localidad: GUAYMALLEN'),0,0);
$pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1//TRANSLIT','Tel: 2616268610'),0,1);
$pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1//TRANSLIT','Provincia: MENDOZA'),0,0);
$pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1//TRANSLIT','Inicio de Actividades: 25/08/2025'),0,1);
$pdf->Ln(10);

// ==========================
// CARGAR DATOS DEL CLIENTE
// ==========================
$cliente = [];
if ($id_cliente > 0) {
    $stmt = $conn->prepare("SELECT nombre, apellido, dni_cuit, telefono, email, direccion FROM Cliente WHERE id_cliente = ?");
    $stmt->bind_param("i", $id_cliente);
    $stmt->execute();
    $res = $stmt->get_result();
    $cliente = $res->fetch_assoc() ?: [];
    $stmt->close();
} else if (!empty($_POST['cliente_json'])) {
    $cli = json_decode($_POST['cliente_json'], true);
    if ($cli) $cliente = $cli;
}

// Datos individuales
$clienteNombre = ($cliente['nombre'] ?? '') . ' ' . ($cliente['apellido'] ?? '');
$dni = $cliente['dni_cuit'] ?? '';
$telefono = $cliente['telefono'] ?? '';
$email = $cliente['email'] ?? '';
$direccion = $cliente['direccion'] ?? '';

// ==========================
// BLOQUE PDF DATOS DEL CLIENTE
// ==========================
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6, iconv('UTF-8', 'ISO-8859-1','Datos Cliente:'),0,1);
$pdf->SetFont('Arial','',11);

$colWidth = 0; // ancho hasta margen derecho
$lineHeight = 6;
// $pdf->SetFillColor(255,255,255);

if(!empty($cliente)){
    $pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1',"$clienteNombre - DNI/CUIT: $dni"),1,1,'L');
    $pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1',"Tel: $telefono - Email: $email"),1,1,'L',);
    $pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1',"Dirección: $direccion"),1,1,'L',);
} else {
    $pdf->Cell($colWidth,$lineHeight, iconv('UTF-8','ISO-8859-1','Cliente no especificado'),1,1,'L',);
}

$pdf->Ln(5);


// --- TABLA DE PRODUCTOS PARA FACTURA B ---
$pdf->SetFont('Arial','B',12);
$pdf->Cell(80,7, iconv('UTF-8','ISO-8859-1//TRANSLIT','Producto'),1,0,'C');
$pdf->Cell(30,7, iconv('UTF-8','ISO-8859-1//TRANSLIT','Cantidad'),1,0,'C');
$pdf->Cell(40,7, iconv('UTF-8','ISO-8859-1//TRANSLIT','Precio Unitario'),1,0,'C');
$pdf->Cell(40,7, iconv('UTF-8','ISO-8859-1//TRANSLIT','Precio Total'),1,1,'C');
$pdf->SetFont('Arial','',11);

$totalFinal = 0;
foreach($productos as $p){
    $nombreProd = $p['Nombre'] ?? '';
    $cantidad = $p['Cantidad'] ?? 1;
    $precioBase = ($p['PrecioFinal'] ?? 0) / $cantidad; // Precio sin IVA

    $precioUnit = $precioBase * 1.21; // Precio con IVA 21%
    $totalLinea = $cantidad * $precioUnit; // Total con IVA
    $totalFinal += $totalLinea;

    // Posición actual
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Nombre del producto con MultiCell para que se ajuste en varias líneas
    $pdf->MultiCell(80,6, iconv('UTF-8','ISO-8859-1//TRANSLIT',$nombreProd),1,'L');

    // Altura usada por MultiCell
    $altura = $pdf->GetY() - $y;

    // Volver a la derecha de la celda de nombre
    $pdf->SetXY($x+80,$y);

    // Otras columnas con la misma altura
    $pdf->Cell(30,$altura,$cantidad,1,0,'C');
    $pdf->Cell(40,$altura,number_format($precioUnit,2),1,0,'R');
    $pdf->Cell(40,$altura,number_format($totalLinea,2),1,1,'R');
}

// --- TOTAL FINAL ---
$pdf->Ln(5);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(150,12, iconv('UTF-8','ISO-8859-1//TRANSLIT','TOTAL'),1,0,'R');
$pdf->Cell(40,12,number_format($totalFinal,2),1,1,'R');


// ==========================
// GENERAR QR AFIP Y LEYENDA OBLIGATORIA
// ==========================
require __DIR__ . '/../../Libs/phpqrcode/qrlib.php';

// Datos para el JSON del QR
$qrData = [
    "ver" => 1,
    "fecha" => date("Y-m-d"),
    "cuit" => 20398443609,   // CUIT emisor
    "ptoVta" => $pto_vta,
    "tipoCmp" => $CbteTipo,  // Tipo de comprobante (1=A, 6=B, 11=C, 51=M)
    "nroCmp" => $numeroFactura,
    "importe" => round($impTotal, 2),
    "moneda" => "PES",
    "ctz" => 1,
    "tipoDocRec" => 99,      // 99 = Consumidor Final
    "nroDocRec" => 0,
    "tipoCodAut" => "E",
    "codAut" => $CAE
];

// Convertir JSON a Base64 URL-safe
$jsonQr   = json_encode($qrData, JSON_UNESCAPED_SLASHES);
$base64Qr = rtrim(strtr(base64_encode($jsonQr), '+/', '-_'), '=');
$urlQr    = "https://www.afip.gob.ar/fe/qr/?p=$base64Qr";

// Generar QR temporal
$tempDir = __DIR__ . "/facturas"; 
if (!is_dir($tempDir)) mkdir($tempDir, 0777, true);
$qrTemp = $tempDir . "/qr_temp.png";
QRcode::png($urlQr, $qrTemp, QR_ECLEVEL_L, 4);

// Insertar QR en PDF (abajo a la izquierda)
$yPos = $pdf->GetPageHeight() - 60; // 60mm desde abajo
$pdf->Image($qrTemp, 10, $yPos, 40, 40);

// Leyenda obligatoria AFIP
$pdf->SetFont('Arial','',10);
$leyenda = "Comprobante autorizado electrónicamente por AFIP. Consulte el comprobante en www.afip.gob.ar";
$pdf->SetXY(55, $yPos + 5);
$pdf->MultiCell(0, 5, iconv('UTF-8','ISO-8859-1//TRANSLIT',$leyenda), 0, 'L');

// CAE y vencimiento debajo del QR
$pdf->SetXY(55, $yPos + 20);
$pdf->Cell(0,6, iconv('UTF-8','ISO-8859-1//TRANSLIT',"CAE: $CAE - Vto CAE: $vtoCAE"),0,1,'L');

// Borrar QR temporal después de usarlo
if (file_exists($qrTemp)) {
    unlink($qrTemp);
}
// --- NOMBRE DEL PDF CONSISTENTE ---
$nombrePdf = "Factura_{$tipo_factura}_{$numeroFactura}_" . date("Ymd_His") . ".pdf";

// Carpeta según tipo de factura
$carpeta = __DIR__ . "/facturas/$tipo_factura";
if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

// Ruta completa en el servidor
$archivo = $carpeta . "/" . $nombrePdf;

// --- GUARDAR PDF EN EL SERVIDOR ---
$pdf->Output('F', $archivo); // guarda en servidor

// --- LIMPIAR CUALQUIER SALIDA PREVIA ---
if (ob_get_length()) ob_end_clean();

// --- MOSTRAR PDF EN NAVEGADOR CON NOMBRE CORRECTO ---
$pdf->Output('I', $nombrePdf); // 'I' = inline, segundo parámetro = nombre del archivo
exit;

