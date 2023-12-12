<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/tables.docx');

// clone the second row of the table
$referenceToBeCloned = array(
    'customQuery' => '//w:tbl/w:tr[2]',
);

$referenceNodeTo = array(
    'customQuery' => '//w:tbl/w:tr[2]',
);

$docx->cloneWordContent($referenceToBeCloned, $referenceNodeTo, 'after');

$docx->createDocx('example_cloneWordContent_7');