<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/tables.docx');

$referenceNodeFrom = array(
    'type' => 'paragraph',
    'parent' => '/w:tc/',
    'occurrence' => 4,
);

$referenceNodeTo = array(
    'type' => 'paragraph',
    'parent' => '/w:tc/',
    'occurrence' => 8,
);

$docx->moveWordContent($referenceNodeFrom, $referenceNodeTo, 'after');

$content = new WordFragment($docx, 'document');

$content->addText('New text to avoid empty cell');

$referenceNode = array(
    'parent' => '/w:tc/',
    'occurrence' => 7,
);

$docx->insertWordFragment($content, $referenceNode, 'after');

$docx->createDocx('example_moveWordContent_5');