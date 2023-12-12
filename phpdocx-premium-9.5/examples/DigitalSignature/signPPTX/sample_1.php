<?php

require_once '../../../classes/SignPPTX.php';

$sign = new SignPPTX();

copy('../../files/sample.pptx', 'sample.pptx');
$sign->setPptx('sample.pptx');
$sign->setPrivateKey('../../files/Test.pem', 'phpdocx_pass');
$sign->setX509Certificate('../../files/Test.pem');
$sign->setSignatureComments('This document has been signed by me');
$sign->sign();