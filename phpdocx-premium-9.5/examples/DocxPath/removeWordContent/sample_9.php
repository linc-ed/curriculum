<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/tables.docx');

// remove the second row of the table
$referenceNode = array(
    'customQuery' => '//w:tbl/w:tr[2]',
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_9');