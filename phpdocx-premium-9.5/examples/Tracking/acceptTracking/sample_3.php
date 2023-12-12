<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/tracking_tables.docx');

$referenceNode = array(
	'type' => 'table',
    'occurrence' => '1..2',
);

$docx->acceptTracking($referenceNode);

$referenceNode = array(
    'type' => 'table',
    'occurrence' => -1,
);

$docx->acceptTracking($referenceNode);

$docx->createDocx('example_acceptTracking_3');