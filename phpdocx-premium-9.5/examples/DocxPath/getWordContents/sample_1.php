<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'contains' => 'heading',
);

$contents = $docx->getWordContents($referenceNode);

print_r($contents);