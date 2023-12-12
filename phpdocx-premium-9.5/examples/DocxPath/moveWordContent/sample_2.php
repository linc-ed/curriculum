<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/images.docx');

$referenceNodeFrom = array(
    'type' => 'image',
);

$referenceNodeTo = array(
    'type' => 'paragraph',
    'contains' => 'closing paragraph',
);

$docx->moveWordContent($referenceNodeFrom, $referenceNodeTo, 'after');

$docx->createDocx('example_moveWordContent_2');