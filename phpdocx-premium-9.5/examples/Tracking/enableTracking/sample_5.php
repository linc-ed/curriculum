<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$docx->addText('Not tracked paragraph');

$docx->addPerson(array('author' => 'phpdocx'));

$docx->enableTracking(array('author' => 'phpdocx'));

// create a Word fragment with an image to be inserted in the header of the document
$imageOptions = array(
    'src' => '../../img/image.png', 
    'dpi' => 300,  
);

$image = new WordFragment($docx, 'defaultHeader');
$image->addImage($imageOptions);

$docx->addHeader(array('default' => $image));

$textFooter = new WordFragment($docx, 'firstFooter');
$textFooter->addText('page footer.');

$docx->addFooter(array('default' => $textFooter));

$docx->addSection('nextPage', 'A3');

$docx->addText('Other text');

$paramsText = array(
    'b' => true
);

$docx->addText('New section', $paramsText);

$docx->disableTracking();

$docx->addText('Other paragraph');

$docx->createDocx('example_enableTracking_5');