<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'occurrence' => 1,
    'contains' => 'A level 2 heading',
);

$startProtection = new WordFragment($docx, 'document');
$startProtection->addPermProtection('start');

$docx->insertWordFragment($startProtection, $referenceNode, 'before');

$endProtection = new WordFragment($docx, 'document');
$endProtection->addPermProtection('end');

$referenceNode = array(
    'type' => 'image',
    'occurrence' => 1,
);

$docx->insertWordFragment($endProtection, $referenceNode, 'after');

$docx->createDocx('example_addPermProtection_2');

$docx = new CryptoPHPDOCX();
$docx->protectDocx('example_addPermProtection_2.docx', 'example_addPermProtection_protected_2.docx', array('password' => 'phpdocx'));