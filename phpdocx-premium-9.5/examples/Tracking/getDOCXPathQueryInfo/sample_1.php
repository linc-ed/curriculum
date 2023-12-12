<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/tracking_content.docx');

$referenceNode = array(
    'type' => 'tracking-insert',
    'parent' => '/',
);

$queryInfo = $docx->getDocxPathQueryInfo($referenceNode);

var_dump($queryInfo);