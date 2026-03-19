<?php
// ======================================
// GENERAR FACTURA COMÚN - PDF + INSERTAR EN DB
// ======================================

error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../../Libs/fpdf/fpdf.php';
require __DIR__ . '/../../Panel/includes/conexion.php'; // Conexión a la BBDD

// ===========================
// RECIBIR DATOS
// ===========================
$productosJson = $_POST['productos'] ?? '[]';
$productos = json_decode($productosJson, true);
if(!is_array($productos)) $productos = []; // Validación JSON

$clienteData = $_POST['cliente_json'] ?? '';
$cliente = json_decode($clienteData, true);
if(!is_array($cliente)) $cliente = []; // Validación JSON

$idCliente = $_POST['id_cliente'] ?? null;

// ===========================
// CLIENTE EXISTENTE O MANUAL
// ===========================
if($idCliente){
    $stmt = $conn->prepare("SELECT * FROM Cliente WHERE id_cliente = ?");
    $stmt->bind_param("i", $idCliente);
    if(!$stmt->execute()) throw new Exception("Error al obtener cliente: ".$stmt->error);
    $cliente = $stmt->get_result()->fetch_assoc();
    $stmt->close();
} else if(!empty($cliente)){
    // Validar campos obligatorios
    $nombre = $cliente['nombre'] ?? null;
    $apellido = $cliente['apellido'] ?? null;
    if(!$nombre || !$apellido) die("❌ Cliente debe tener nombre y apellido");

    $dni = $cliente['dni'] ?? '';
    $telefono = $cliente['telefono'] ?? '';
    $email = $cliente['email'] ?? '';
    $direccion = $cliente['direccion'] ?? '';

    $stmt = $conn->prepare("INSERT INTO Cliente (nombre, apellido, dni, telefono, email, direccion) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $nombre, $apellido, $dni, $telefono, $email, $direccion);
    if(!$stmt->execute()) throw new Exception("Error al insertar cliente: ".$stmt->error);
    $idCliente = $stmt->insert_id;
    $stmt->close();
}

$idUsuario = $_POST['id_usuario'] ?? 1; // Usuario que genera la venta
$puntoVenta = 1; // Punto de venta


// ===========================
// VALIDAR PRODUCTOS
// ===========================
if(empty($productos)){
    echo "<script>alert('⚠️ No se han enviado productos para la venta.'); window.history.back();</script>";
    exit;
}

// DEBUG: Mostrar productos recibidos
error_log('PRODUCTOS RECIBIDOS: ' . print_r($productos, true));
foreach ($productos as $p) {
    error_log('PRODUCTO JSON: ' . json_encode($p));
}

// ===========================
// CALCULAR TOTAL
// ===========================
$totalGeneral = 0;
foreach ($productos as $p) {
    $precioFila = $p['PrecioFinal'] ?? 0;
    $totalGeneral += $precioFila;
}

// ===========================
// INICIAR TRANSACCIÓN
// ===========================
$conn->begin_transaction();
try {
    // ===========================
    // INSERTAR EN TABLA VENTA
    // ===========================
    $stmtVenta = $conn->prepare("INSERT INTO Venta (id_usuario, id_cliente, fecha, total) VALUES (?, ?, NOW(), ?)");
    $stmtVenta->bind_param("iid", $idUsuario, $idCliente, $totalGeneral);
    if(!$stmtVenta->execute()) throw new Exception("Error al insertar venta: ".$stmtVenta->error);
    $idVenta = $stmtVenta->insert_id;
    $stmtVenta->close();

    // ===========================
    // DETALLE DE VENTA Y ACTUALIZAR STOCK
    // ===========================
    foreach ($productos as $p) {
        $idProducto = $p['IdProducto'] ?? 0;
        $cantidad = $p['Cantidad'] ?? 1;
        $precioFinal = $p['PrecioFinal'] ?? 0;

        // Si el producto es manual, asignar un ID único y crear registro en Producto
        if($idProducto == 0){
            // Obtener el máximo id_producto actual
            $result = $conn->query("SELECT MAX(id_producto) AS max_id FROM Producto");
            $row = $result->fetch_assoc();
            $nuevoIdProducto = ($row['max_id'] ?? 0) + 1;
            $idProducto = $nuevoIdProducto;
            // Insertar producto manual en la tabla Producto
            $nombreProd = $p['Nombre'] ?? 'Producto Manual';
            $stmtProd = $conn->prepare("INSERT INTO Producto (id_producto, nombre, precio_venta, stock) VALUES (?, ?, ?, ?)");
            $stmtProd->bind_param("isdi", $idProducto, $nombreProd, $precioFinal, $cantidad);
            $stmtProd->execute();
            $stmtProd->close();
        }

        // Validar stock solo si el producto existe en la BD
        if($idProducto != 0){
            $stmtStockCheck = $conn->prepare("SELECT stock FROM Producto WHERE id_producto = ?");
            $stmtStockCheck->bind_param("i", $idProducto);
            if(!$stmtStockCheck->execute()) throw new Exception("Error al consultar stock: ".$stmtStockCheck->error);
            $stockActual = $stmtStockCheck->get_result()->fetch_assoc()['stock'] ?? 0;
            $stmtStockCheck->close();

            if($cantidad > $stockActual){
                throw new Exception("❌ Stock insuficiente para el producto ID $idProducto (Disponible: $stockActual, Pedido: $cantidad)");
            }
        }

        // Insertar detalle
        $stmtDet = $conn->prepare("INSERT INTO Detalle_Venta (id_venta, id_producto, cantidad, precio_unitario) VALUES (?, ?, ?, ?)");
        $stmtDet->bind_param("iiid", $idVenta, $idProducto, $cantidad, $precioFinal);
        if(!$stmtDet->execute()) throw new Exception("Error al insertar detalle: ".$stmtDet->error);
        $stmtDet->close();

        // Verificar stock antes de actualizar
        $stmtCheck = $conn->prepare("SELECT stock FROM Producto WHERE id_producto = ?");
        $stmtCheck->bind_param("i", $idProducto);
        $stmtCheck->execute();
        $stockAntes = $stmtCheck->get_result()->fetch_assoc()['stock'] ?? 0;
        $stmtCheck->close();
        error_log("STOCK ANTES: Producto $idProducto = $stockAntes");

        // Actualizar stock solo si el producto existe en la BD
        if($idProducto != 0){
            $stmtStock = $conn->prepare("UPDATE Producto SET stock = stock - ? WHERE id_producto = ?");
            $stmtStock->bind_param("ii", $cantidad, $idProducto);
            if(!$stmtStock->execute()) throw new Exception("Error al actualizar stock: ".$stmtStock->error);
            $stmtStock->close();
        }

        // Verificar stock después de actualizar
        $stmtCheck = $conn->prepare("SELECT stock FROM Producto WHERE id_producto = ?");
        $stmtCheck->bind_param("i", $idProducto);
        $stmtCheck->execute();
        $stockDespues = $stmtCheck->get_result()->fetch_assoc()['stock'] ?? 0;
        $stmtCheck->close();
        error_log("STOCK DESPUES: Producto $idProducto = $stockDespues");
    }

    // ===========================
    // OBTENER EL NUMERO DE FACTURA COMÚN
    // ===========================
    $query = "SELECT MAX(numero_comprobante) AS ultimo_numero 
              FROM Comprobante 
              WHERE tipo_comprobante = 'Factura Común'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $ultimoNumero = (int)($row['ultimo_numero'] ?? 0);
    $nuevaFactura = $ultimoNumero + 1;
    $numeroFactura = str_pad($puntoVenta, 4, '0', STR_PAD_LEFT) . '-' . str_pad($nuevaFactura, 8, '0', STR_PAD_LEFT);

    // ===========================
    // INSERTAR EN TABLA COMPROBANTE
    // ===========================
    $stmt = $conn->prepare("INSERT INTO Comprobante 
        (id_venta, tipo_comprobante, punto_venta, numero_comprobante, fecha_emision, estado, monto_total) 
        VALUES (?, 'Factura Común', ?, ?, NOW(), 'Autorizado', ?)");
    $stmt->bind_param("iisi", $idVenta, $puntoVenta, $nuevaFactura, $totalGeneral);
    if(!$stmt->execute()) throw new Exception("Error al insertar comprobante: ".$stmt->error);
    $stmt->close();


// ===========================
// CREAR PDF
// ===========================
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);

// LOGO + NOMBRE
$pdf->Image(__DIR__ . '/../../Panel/image_logo/logo1.jpeg', 10, 10, 30);
$posX = 42;
$pdf->SetFont('Arial','B',26);
$pdf->SetXY($posX, 12);
$pdf->Cell(0, 7, iconv('UTF-8', 'ISO-8859-1','L & M'), 0, 1);
$pdf->SetFont('Arial','',12);
$pdf->SetX($posX);
$pdf->Cell(90, 6, iconv('UTF-8', 'ISO-8859-1','MATERIALES ELÉCTRICOS'), 0, 1);

// Número de factura arriba a la derecha
$pdf->SetFont('Arial','B',16);
$anchoPagina = $pdf->GetPageWidth();
$pdf->SetXY($anchoPagina - 80, 15);
$pdf->Cell(70, 10, iconv('UTF-8', 'ISO-8859-1',"Factura N° $numeroFactura"), 0, 0, 'R');

// DATOS DEL LOCAL
$pdf->SetY(50);
$pdf->SetX(10);
$pdf->SetFont('Arial','',10);
$colWidth = 90;
$lineHeight = 6;

$pdf->Cell($colWidth, $lineHeight, iconv('UTF-8', 'ISO-8859-1','Titular: LUCATTO CRISTIAN EDUARDO'), 0, 0);
$pdf->Cell($colWidth, $lineHeight, iconv('UTF-8', 'ISO-8859-1','CUIT: 20-39844360-9'), 0, 1);
$pdf->Cell($colWidth, $lineHeight, iconv('UTF-8', 'ISO-8859-1','Dirección: Ruta 20 / Km 12.521'), 0, 0);
$pdf->Cell($colWidth, $lineHeight, iconv('UTF-8', 'ISO-8859-1','Condición de IVA: RESPONSABLE INSCRIPTO'), 0, 1);
$pdf->Cell($colWidth, $lineHeight, iconv('UTF-8', 'ISO-8859-1','Localidad: GUAYMALLEN'), 0, 0);
$pdf->Cell($colWidth, $lineHeight, iconv('UTF-8', 'ISO-8859-1','Tel: 2616268610'), 0, 1);
$pdf->Cell($colWidth, $lineHeight, iconv('UTF-8', 'ISO-8859-1','Provincia: MENDOZA'), 0, 0);
$pdf->Cell($colWidth, $lineHeight, iconv('UTF-8', 'ISO-8859-1','Inicio de Actividades: 25/08/2025'), 0, 1);
$pdf->Ln(10);

// DATOS DEL CLIENTE
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6, iconv('UTF-8', 'ISO-8859-1','Datos Cliente:'), 0,1);
$pdf->SetFont('Arial','',11);
if(!empty($cliente)){
    $nombre = $cliente['nombre'] ?? '';
    $apellido = $cliente['apellido'] ?? '';
    $dni = $cliente['dni'] ?? '';
    $telefono = $cliente['telefono'] ?? '';
    $email = $cliente['email'] ?? '';
    $direccion = $cliente['direccion'] ?? '';

    $pdf->Cell(0,6, iconv('UTF-8', 'ISO-8859-1', "$nombre $apellido - DNI/CUIT: $dni"),0,1);
    $pdf->Cell(0,6, iconv('UTF-8', 'ISO-8859-1', "Tel: $telefono - Email: $email"),0,1);
    $pdf->Cell(0,6, iconv('UTF-8', 'ISO-8859-1', "Dirección: $direccion"),0,1);
} else {
    $pdf->Cell(0,6, iconv('UTF-8', 'ISO-8859-1','Cliente no especificado'),0,1);
}
$pdf->Ln(5);

// TABLA DE PRODUCTOS
$pdf->SetFont('Arial','B',12);
$pdf->Cell(80,7, 'Nombre',1,0,'C');
$pdf->Cell(30,7, 'Cantidad',1,0,'C');
$pdf->Cell(40,7, 'Precio Unitario',1,0,'C');
$pdf->Cell(40,7, 'Precio Total',1,1,'C');

$pdf->SetFont('Arial','',11);
foreach($productos as $p){
    $nombreProd  = $p['Nombre'] ?? '';
    $cantidad    = $p['Cantidad'] ?? 1;
    $precioFinal = $p['PrecioFinal'] ?? 0;               // total fila ya viene calculado
    $precioUnit  = $cantidad > 0 ? $precioFinal / $cantidad : 0; // unitario

    // Posición actual
    $x = $pdf->GetX();
    $y = $pdf->GetY();

    // Nombre con MultiCell
    $pdf->MultiCell(80,6,iconv('UTF-8', 'ISO-8859-1',$nombreProd),1,'L');

    // Altura usada
    $altura = $pdf->GetY() - $y;

    // Volver a la derecha de la celda de "Nombre"
    $pdf->SetXY($x+80,$y);

    // Otras columnas con la misma altura
    $pdf->Cell(30,$altura,$cantidad,1,0,'C');
    $pdf->Cell(40,$altura,number_format($precioUnit,2),1,0,'R');
    $pdf->Cell(40,$altura,number_format($precioFinal,2),1,1,'R');
}


// TOTAL FINAL
$pdf->Ln(5);
$pdf->SetFont('Arial','B',14);
$pdf->Cell(150,12,iconv('UTF-8', 'ISO-8859-1','TOTAL'),1,0,'R');
$pdf->Cell(40,12,iconv('UTF-8', 'ISO-8859-1',number_format($totalGeneral,2)),1,1,'R');

// ===========================
// COMMIT DE TRANSACCIÓN
// ===========================
$conn->commit();

// ALERTA DE ÉXITO
    // echo "<script>alert('✅ Venta registrada correctamente. Cliente guardado y stock actualizado.');</script>";
    // No enviar ninguna salida antes de mostrar el PDF
    
} catch(Exception $e){
    $conn->rollback();
    die("❌ ERROR al guardar en BD: " . $e->getMessage());
}

// ===========================
// GUARDAR PDF EN SERVIDOR
// ===========================
$carpeta = __DIR__ . "/facturas/comun/";
if (!is_dir($carpeta)) mkdir($carpeta, 0777, true);

$nombreArchivo = $carpeta . "factura_$numeroFactura.pdf";
$pdf->Output('F', $nombreArchivo);

// ===========================
// MOSTRAR PDF EN NAVEGADOR
// ===========================
$pdf->Output('I', 'venta_comun.pdf');
exit;
?>
