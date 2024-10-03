<?php
use Greenter\Ws\Services\SunatEndpoints;

$see = new \Greenter\See();
$see->setService(SunatEndpoints::FE_PRODUCCION); // Cambiar la url para cuando sea Percepción/Retención
$see->setCertificate(file_get_contents(__DIR__.'/valid-cer.pem'));
$see->setClaveSOL('20000000001D', 'NOMBLOI', 'psdlbmrt');