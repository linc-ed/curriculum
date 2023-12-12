<?php

require_once '../../../classes/PdfUtilities.php';

$docx = new PdfUtilities();

$source = '../../files/Test.pdf';
$target = 'example_watermarkImage.pdf';

$docx->watermarkPdf($source, $target, 'image', array('image' => '../../files/image.png'));

// the method allows to set a fixed position if needed
//$docx->watermarkPdf($source, $target, 'image', array('image' => '../../files/image.png', 'positionX' => 70, 'positionY' => 120));