<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/SimpleExample.docx');

$referenceNode = array(
    'type' => 'image',
    'occurrence' => 1,
);

$contents = $docx->getWordStyles($referenceNode);

print_r($contents);