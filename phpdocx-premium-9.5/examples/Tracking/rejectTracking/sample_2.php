<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/tracking_content.docx');

$referenceNode = array(
	'type' => 'paragraph',
    'contains' => 'xmldocx',
);

$docx->rejectTracking($referenceNode);

$docx->createDocx('example_rejectTracking_2');