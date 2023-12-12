<?php

require_once '../../../classes/CreateDocx.php';

$docxStructureStream = new DOCXStructureFromStream();
$docxStructure = $docxStructureStream->generateDOCXStructure('http://www.phpdocx.com/files/samples/TemplateSimpleText.docx');

$docx = new CreateDocxFromTemplate($docxStructure);

$variables = array('FIRSTTEXT' => 'PHPDocX', 'MULTILINETEXT' => 'This is the first line.\nThis is the second line of text.');
$options = array('parseLineBreaks' => true);

$docx->replaceVariableByText($variables, $options);

$docx->createDocx('example_generateDocxStructureFromStream_1');