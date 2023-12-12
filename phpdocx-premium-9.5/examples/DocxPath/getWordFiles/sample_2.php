<?php

require_once '../../../classes/CreateDocx.php';

// load a template, get the document body content, do a replacement and get the DOCX as a structure
$docx = new CreateDocxFromTemplate('../../files/TemplateSimpleText.docx');
CreateDocx::$returnDocxStructure = true;

$contents = $docx->getWordFiles('word/document.xml');

var_dump($contents);

$first = 'PHPDocX';
$multiline = 'This is the first line.\nThis is the second line of text.';

$variables = array('FIRSTTEXT' => $first, 'MULTILINETEXT' => $multiline);
$options = array('parseLineBreaks' => true);

$docx->replaceVariableByText($variables, $options);

$docxStructure = $docx->createDocx();

// load the DOCX structure and get the document body content to see the changes
$docx = new CreateDocxFromTemplate($docxStructure);

$contents = $docx->getWordFiles('word/document.xml');

var_dump($contents);