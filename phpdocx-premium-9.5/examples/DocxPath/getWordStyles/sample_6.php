<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/example_area_chart.docx');

$referenceNode = array(
    'type' => 'chart',
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);