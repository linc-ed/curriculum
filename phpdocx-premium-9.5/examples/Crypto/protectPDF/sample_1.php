<?php

require_once '../../../classes/CreateDocx.php';

$pdf = new CryptoPHPDOCX();
$source = '../../files/Test.pdf';
$target = 'protected.pdf';
$pdf->protectPDF($source, $target, array('permissionsBlocked' => array('print', 'annot-forms')));