<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceToBeCloned = array(
    'type' => 'table',
    'occurrence' => 1,
);

$referenceNodeTo = array(
    'type' => 'chart',
    'occurrence' => 1,
);

$docx->cloneWordContent($referenceToBeCloned, $referenceNodeTo, 'before');

$docx->createDocx('example_cloneWordContent_6');