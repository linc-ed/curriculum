<?php

require_once '../../../classes/CreateDocx.php';

// this object can be serialized (in memory, database, file system...) to be reused later
$docxStructure = new DOCXStructure();
$docxStructure->parseDocx('../../files/TemplateSimpleText.docx');

$docx = new CreateDocxFromTemplate($docxStructure);

$first = 'PHPDocX';
$multiline = 'This is the first line.\nThis is the second line of text.';

$variables = array('FIRSTTEXT' => $first, 'MULTILINETEXT' => $multiline);
$options = array('parseLineBreaks' => true);

$docx->replaceVariableByText($variables, $options);

$docx->createDocx('example_parseDocx_1');