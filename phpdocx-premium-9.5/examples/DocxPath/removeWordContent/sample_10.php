<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/charts.docx');

$referenceNode = array(
    'type' => 'chart',
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_10');