<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/links.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'occurrence' => 2,
    'contains' => 'HYPERLINK',
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_6');