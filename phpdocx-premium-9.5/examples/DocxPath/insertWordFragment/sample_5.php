<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/docxpath/lists.docx');

$content = new WordFragment($docx, 'document');

$content->addText('New text');

$referenceNode = array(
	'type' => 'list',
    'occurrence' => -1,
);

$docx->insertWordFragment($content, $referenceNode, 'after');

$docx->createDocx('example_insertWordFragment_5');