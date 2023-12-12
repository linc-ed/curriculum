<?php

require_once '../../../classes/PdfUtilities.php';

$docx = new PdfUtilities();

$source = '../../files/document_test.pdf';
$target = 'removePagesPdf.pdf';

$docx->removePagesPdf($source, $target, array('pages' => array(2)));