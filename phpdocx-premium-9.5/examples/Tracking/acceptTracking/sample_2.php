<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/tracking_content.docx');

$referenceNode = array(
	'type' => 'paragraph',
    'contains' => 'xmldocx',
);

$docx->acceptTracking($referenceNode);

$docx->createDocx('example_acceptTracking_2');