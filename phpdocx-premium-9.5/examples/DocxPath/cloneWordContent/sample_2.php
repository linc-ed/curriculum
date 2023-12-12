<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/images.docx');

$referenceToBeCloned = array(
    'type' => 'image',
);

$referenceNodeTo = array(
    'type' => 'paragraph',
    'contains' => 'closing paragraph',
);

$docx->cloneWordContent($referenceToBeCloned, $referenceNodeTo, 'after');

$docx->createDocx('example_cloneWordContent_2');