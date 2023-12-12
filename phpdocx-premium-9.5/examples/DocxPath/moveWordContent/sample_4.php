<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/sections.docx');

$referenceNodeFrom = array(
    'type' => 'paragraph',
    'occurrence' => 1,
    'contains' => 'This is other section',
);

$referenceNodeTo = array(
    'type' => 'section',
    'occurrence' => 1,
);

$docx->moveWordContent($referenceNodeFrom, $referenceNodeTo, 'before');

$docx->createDocx('example_moveWordContent_4');