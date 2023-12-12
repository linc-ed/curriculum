<?php

require_once '../../../classes/DocxUtilities.php';

$docx = new DocxUtilities();

$source = '../../files/Text.docx';
$target = 'example_watermarkText.docx';

$docx->watermarkDocx($source, $target, 'text', array('text' => 'phpdocx'));