<?php

require_once '../../../classes/PdfUtilities.php';

$docx = new PdfUtilities();

$source = '../../files/document_test.pdf';
$target = 'splitPdf_.pdf';

$docx->splitPdf($source, $target, array('pages' => array(3)));