<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/tracking_tables.docx');

$referenceNode = array(
	'type' => 'table',
    'occurrence' => '1..2',
);

$docx->rejectTracking($referenceNode);

$referenceNode = array(
    'type' => 'table',
    'occurrence' => -1,
);

$docx->rejectTracking($referenceNode);

$docx->createDocx('example_rejectTracking_3');