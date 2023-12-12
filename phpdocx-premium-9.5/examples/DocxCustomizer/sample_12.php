<?php

require_once '../../classes/CreateDocx.php';

$docx = new CreateDocxFromTemplate('../files/DOCXPathTemplate.docx');

$referenceNode = array(
    'type' => 'paragraph',
    'contains' => 'simple Word document',
);

$docx->customizeWordContent($referenceNode, 
    array(
        'bold' => true,
        'caps' => true,
    )
);

$referenceNode = array(
    'type' => 'paragraph',
    'contains' => 'Now a chart',
);

$docx->customizeWordContent($referenceNode, 
    array(
        'backgroundColor' => 'FFFF00',
        'bold' => true,
        'italic' => true,
    )
);

$docx->createDocx('example_customizer_12');