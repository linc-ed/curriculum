<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$docx->addText('Not tracked paragraph');

$docx->addPerson(array('author' => 'phpdocx'));

$docx->enableTracking(array('author' => 'phpdocx'));

$docx->addText('First tracked paragraph');

$docx->addText('Second tracked paragraph');

$docx->disableTracking();

$docx->addText('Other paragraph');

$docx->enableTracking(array('author' => 'phpdocx'));

$docx->addText('Other tracked paragraph');

$docx->disableTracking();

$docx->createDocx('example_disableTracking_1');