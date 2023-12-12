<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'contains' => 'heading',
);

$queryInfo = $docx->getDocxPathQueryInfo($referenceNode);

for ($i = 1; $i <= $queryInfo['length']; $i++) {
    $content = new WordFragment($docx, 'document');

    $referenceNode = array(
        'type' => 'paragraph',
        'contains' => 'heading',
        'occurrence' => $i,
    );

    $content->addText('New text', array('sz' => 18));

    $docx->insertWordFragment($content, $referenceNode, 'after');
}

$docx->createDocx('example_getDocxPathQueryInfo_2');