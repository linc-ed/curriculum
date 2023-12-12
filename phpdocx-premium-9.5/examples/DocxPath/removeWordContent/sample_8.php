<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/sections.docx');

$referenceNode = array(
    'type' => 'section',
    'occurrence' => 1,
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_8');