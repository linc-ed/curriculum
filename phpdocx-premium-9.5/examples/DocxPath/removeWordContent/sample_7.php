<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/lists.docx');

$referenceNode = array(
    'type' => 'list',
    'occurrence' => -1,
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_7');