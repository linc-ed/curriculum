<?php

require_once '../../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' .
    'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut ' .
    'enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut' .
    'aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit ' .
    'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' .
    'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui ' .
    'officia deserunt mollit anim id est laborum.';

$paramsText = array(
    'b' => 'single',
    'font' => 'Arial'
);

$docx->addText($text, $paramsText);

$docx->createDocx('transformDocument_libreoffice_2');

$docx->transformDocument('transformDocument_libreoffice_2.docx', 'transformDocument_libreoffice_2.doc', 'libreoffice');