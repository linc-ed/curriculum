<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/headings.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'occurrence' => 1,
    'attributes' => array('w:outlineLvl' => array('w:val' => 2)),
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_5');