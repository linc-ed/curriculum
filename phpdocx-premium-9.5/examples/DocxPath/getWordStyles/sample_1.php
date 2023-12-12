<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'contains' => 'level 2 heading',
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);