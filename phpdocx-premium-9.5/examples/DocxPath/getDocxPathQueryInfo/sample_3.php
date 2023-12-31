<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$text = 'Lorem ipsum dolor sit amet, consectetur adipisicing elit, ' .
    'sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut ' .
    'enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut' .
    'aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit ' .
    'in voluptate velit esse cillum dolore eu fugiat nulla pariatur. ' .
    'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui ' .
    'officia deserunt mollit anim id est laborum.';

$paragraphOptions = array(
    'bold' => true,
    'font' => 'Arial',
);

$docx->addText($text, $paragraphOptions);

$text = 'Sed ut perspiciatis unde omnis iste natus error sit voluptatem ' . 
    'accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ' . 
    'ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt ' . 
    'explicabo. Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut ' . 
    'odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem ' . 
    'sequi nesciunt.';

$docx->addText($text, $paragraphOptions);

$referenceNode = array(
    'type' => 'paragraph',
);

$queryInfo = $docx->getDocxPathQueryInfo($referenceNode);

$secondElementContent = $queryInfo['elements'][1]->ownerDocument->saveXml($queryInfo['elements'][1]);
$secondElementChanged = str_replace('unde omnis', 'other text', $secondElementContent);

$wordML = new WordFragment();
$wordML->addWordML($secondElementChanged);

$referenceNode = array(
    'type' => 'paragraph',
    'occurrence' => 2,
);

$docx->replaceWordContent($wordML, $referenceNode);

$docx->createDocx('example_getDocxPathQueryInfo_3');