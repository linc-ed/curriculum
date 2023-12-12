<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/tracking_content.docx');

$referenceNode = array(
	'type' => 'paragraph',
    'contains' => 'www.phpdocx.com',
);

$docx->acceptTracking($referenceNode);

$referenceNode = array(
    'type' => 'paragraph',
    'contains' => 'phpdocx is a library',
);

$docx->acceptTracking($referenceNode);

$docx->createDocx('example_acceptTracking_1');