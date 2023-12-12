<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$imageOptions = array(
    'src' => '../../img/image.png', 
    'dpi' => 300,  
);

$headerContent = new WordFragment($docx, 'defaultHeader');
$headerContent->addText('New text.', array('fontSize' => 20, 'color' => '#0000ff'));
$headerContent->addImage($imageOptions);

$docx->addHeader(array('default' => $headerContent));

$text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' .
    'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut ' .
    'enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut' .
    'aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit ' .
    'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' .
    'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui ' .
    'officia deserunt mollit anim id est laborum.';

$docx->addText($text);

$referenceNode = array(
    'target' => 'header',
    'type' => 'image',
    'occurrence' => 1,
);

$docx->removeWordContent($referenceNode);

$docx->createDocx('example_removeWordContent_13');