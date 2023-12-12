<?php

require_once '../../../classes/DocxUtilities.php';
require_once '../../../classes/MultiMerge.php';

$merge = new MultiMerge();
$merge->mergeDocx('../../files/Text.docx', array('../../files/second.docx', '../../files/SimpleExample.docx'), 'output.docx', array());

$docx = new DocxUtilities();
$docx->watermarkDocx('output.docx', 'output_watermark.docx', 'text', array('text' => 'DRAFT', 'section' => 1));
$docx->watermarkDocx('output_watermark.docx', 'output_watermark_2.docx', 'text', array('text' => 'CONFIDENTIAL', 'section' => 2));