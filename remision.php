<?php
$see = new \Greenter\Api([
  'auth' => 'https://api-seguridad.sunat.gob.pe/v1',
  'cpe' => 'https://api-cpe.sunat.gob.pe/v1',
]);
$see->setCertificate(file_get_contents(__DIR__.'/valid-cer.pem'));
$see->setClaveSOL('20000000001D', 'NOMBLOI', 'psdlbmrt');
$see->setApiCredentials('aad1-85e5b0ae-255c-4891-a595-0b98c65c9854', 'Hty/M6QshYvPgItX2P0+Kw==');