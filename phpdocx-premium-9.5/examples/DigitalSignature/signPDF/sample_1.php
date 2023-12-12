<?php

require_once '../../../classes/SignPDF.php';

$sign = new SignPDF();

$sign->setPDF('../../files/Test.pdf');
$sign->setPrivateKey('../../files/Test.pem', 'phpdocx_pass');
$sign->setX509Certificate('../../files/Test.pem');
$sign->sign('Test_signed.pdf');