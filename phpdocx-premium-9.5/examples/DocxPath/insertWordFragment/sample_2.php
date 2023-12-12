<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/breaks.docx');

$content = new WordFragment($docx, 'document');

$content->addText('New text');

$valuesTable = array(
    array(
        'AAA',
        'BBB',
    ),
    array(
        'Text',
        'Text: More text',
    ),

);
$paramsTable = array(
    'border' => 'single',
    'tableAlign' => 'center',
    'borderWidth' => 10,
    'borderColor' => 'B70000',
    'textProperties' => array('bold' => true),
);
$content->addTable($valuesTable, $paramsTable);

$referenceNode = array(
	'type' => 'break',
    'attributes' => array('w:type' => 'page'),
);

$docx->insertWordFragment($content, $referenceNode, 'after');

$docx->createDocx('example_insertWordFragment_2');