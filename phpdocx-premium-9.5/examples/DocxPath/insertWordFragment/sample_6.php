<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/sections.docx');

$contentA = new WordFragment($docx, 'document');
$contentA->addText('New text at the beginning');
$referenceNode = array(
    'type' => '*',
    'occurrence' => 1,
);
$docx->insertWordFragment($contentA, $referenceNode, 'before');

$contentB = new WordFragment($docx, 'document');
$contentB->addText('New text second page');
$referenceNode = array(
    'type' => 'section',
    'occurrence' => 1,
);
$docx->insertWordFragment($contentB, $referenceNode, 'after');

$contentC = new WordFragment($docx, 'document');
$contentC->addText('New text first page');
$referenceNode = array(
    'type' => 'section',
    'occurrence' => 1,
);
$docx->insertWordFragment($contentC, $referenceNode, 'before', true);

$contentD = new WordFragment($docx, 'document');
$contentD->addText('New text at the end');
$referenceNode = array(
    'type' => '*',
    'occurrence' => -1,
);
$docx->insertWordFragment($contentD, $referenceNode, 'after');

$docx->createDocx('example_insertWordFragment_6');