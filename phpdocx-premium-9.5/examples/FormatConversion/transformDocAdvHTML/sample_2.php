<?php

require_once '../../../classes/CreateDocx.php';

$docx = new CreateDocx();

$valuesTable = array(
    array(
        11,
        12,
        13,
        14
    ),
    array(
        21,
        22,
        23,
        24
    ),
    array(
        31,
        32,
        33,
        34
    ),

);

$paramsTable = array(
    'border' => 'single',
    'tableAlign' => 'center',
    'borderWidth' => 10,
    'borderColor' => 'B70000',
    'font' => 'Arial',
);

$docx->addTable($valuesTable, $paramsTable);

$link = new WordFragment($docx);
$options = array(
    'url' => 'http://www.google.es'
);

$link->addLink('Link to Google', $options);

$image = new WordFragment($docx);
$options = array(
    'src' => '../../files/image.png'
);

$image->addImage($options);

$valuesTable = array(
    array(
        'Title A',
        'Title B',
        'Title C'
    ),
    array(
        'Line A',
        $link,
        $image
    )
);


$paramsTable = array(
    'tableStyle' => 'LightListAccent1PHPDOCX',
    'tableAlign' => 'center',
    'columnWidths' => array(1000, 2500, 3000),
    );

$docx->addTable($valuesTable, $paramsTable);

$docx->createDocx('example_transformDocAdvHTML_2.docx');

$transformHTMLPlugin = new TransformDocAdvHTMLDefaultPlugin();

$transform = new TransformDocAdvHTML('example_transformDocAdvHTML_2.docx');
$html = $transform->transform($transformHTMLPlugin);

echo $html;