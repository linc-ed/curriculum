<?php

require_once '../../../classes/PdfUtilities.php';

$docx = new PdfUtilities();

$source = '../../files/Test.pdf';
$target = 'example_watermarkText.pdf';

$docx->watermarkPdf($source, $target, 'text', array('text' => 'phpdocx'));