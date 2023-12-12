<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$docx->addText('Not tracked paragraph');

$docx->addPerson(array('author' => 'phpdocx'));

$docx->enableTracking(array('author' => 'phpdocx'));

$docx->addText('A tracked paragraph');

$itemList = array(
    'Line 1',
    array(
        'Line A',
        'Line B',
        'Line C'
    ),
    'Line 2',
    'Line 3',
);

$docx->addList($itemList, 2);

$docx->addLink('Link to Google', array('url'=> 'http://www.google.es'));

$docx->disableTracking();

$docx->addText('Other paragraph');

$docx->createDocx('example_enableTracking_2');