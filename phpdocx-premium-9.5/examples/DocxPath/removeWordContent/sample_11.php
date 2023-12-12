<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'customQuery' => '//*[preceding-sibling::w:p[w:r/w:t[text()[contains(.,"A level 2 heading")]]] and following-sibling::w:p[w:r/w:t[text()[contains(.,"Another heading")]]]]',
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_11');