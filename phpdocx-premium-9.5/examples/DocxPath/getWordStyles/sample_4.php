<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'style',
    'contains' => 'ListParagraph',
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);