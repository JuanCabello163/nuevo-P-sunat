<?php

use Greenter\Model\Client\Client;
use Greenter\Model\Company\Company;
use Greenter\Model\Company\Address;
use Greenter\Model\Sale\FormaPagos\FormaPagoContado;
use Greenter\Model\Sale\Invoice;
use Greenter\Model\Sale\SaleDetail;
use Greenter\Model\Sale\Legend;
use Greenter\See;

require __DIR__.'/vendor/autoload.php';

$see = require __DIR__.'/config.php';

// Cliente
$client = (new Client())
    ->setTipoDoc('6')
    ->setNumDoc('20000000001')
    ->setRznSocial('EMPRESA X');

// Emisor
$address = (new Address())
    ->setUbigueo('150101')
    ->setDepartamento('LIMA')
    ->setProvincia('LIMA')
    ->setDistrito('LIMA')
    ->setUrbanizacion('-')
    ->setDireccion('Av. Villa Nueva 221')
    ->setCodLocal('0000'); // Codigo de establecimiento asignado por SUNAT, 0000 por defecto.

$company = (new Company())
    ->setRuc('20123456789')
    ->setRazonSocial('GREEN SAC')
    ->setNombreComercial('GREEN')
    ->setAddress($address);

// Venta
// Recibir los datos del formulario
$fechaEmision = $_POST['fechaEmision'] ?? '';
$mtoOperGravadas = $_POST['mtoOperGravadas'] ?? 0;
$codProducto = $_POST['codProducto'] ?? '';
$cantidad = $_POST['cantidad'] ?? 0;
$mtoValorUnitarioDetalle = $_POST['mtoValorUnitarioDetalle'] ?? 0;
$descripcion = $_POST['descripcion'] ?? '';

// Mostrar los valores de las variables
var_dump($cantidad);
var_dump($mtoValorUnitarioDetalle);

// Calcular el valor de venta
$valorVenta = $cantidad * $mtoValorUnitarioDetalle;

// Calcular el monto importe venta
$montoImpVenta = $valorVenta * 1.18;

// Crear la factura
$invoice = (new Invoice())
    ->setUblVersion('2.1')
    ->setTipoOperacion('0101') // Venta - Catalog. 51
    ->setTipoDoc('01') // Factura - Catalog. 01 
    ->setSerie('F001')
    ->setCorrelativo('1')
    ->setFechaEmision(new DateTime($fechaEmision)) // Zona horaria: Lima
    ->setFormaPago(new FormaPagoContado()) // FormaPago: Contado
    ->setTipoMoneda('PEN') // Sol - Catalog. 02
    ->setCompany($company)
    ->setClient($client)
    ->setMtoOperGravadas($mtoOperGravadas)
    ->setValorVenta($valorVenta)
    ->setMtoImpVenta($montoImpVenta)
    ;

    $item = (new SaleDetail())
    ->setCodProducto($codProducto)
    ->setUnidad('NIU') // Unidad - Catalog. 03
    ->setCantidad($cantidad)
    ->setMtoValorUnitario($mtoValorUnitarioDetalle) // Cambia el nombre de la variable
    ->setDescripcion($descripcion)
    ->setMtoBaseIgv($mtoValorUnitarioDetalle * $cantidad)
    ->setPorcentajeIgv(18.00) // 18%
    ->setTipAfeIgv('10') // Gravado Op. Onerosa - Catalog. 07
    ->setMtoValorVenta($valorVenta)
    ;

$legend = (new Legend())
    ->setCode('1000') // Monto en letras - Catalog. 52
    ->setValue('SON DOSCIENTOS TREINTA Y SEIS CON 00/100 SOLES');

$invoice->setDetails([$item])
        ->setLegends([$legend]);

$result = $see->send($invoice);

// Calcula el valor de venta y monto importe venta
$valorVenta = $mtoOperGravadas;
$montoImpVenta = $valorVenta + 18;

// Actualiza el valor de los campos
echo "Factura creada con éxito:\n";
echo "Fecha de emisión: " . $fechaEmision . "\n";
echo "Monto operaciones gravadas: " . $mtoOperGravadas . "\n";
echo "Monto IGV: 18\n";
echo "Valor venta: " . $valorVenta . "\n";
echo "Monto importe venta: " . $montoImpVenta . "\n";
echo "Código producto: " . $codProducto . "\n";
echo "Cantidad: " . $cantidad . "\n";
echo "Monto valor unitario: " . $mtoValorUnitarioDetalle . "\n";
echo "Descripción: " . $descripcion . "\n";

// Muestra el resultado en el HTML
?>
<html>
  <body>
    <h2>Factura</h2>
    <iframe src="factura.php" frameborder="0" width="100%" height="500"></iframe>
    <p>Fecha de emisión: <?php echo $fechaEmision; ?></p>
    <p>Monto operaciones gravadas: <?php echo $mtoOperGravadas; ?></p>
    <p>Monto IGV: 18</p>
    <p>Valor venta: <?php echo $valorVenta; ?></p>
    <p>Monto importe venta: <?php echo $montoImpVenta; ?></p>
    <p>Código producto: <?php echo $codProducto; ?></p>
    <p>Cantidad: <?php echo $cantidad; ?></p>
    <p>Monto valor unitario: <?php echo $mtoValorUnitarioDetalle; ?></p>
    <p>Descripción: <?php echo $descripcion; ?></p>
  </body>
</html>


<?php
// Guardar XML firmado digitalmente.
file_put_contents($invoice->getName().'.xml',
                    $see->getFactory()->getLastXml());
        
// Verificamos que la conexión con SUNAT fue exitosa.
if (!$result->isSuccess()) {
    // Mostrar error al conectarse a SUNAT.
    echo 'Codigo Error: '.$result->getError()->getCode();
    echo 'Mensaje Error: '.$result->getError()->getMessage();
    exit();
}
        
// Guardamos el CDR
file_put_contents('R-'.$invoice->getName().'.zip', $result->getCdrZip());

        
/*
* file: factura.php 
*/

$cdr = $result->getCdrResponse();

$code = (int)$cdr->getCode();

if ($code === 0) {
    echo 'ESTADO: ACEPTADA'.PHP_EOL;
    if (count($cdr->getNotes()) > 0) {
        echo 'OBSERVACIONES:'.PHP_EOL;
        // Corregir estas observaciones en siguientes emisiones.
        var_dump($cdr->getNotes());
    }  
} else if ($code >= 2000 && $code <= 3999) {
    echo 'ESTADO: RECHAZADA'.PHP_EOL;
} else {
    /* Esto no debería darse, pero si ocurre, es un CDR inválido que debería tratarse como un error-excepción. */
    /*code: 0100 a 1999 */
    echo 'Excepción';
}

echo $cdr->getDescription().PHP_EOL;