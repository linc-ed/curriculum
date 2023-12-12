<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNodeFrom = array(
    'type' => 'table',
    'occurrence' => 1,
);

$referenceNodeTo = array(
    'type' => 'chart',
    'occurrence' => 1,
);

$docx->moveWordContent($referenceNodeFrom, $referenceNodeTo, 'before');

$docx->createDocx('example_moveWordContent_6');