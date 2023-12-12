<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'list',
    'occurrence' => 1,
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);