<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();
$docx->importHeadersAndFooters('../../files/TemplateHeaderAndFooter.docx');

$referenceNode = array(
    'target' => 'header',
    'type' => 'table',
);

$contents = $docx->getWordContents($referenceNode);

print_r($contents);