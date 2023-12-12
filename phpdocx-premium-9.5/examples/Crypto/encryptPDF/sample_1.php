<?php

require_once '../../../classes/CreateDocx.php';

$pdf = new CryptoPHPDOCX();
$source = '../../files/Test.pdf';
$target = 'crypted.pdf';
$pdf->encryptPDF($source, $target, array('password' => 'phpdocx'));