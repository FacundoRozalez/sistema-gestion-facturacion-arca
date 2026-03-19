<?php
// ======================================
// GENERAR PRESUPUESTO - PDF
// ======================================

require __DIR__ . '/../../Libs/fpdf/fpdf.php';

// ===========================
// RECIBIR DATOS
// ===========================
$productosJson = $_POST['productos'] ?? '[]';
$productos = json_decode($productosJson, true);

$clienteData = $_POST['cliente_json'] ?? '';
$cliente = json_decode($clienteData, true);
if(!$cliente) $cliente = []; // Cliente vacío si no viene nada

// ===========================
// CALCULAR TOTAL
// ===========================
$totalGeneral = 0;
foreach ($productos as $p) {
    $precioFinal = $p['PrecioFinal'] ?? 0;
    $totalGeneral += $precioFinal;
}

// ===========================
// CREAR PDF
// ===========================
$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',14);

// ===========================
// LOGO + NOMBRE
// ===========================
$pdf->Image(__DIR__ . '/../../Panel/image_logo/logo1.jpeg', 10, 10, 30); // Logo

// ===============================
// SOLO PRESUPUESTO
// ===============================
$pdf->SetFont('Arial','B',16);
$pdf->SetXY(150, 10); // posición a la derecha
$pdf->Cell(40, 15, 'PRESUPUESTO', 0, 1, 'L');

// ===========================
// Nombre del local
// ===========================
$posX = 42;
$pdf->SetFont('Arial','B',26);
$pdf->SetXY($posX, 12);
$pdf->Cell(0, 7, utf8_decode('L & M'), 0, 1);

$pdf->SetFont('Arial','',12);
$pdf->SetX($posX);
$pdf->Cell(90, 6, utf8_decode('MATERIALES ELÉCTRICOS'), 0, 1);

// ===========================
// DATOS DEL LOCAL (2 columnas)
// ===========================
$pdf->Ln(20);
$pdf->SetFont('Arial','',10);
$colWidth = 90;
$lineHeight = 6;

$pdf->SetX(10);
$pdf->Cell($colWidth, $lineHeight, utf8_decode('Titular: LUCATTO CRISTIAN EDUARDO'), 0, 0);
$pdf->Cell($colWidth, $lineHeight, utf8_decode('CUIT: 20-39844360-9'), 0, 1);

$pdf->SetX(10);
$pdf->Cell($colWidth, $lineHeight, utf8_decode('Dirección: Ruta 20 / Km 12.521'), 0, 0);
$pdf->Cell($colWidth, $lineHeight, utf8_decode('Condición de IVA: RESPONSABLE INSCRIPTO'), 0, 1);

$pdf->SetX(10);
$pdf->Cell($colWidth, $lineHeight, utf8_decode('Localidad: GUAYMALLEN'), 0, 0);
$pdf->Cell($colWidth, $lineHeight, utf8_decode('Tel: 2616268610'), 0, 1);

$pdf->SetX(10);
$pdf->Cell($colWidth, $lineHeight, utf8_decode('Provincia: MENDOZA'), 0, 0);
$pdf->Cell($colWidth, $lineHeight, utf8_decode('Inicio de Actividades: 25/08/2025'), 0, 1);

$pdf->Ln(10);

// ===========================
// DATOS DEL CLIENTE
// ===========================
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,utf8_decode('Datos Cliente:'),0,1);
$pdf->SetFont('Arial','',11);

if(!empty($cliente)){
    $nombre = $cliente['nombre'] ?? '';
    $apellido = $cliente['apellido'] ?? '';
    $dni = $cliente['dni'] ?? '';
    $telefono = $cliente['telefono'] ?? '';
    $email = $cliente['email'] ?? '';
    $direccion = $cliente['direccion'] ?? '';

    $pdf->Cell(0,6,utf8_decode("$nombre $apellido - DNI/CUIT: $dni"),0,1);
    $pdf->Cell(0,6,utf8_decode("Tel: $telefono - Email: $email"),0,1);
    $pdf->Cell(0,6,utf8_decode("Dirección: $direccion"),0,1);
} else {
    $pdf->Cell(0,6,'Cliente no especificado',0,1);
}

$pdf->Ln(5);

// ===========================
// TABLA DE PRODUCTOS CON CONTROL DE PÁGINA
// ===========================
$pdf->SetFont('Arial','B',12);

function dibujarCabeceraTabla($pdf){
    $pdf->Cell(80,7, 'Nombre',1,0,'C');
    $pdf->Cell(30,7, 'Cantidad',1,0,'C');
    $pdf->Cell(40,7, 'Precio Unitario',1,0,'C');
    $pdf->Cell(40,7, 'Precio Total',1,1,'C');
}

// Dibujar la primera cabecera
dibujarCabeceraTabla($pdf);
$pdf->SetFont('Arial','',11);

// Método para calcular NbLines
if (!method_exists($pdf,'NbLines')) {
    $pdf->NbLines = function($w,$txt){
        $cw = $this->CurrentFont['cw'];
        if($w==0) $w=$this->w-$this->rMargin-$this->x;
        $wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
        $s=str_replace("\r",'',$txt);
        $nb=strlen($s);
        if($nb>0 and $s[$nb-1]=="\n") $nb--;
        $sep=-1;
        $i=0;
        $j=0;
        $l=0;
        $nl=1;
        while($i<$nb){
            $c=$s[$i];
            if($c=="\n"){
                $i++;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
                continue;
            }
            if($c==' ') $sep=$i;
            $l+=$cw[$c];
            if($l>$wmax){
                if($sep==-1){
                    if($i==$j) $i++;
                } else $i=$sep+1;
                $sep=-1;
                $j=$i;
                $l=0;
                $nl++;
            } else $i++;
        }
        return $nl;
    };
}

// ===========================
// RECORRER PRODUCTOS
// ===========================
foreach($productos as $p){
    $nombreProd  = $p['Nombre'] ?? '';
    $cantidad    = $p['Cantidad'] ?? 1;
    $precioFinal = $p['PrecioFinal'] ?? 0;
    $precioUnit  = $cantidad > 0 ? $precioFinal / $cantidad : 0;

    $x = $pdf->GetX();
    $y = $pdf->GetY();

    $texto = iconv('UTF-8','ISO-8859-1',$nombreProd);
    $lineas = ceil(strlen($texto)/40);
    $altura = 6 * $lineas;

// ESPACIO RESERVADO PARA EL TOTAL
$altoTotal = 12; // alto de la celda TOTAL
$espacioTotal = $altura + $altoTotal + 5; // altura del producto + total + margen

// SALTO DE PÁGINA SI NO CABE EL PRODUCTO + TOTAL
if($y + $espacioTotal > $pdf->GetPageHeight() - 20){
    $pdf->AddPage();
    dibujarCabeceraTabla($pdf);
    $y = $pdf->GetY();
}




    // Dibujar Nombre
    $pdf->SetXY($x, $y);
    $pdf->MultiCell(80,6,$texto,1,'L');

    // Dibujar las demás columnas al mismo nivel
    $pdf->SetXY($x+80, $y);
    $pdf->Cell(30,$altura,$cantidad,1,0,'C');
    $pdf->Cell(40,$altura,number_format($precioUnit,2),1,0,'R');
    $pdf->Cell(40,$altura,number_format($precioFinal,2),1,1,'R');

    // Ajustar puntero Y
    $y = $y + $altura; // calcular nueva posición
    $pdf->SetY($y);

}

// ===========================
// TOTAL FINAL
// ===========================
$pdf->Ln(5);

$altoTotal = 12; // alto de la celda TOTAL

// Si no hay espacio suficiente para TOTAL, hacer salto de página
if($pdf->GetY() + $altoTotal + 5 > $pdf->GetPageHeight() - 20){
    $pdf->AddPage();
}


$pdf->SetFont('Arial','B',14);
$pdf->Cell(150,12,iconv('UTF-8','ISO-8859-1','TOTAL'),1,0,'R');
$pdf->Cell(40,12,number_format($totalGeneral,2),1,1,'R');

// ===========================
// SALIDA PDF
// ===========================
$pdf->Output('I','presupuesto.pdf'); 
