<?php

require_once '../../../classes/DocxUtilities.php';

$docx = new DocxUtilities();

$source = '../../files/Text.docx';
$target = 'example_watermarkImage.docx';

$docx->watermarkDocx($source, $target, 'image', array('image' => '../../files/image.png', 'decolorate' => false));