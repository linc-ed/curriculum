<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/breaks.docx');

$referenceNode = array(
    'type' => 'break',
    'attributes' => array('w:type' => 'page'),
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_3');