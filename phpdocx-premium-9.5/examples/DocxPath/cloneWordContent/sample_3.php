<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/links.docx');

$referenceToBeCloned = array(
    'type' => 'paragraph',
    'occurrence' => 1,
    'contains' => 'HYPERLINK',
);

$referenceNodeTo = array(
    'type' => 'paragraph',
    'occurrence' => 2,
    'contains' => 'HYPERLINK',
);

$docx->cloneWordContent($referenceToBeCloned, $referenceNodeTo, 'after');

$docx->createDocx('example_cloneWordContent_3');