<?php

require_once '../../classes/CreateDocx.php';

$docx = new CreateDocx();
$docx->importHeadersAndFooters('../files/TemplateHeaderAndFooter.docx');

$referenceNode = array(
    'target' => 'footer',
    'type' => 'paragraph',
    'reference' => array(
        'types' => array('default'),
    ),
);

$docx->customizeWordContent($referenceNode, 
    array(
        'backgroundColor' => 'FFFF00',
    )
);

$docx->createDocx('example_customizer_14');