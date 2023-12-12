<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();
$docx->importHeadersAndFooters('../../files/TemplateHeaderAndFooter.docx');

$referenceNode = array(
    'target' => 'header',
    'type' => 'paragraph',
);

$queryInfo = $docx->getDocxPathQueryInfo($referenceNode);

var_dump($queryInfo);