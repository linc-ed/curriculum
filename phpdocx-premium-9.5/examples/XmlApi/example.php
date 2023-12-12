<?php

require_once '../../classes/CreateDocx.php';

$docx = new XMLAPI('config.xml');
$docx->setDocumentProperties('settings.xml');
$docx->addContent('content.xml');

$docx->render();