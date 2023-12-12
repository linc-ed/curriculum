<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/links.docx');

$referenceNodeFrom = array(
    'type' => 'paragraph',
    'occurrence' => 1,
    'contains' => 'HYPERLINK',
);

$referenceNodeTo = array(
    'type' => 'paragraph',
    'occurrence' => 2,
    'contains' => 'HYPERLINK',
);

$docx->moveWordContent($referenceNodeFrom, $referenceNodeTo, 'after');

$docx->createDocx('example_moveWordContent_3');