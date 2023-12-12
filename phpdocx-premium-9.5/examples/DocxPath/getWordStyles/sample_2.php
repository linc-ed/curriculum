<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'default',
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);