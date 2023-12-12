<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CryptoPHPDOCX();
$source = '../../files/sample.pptx';
$target = 'Crypted.pptx';
$docx->encryptPPTX($source, $target, array('password' => 'phpdocx'));
