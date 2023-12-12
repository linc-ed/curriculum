<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/tables.docx');

$referenceNode = array(
    'customQuery' => '//w:tbl/w:tr[2]/w:tc[1]',
);

$contents = $docx->getWordContents($referenceNode);

print_r($contents);