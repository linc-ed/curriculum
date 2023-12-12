<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/bookmarks.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'occurrence' => 1,
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_2');